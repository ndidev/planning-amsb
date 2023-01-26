import { UserRoles } from "./UserRoles";

/**
 * Vérifie si un utilisateur peut accéder à une fonction.
 *
 * @param {string}        rubrique Rubrique concernée.
 * @param {string|number} roleMini Rôle minimal pour pouvoir accéder à la fonction.
 *
 * @returns {boolean} `true` si l'utilisateur peut accéder à la fonction, `false` sinon.
 */
export function utilisateurEstAutorise(rubrique, roleMini) {
  const { roles } = JSON.parse(localStorage.getItem("user") || "{}");
  const userRole = roles[rubrique] || 0;

  if (typeof roleMini === "string") {
    roleMini = UserRoles[roleMini.toUpperCase()] || Infinity;
  }

  return userRole >= roleMini;
}
