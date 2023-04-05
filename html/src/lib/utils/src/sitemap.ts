import { UserRoles } from "@app/auth";
import type { ModuleId, Module } from "@app/types";

/**
 * Types de modules.
 */
export enum TypesModules {
  /**
   * Le module est de type "Accès/Pas accès".
   */
  ACCESS = "access",

  /**
   * Le module est de type "Aucun/Voir/Modifier".
   */
  EDIT = "edit",
}

/**
 * Modules (rubriques) de l'application.
 */
export const sitemap: Map<ModuleId, Module> = new Map([
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
            href: "/bois/rdvs",
            devices: ["mobile", "tablet", "desktop"],
          },
          {
            affichage: "Statistiques",
            roleMini: UserRoles.ACCESS,
            href: "/bois/stats",
            devices: ["tablet", "desktop"],
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
            href: "/vrac/rdvs",
            devices: ["mobile", "tablet", "desktop"],
          },
          {
            affichage: "Produits",
            roleMini: UserRoles.EDIT,
            href: "/vrac/produits",
            devices: ["mobile", "tablet", "desktop"],
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
            href: "/consignation/escales",
            devices: ["mobile", "tablet", "desktop"],
          },
          {
            affichage: "Archives",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/escales?archives",
            devices: ["mobile", "tablet", "desktop"],
          },
          {
            affichage: "Tirants d'eau",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/te",
            devices: ["tablet", "desktop"],
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
            href: "/chartering/charters",
            devices: ["mobile", "tablet", "desktop"],
          },
          {
            affichage: "Archives",
            roleMini: UserRoles.ACCESS,
            href: "/chartering/charters?archives",
            devices: ["mobile", "tablet", "desktop"],
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
        href: "/tiers",
        devices: ["mobile", "tablet", "desktop"],
      },
    },
  ],
  [
    "config",
    {
      affichage: "Configuration",
      type: TypesModules.ACCESS,
      tree: {
        href: "/config",
        devices: ["tablet", "desktop"],
      },
    },
  ],
  [
    "admin",
    {
      affichage: "Administration",
      type: TypesModules.ACCESS,
      tree: {
        href: "/admin",
        devices: ["mobile", "tablet", "desktop"],
      },
    },
  ],
]);
