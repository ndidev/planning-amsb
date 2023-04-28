import type { breakpoints } from "@app/utils";
import type { TypesModules } from "@app/auth";

/**
 * Identifiants des modules de l'application.
 */
export type ModuleId = keyof Roles;

/**
 * Identifiants des modules "planning" de l'application.
 */
export type ModulePlanning = keyof Pick<
  Roles,
  "bois" | "vrac" | "consignation" | "chartering"
>;

/**
 * Autorisations attribuées à l'utilisateur pour chaque rubrique de l'application.
 */
export type Roles = {
  /**
   * Accès au planning bois.
   */
  bois?: 0 | 1 | 2;

  /**
   * Accès au planning vrac.
   */
  vrac?: 0 | 1 | 2;

  /**
   * Accès au planning consignation.
   */
  consignation?: 0 | 1 | 2;

  /**
   * Accès au planning affrètement maritime.
   */
  chartering?: 0 | 1 | 2;

  /**
   * Accès à la page des tiers.
   */
  tiers?: 0 | 1;

  /**
   * Accès à la page de configuration.
   */
  config?: 0 | 1;

  /**
   * Accès à l'interface d'administration.
   */
  admin?: 0 | 1;
};

export type Module = {
  /**
   * Nom d'affichage du module.
   */
  affichage: string;

  /**
   * Type de module (accès/modification).
   */
  type: TypesModules;
  tree: Tree;
};

type Tree = {
  /**
   * Texte affiché pour le lien.
   */
  affichage?: string;

  /**
   * URL du lien.
   */
  href?: string;

  /**
   * Rôle minimum de l'utilisateur pour afficher le lien.
   */
  roleMini?: 0 | 1 | 2;

  /**
   * Appareils sur lesquels afficher le lien.
   */
  devices?: Array<(typeof breakpoints)[number]["type"]>;

  /**
   * Enfants de la rubrique.
   */
  children?: Tree[];
};
