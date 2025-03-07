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
          {
            affichage: "Dispatch",
            roleMini: UserRoles.ACCESS,
            href: "/vrac/dispatch",
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
            affichage: "Tirants d'eau",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/te",
            devices: ["desktop"],
          },
          {
            affichage: "Statistiques",
            roleMini: UserRoles.ACCESS,
            href: "/consignation/stats",
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
        ],
      },
    },
  ],
  [
    "manutention",
    {
      affichage: "Manutention",
      type: TypesModules.EDIT,
      tree: {
        children: [
          {
            affichage: "Rapports navires",
            roleMini: UserRoles.ACCESS,
            href: "/manutention/rapports-navires",
            devices: ["desktop", "mobile"],
          },
          {
            affichage: "Équipements",
            roleMini: UserRoles.ACCESS,
            href: "/manutention/equipements",
            devices: ["mobile", "desktop"],
          },
          {
            affichage: "Activités hors navires",
            roleMini: UserRoles.ACCESS,
            href: "/manutention/activites-hors-navires",
            devices: ["desktop", "mobile"],
          },
          {
            affichage: "Heures intérimaires",
            roleMini: UserRoles.ACCESS,
            href: "/manutention/heures-interimaires",
            devices: ["desktop", "mobile"],
          },
        ],
      },
    },
  ],
  [
    "personnel",
    {
      affichage: "Personnel de manutention",
      type: TypesModules.ACCESS,
      tree: {
        href: "/personnel",
        roleMini: UserRoles.ACCESS,
        devices: ["mobile", "desktop"],
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
        roleMini: UserRoles.ACCESS,
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
        roleMini: UserRoles.ACCESS,
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
        roleMini: UserRoles.ACCESS,
        devices: ["mobile", "desktop"],
      },
    },
  ],
]);
