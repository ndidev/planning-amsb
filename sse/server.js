const http = require("node:http");
const cookie = require("cookie");
const { v4: uuid } = require("uuid");
const { createClient } = require("redis");

const SSE_PORT = 3000;
const UPDATES_PORT = 3001;

/** @type {string} */
const REDIS_HOST = process.env.REDIS_HOST;
/** @type {number} */
const REDIS_PORT = process.env.REDIS_PORT;
/** @type {string} */
const COOKIE_NAME = process.env.COOKIE_NAME;

const redis = createClient({
  url: `redis://${REDIS_HOST}:${REDIS_PORT}`,
});

redis.on("error", (err) => {
  console.error(`[${new Date().toISOString()}] Erreur Redis`, err);
});

// Connexion unique au serveur Redis
(async () => await redis.connect())();

// Ping régulier pour maintenir la connexion
const pingDelai = 60 * 1000; // 1 minute
const pingInterval = setInterval(async () => {
  try {
    if (!(await redis.ping())) await redis.connect();
  } catch (err) {
    console.error("Erreur redis.connect", err);
  }
}, pingDelai);

/**
 * @typedef  DBEvent
 * @type     {Object}
 * @property {string} type    Type d'événement (create|update|delete)
 * @property {string} module  Rubrique/module concerné par l'événement
 * @property {number} id      ID de la connexion
 */

/**
 * @typedef  Connection
 * @type     {Object}
 * @property {string}               uid           UID de l'utilisateur
 * @property {number}               id            ID de la connexion
 * @property {http.IncomingMessage} request       Requête HTTP
 * @property {http.ServerResponse}  response      Réponse HTTP
 * @property {string[]}             subscriptions Liste des rubriques souscrites
 */

/** @type {Connection[]} - Liste des connexions actives */
let connections = [];

/**
 * @param {http.IncomingMessage} request
 * @param {http.ServerResponse} response
 */
async function sseListener(request, response) {
  // Récupération des données de l'utilisateur qui se connecte
  const cookies = cookie.parse(request.headers.cookie || "");
  const sessionId = cookies[COOKIE_NAME];

  /** @type {string?} UID de l'utilisateur */
  let uid = null;

  try {
    /** @type {string?} UID de l'utilisateur */
    uid = await redis.get(`sessions:${sessionId}`);
  } catch (err) {
    console.error("Erreur redis.get", err);
    return;
  }

  // Si aucune session active trouvée, renvoyer une erreur 401
  if (!uid) {
    response.statusCode = 401;
    response.end();
    return;
  }

  const queryString = request.url.split("?")[1];
  const query = new URLSearchParams(queryString);
  const subscriptions = query.get("subs")?.split(",") || [];

  /** @type {number} ID de la connexion */
  const id = uuid();

  // Mise à jour du tableau des connexions actives
  // en cas de nouvelle connexion
  connections.push({
    uid,
    id,
    request,
    response,
    subscriptions,
  });

  // Établissement de la connexion
  response.statusCode = 200;
  response.setHeader("Content-Type", "text/event-stream");
  response.setHeader("Cache-Control", "no-cache");
  response.setHeader("Connection", "keep-alive");
  response.setHeader("Access-Control-Allow-Origin", "*");
  response.write("event: open\n");
  response.write("retry: 5000\n\n");

  // Ping régulier pour maintenir la connexion ouverte
  const pingTicker = 60; // secondes
  setInterval(() => {
    response.write(":ping\n\n");
  }, pingTicker * 1000);

  // Mise à jour du tableau des connexions actives
  // en cas de fermeture d'une connexion
  request.on("close", () => {
    connections = connections.filter((connection) => connection.id !== id);
  });
}

const sseServer = http.createServer(sseListener);
sseServer.listen(SSE_PORT, () => {
  console.log(
    `[${new Date().toISOString()}] SSE server listening on port ${SSE_PORT}...`
  );
});

/**
 * Updates server
 */

/**
 * @param {http.IncomingMessage} request
 * @param {http.ServerResponse} response
 */
const updatesListener = (request, response) => {
  /** @type {string} - Corps de la requête */
  let body = "";

  request.on("data", (chunk) => {
    body += chunk;
  });

  request.on("end", () => {
    /** @type {DBEvent} */ const event = JSON.parse(body);
    response.end();

    // Envoi de l'événement/notification aux clients concernés
    connections.forEach((connection) => {
      if (connection.subscriptions.includes(event.module)) {
        connection.response.write(`event: db\n`);
        connection.response.write(`data: ${body}\n\n`);
      }
    });

    // En cas de mise à jour d'un compte utilisateur par l'admin ou l'utilisateur lui-même,
    // envoi de la mise à jour au compte concerné (événement "user")
    if (event.module === "admin/users" || event.module === "user") {
      // Envoi à l'utilisateur concerné
      event.module = "user";
      body = JSON.stringify(event);

      connections
        .filter((connection) => connection.uid === event.id)
        .forEach((connection) => {
          connection.response.write(`event: db\n`);
          connection.response.write(`data: ${body}\n\n`);
        });
    }
  });
};

const updatesServer = http.createServer(updatesListener);
updatesServer.listen(UPDATES_PORT, () => {
  console.log(
    `[${new Date().toISOString()}] Updates server listening on port ${UPDATES_PORT}...`
  );
});
