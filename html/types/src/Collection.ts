import type { ModuleId } from "@app/types";

/**
 * Collection d'objets associés à un module de l'application.
 */
export type Collection<T extends { id: number; module: ModuleId }> = {
  [K in T["module"]]: Map<T["id"], T>;
};
