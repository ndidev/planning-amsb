import Notiflix from "notiflix";

/**
 * Valider un formulaire.
 *
 * Renvoie `true` si formulaire valide, `false` sinon.
 * Affiche un message sur les champs invalides.
 *
 * @param context Conteneur des champs à vérifier
 */
export function validerFormulaire(context: HTMLElement) {
  const inputs = context.querySelectorAll<HTMLInputElement | HTMLSelectElement>(
    "input, select"
  );

  const champsInvalides = [];

  let valide = true;

  for (const input of inputs) {
    if (!input.checkValidity()) {
      valide = false;
      if (input.dataset.nom || input.name) {
        champsInvalides.push(input.dataset.nom || input.name);
      }
    }
  }

  if (!valide) {
    Notiflix.Notify.failure(
      "Certains champs sont invalides : " + champsInvalides.join(", ")
    );
  }

  return valide;
}
