import type http from "node:http";
import { env } from "node:process";
import cookie from "cookie";
import { createClient } from "redis";
import md5 from "md5";

const REDIS_HOST = env["REDIS_HOST"] as string;
const REDIS_PORT = parseInt(env["REDIS_PORT"] as string);
const COOKIE_NAME = env["COOKIE_NAME"] as string;

const redis = createClient({
  url: `redis://${REDIS_HOST}:${REDIS_PORT}`,
});

redis.on("error", (err: unknown) => {
  console.error(`[${new Date().toISOString()}] Erreur Redis`, err);
});

// Connexion unique au serveur Redis
(async () => {
  try {
    await redis.connect();
    console.log("Connexion à Redis OK");
  } catch (error) {
    console.error("Erreur de connexion à Redis", error);
  }
})();

// Ping régulier pour maintenir la connexion
const pingInterval = 60; // secondes
setInterval(async () => {
  try {
    if (!(await redis.ping())) await redis.connect();
  } catch (error) {
    console.error("Erreur redis.connect", error);
  }
}, pingInterval * 1000);

/**
 * Authentifie un utilisateur
 * grâce à la session ou la clé d'API.
 */
export async function authenticate(
  request: http.IncomingMessage
): Promise<string | false> {
  let userId: string | null = null;
  let sessionUserId: string | null = null;
  let sessionOK: boolean = false;
  let apiKeyUserId: string | null = null;
  let apiKeyOK: boolean = false;

  // Via cookie
  const cookies = cookie.parse(request.headers.cookie || "");
  const sessionId = cookies[COOKIE_NAME];

  if (sessionId) {
    sessionUserId = await getUserIdFromSession(sessionId);
    sessionOK = !!sessionUserId;
  }

  if (!sessionOK) {
    // Via clé d'API
    const apiKey =
      new URLSearchParams(request.url?.slice(1)).get("apiKey") || "";

    if (apiKey) {
      apiKeyUserId = await getUserIdFromApiKey(apiKey as string);
      apiKeyOK = !!apiKeyUserId;
    }
  }

  if (sessionOK || apiKeyOK) {
    // Si l'authentification a réussi
    userId = (sessionUserId || apiKeyUserId) as string;
    return userId;
  } else {
    // Si l'authentification a échoué
    return false;
  }
}

/**
 * Vérifier la session.
 *
 * @param sessionId Identifiant de session
 */
async function getUserIdFromSession(sessionId: string): Promise<string | null> {
  try {
    return await redis.get(`admin:sessions:${sessionId}`);
  } catch (err) {
    console.error("Erreur redis.get", err);
    return null;
  }
}

/**
 * Vérifier la clé d'API.
 *
 * @param apiKey Clé d'API
 */
async function getUserIdFromApiKey(apiKey: string): Promise<string | null> {
  try {
    const { uid, status } = await redis.hGetAll(`admin:apikeys:${md5(apiKey)}`);
    if (uid && status === "active") {
      return uid;
    } else {
      return null;
    }
  } catch (err) {
    console.error("Erreur redis.get", err);
    return null;
  }
}
