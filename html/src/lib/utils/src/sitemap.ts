import { UserRoles, TypesModules } from "@app/auth";
import type { ModuleId, Module } from "@app/types";

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
            devices: ["mobile", "desktop"],
          },
          {
            affichage: "Statistiques",
            roleMini: UserRoles.ACCESS,
            href: "/bois/stats",
            devices: ["desktop"],
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
            devices: ["mobile", "desktop"],
          },
          {
            affichage: "Produits",
            roleMini: UserRoles.EDIT,
            href: "/vrac/produits",
            devices: ["mobile", "desktop"],
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
            devices: ["mobile", "desktop"],
          },
          {
            affichage: "Archives",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/escales?archives",
            devices: ["mobile", "desktop"],
          },
          {
            affichage: "Tirants d'eau",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/te",
            devices: ["desktop"],
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
            devices: ["mobile", "desktop"],
          },
          {
            affichage: "Archives",
            roleMini: UserRoles.ACCESS,
            href: "/chartering/charters?archives",
            devices: ["mobile", "desktop"],
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
        devices: ["mobile", "desktop"],
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
        devices: ["desktop"],
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
        devices: ["mobile", "desktop"],
      },
    },
  ],
]);
