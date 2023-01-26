import { UserRoles } from "@app/auth";

type Module = {
  /**
   * Nom d'affichage du module.
   */
  affichage: string;

  /**
   * Type de module (accès/modification).
   */
  type: string;
  tree: Tree;
};

type Tree = {
  affichage?: string;
  href?: string;
  roleMini?: 0 | 1 | 2;
  children?: Tree[];
};

/**
 * Types de modules.
 */
export class TypesModules {
  /**
   * Le module est de type "Accès/Pas accès".
   */
  static get ACCESS() {
    return "access";
  }

  /**
   * Le module est de type "Aucun/Voir/Modifier".
   */
  static get EDIT() {
    return "edit";
  }
}

/**
 * Modules (rubriques) de l'application.
 */
export const sitemap: Map<string, Module> = new Map([
  [
    "bois",
    {
      affichage: "Bois",
      type: TypesModules.EDIT,
      tree: {
        children: [
          {
            affichage: "Planning",
            roleMini: UserRoles.ACCESS,
            href: "/bois/",
          },
          {
            affichage: "Statistiques",
            roleMini: UserRoles.ACCESS,
            href: "/bois/stats/",
          },
        ],
      },
    },
  ],
  [
    "vrac",
    {
      affichage: "Vrac",
      type: TypesModules.EDIT,
      tree: {
        children: [
          {
            affichage: "Planning",
            roleMini: UserRoles.ACCESS,
            href: "/vrac/",
          },
          {
            affichage: "Produits",
            roleMini: UserRoles.EDIT,
            href: "/vrac/produits/",
          },
        ],
      },
    },
  ],
  [
    "consignation",
    {
      affichage: "Consignation",
      type: TypesModules.EDIT,
      tree: {
        children: [
          {
            affichage: "Planning",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/",
          },
          {
            affichage: "Archives",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/?archives",
          },
          {
            affichage: "Tirants d'eau",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/te/",
          },
        ],
      },
    },
  ],
  [
    "chartering",
    {
      affichage: "Affrètement maritime",
      type: TypesModules.EDIT,
      tree: {
        children: [
          {
            affichage: "Planning",
            roleMini: UserRoles.ACCESS,
            href: "/chartering/",
          },
          {
            affichage: "Archives",
            roleMini: UserRoles.ACCESS,
            href: "/chartering/?archives",
          },
        ],
      },
    },
  ],
  [
    "tiers",
    {
      affichage: "Tiers",
      type: TypesModules.ACCESS,
      tree: {
        href: "/tiers/",
      },
    },
  ],
  [
    "config",
    {
      affichage: "Configuration",
      type: TypesModules.ACCESS,
      tree: {
        href: "/config/",
      },
    },
  ],
  [
    "admin",
    {
      affichage: "Administration",
      type: TypesModules.ACCESS,
      tree: {
        href: "/admin/",
      },
    },
  ],
]);
