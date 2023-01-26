import { UserRoles } from "./UserRoles";

/**
 * Supprimer les éléments pour lequel l'utilisateur n'a pas les droits.
 *
 * @param {HTMLElement} context Contexte HTML duquel supprimer les éléments.
 */
export function supprimerElementsNonAutorises(context = document) {
  const elements = context.querySelectorAll("[data-rubrique][data-rolemini]");
  const { roles } = JSON.parse(localStorage.getItem("stores/user") || "{}");

  for (const element of elements) {
    const rubrique = element.dataset.rubrique;
    const roleMini = element.dataset.rolemini.toUpperCase() || Infinity;
    const userRole = roles[rubrique] || 0;

    if (userRole < UserRoles[roleMini]) {
      element.remove();
    }
  }
}
