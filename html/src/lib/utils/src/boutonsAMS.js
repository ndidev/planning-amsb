/**
 * Boutons ajouter/modifier/supprimer + tooltip supprimer.
 *
 * Fonction reprise pour chaque page tiers pour afficher/cacher les boutons
 * en fonction du tiers ou RDV passé en paramètre (nouveau tiers / modif tiers)
 *
 * @param {any} tiers_ou_rdv
 */
export function boutonsAMS(tiers_ou_rdv) {
  const BTN_AJOUTER = document.querySelector(".bouton-ajouter");
  const BTN_MODIFIER = document.querySelector(".bouton-modifier");
  const BTN_SUPPRIMER = document.querySelector(".bouton-supprimer");
  const TOOLTIP_SUPPRIMER = document.querySelector(".tooltip-supprimer");

  if (tiers_ou_rdv) {
    BTN_AJOUTER.style.display = "none";
    BTN_MODIFIER.style.display = "inline-block";
    BTN_SUPPRIMER.style.display = "inline-block";
  } else {
    BTN_AJOUTER.style.display = "inline-block";
    BTN_MODIFIER.style.display = "none";
    BTN_SUPPRIMER.style.display = "none";
    if (TOOLTIP_SUPPRIMER) {
      TOOLTIP_SUPPRIMER.textContent = "";
      TOOLTIP_SUPPRIMER.style.visibility = "hidden";
    }
  }
}
