import Notiflix from "notiflix";

import env from "../lib/environment";
import boutonsAMS from "../lib/boutonsAMS";
import { awesompletePorts } from "../lib/awesompletePorts";
import { awesompleteTiers } from "../lib/awesompleteTiers";
import { formatDecimal } from "../lib/formatDecimal";

const archives = new URLSearchParams(location.search).has("archives")
  ? "?archives"
  : "";

/**
 * Récupération + affichage des informations de l'affrètement
 */
(async () => {
  const id = new URLSearchParams(location.search).get("id");

  if (!id) return; // Nouvel affrètement

  const url = new URL(env.api);
  url.pathname += `chartering/charters/${id}`;

  const reponse = await fetch(url);

  if (!reponse.ok) return;

  const charter = await reponse.json();

  // Statut
  document.getElementById("statut").value = charter.statut;
  document.getElementById("archive").checked = charter.archive;

  // Navire
  document.getElementById("navire").value = charter.navire;

  // Dates
  document.getElementById("lc_debut").value = charter.lc_debut;
  document.getElementById("lc_fin").value = charter.lc_fin;
  document.getElementById("cp_date").value = charter.cp_date;

  // Tiers
  document.getElementById("affreteur_user").value = charter.affreteur_nom;
  document.getElementById("affreteur_value").value = charter.affreteur;
  document.getElementById("armateur_user").value = charter.armateur_nom;
  document.getElementById("armateur_value").value = charter.armateur;
  document.getElementById("courtier_user").value = charter.courtier_nom;
  document.getElementById("courtier_value").value = charter.courtier;

  // Montants
  document.getElementById("fret_achat").value =
    charter.fret_achat?.toFixed(2) || null;
  document.getElementById("fret_vente").value =
    charter.fret_vente?.toFixed(2) || null;
  document.getElementById("surestaries_achat").value =
    charter.surestaries_achat?.toFixed(2) || null;
  document.getElementById("surestaries_vente").value =
    charter.surestaries_vente?.toFixed(2) || null;

  document.getElementById("commentaire").textContent = charter.commentaire;

  // Détails
  const modele_ligne_detail = document.querySelector("template#ligne-detail");

  for (const detail of charter.details) {
    const ligne_detail =
      modele_ligne_detail.content.firstElementChild.cloneNode(true);

    ligne_detail.id = detail.id;
    ligne_detail.querySelector(".marchandise").value = detail.marchandise;
    ligne_detail.querySelector(".quantite").value = detail.quantite;
    ligne_detail.querySelector(".bl_date").value = detail.bl_date;

    ligne_detail.querySelector(".pol_user").value = detail.pol_nom;
    ligne_detail.querySelector(".pol_value").value = detail.pol;
    ligne_detail.querySelector(".pod_user").value = detail.pod_nom;
    ligne_detail.querySelector(".pod_value").value = detail.pod;

    awesompletePorts(".pol", ligne_detail);
    awesompletePorts(".pod", ligne_detail);

    ligne_detail.querySelector(".commentaire").value = detail.commentaire;

    ligne_detail.querySelector(".poubelle").onclick = () => {
      ligne_detail.remove();
    };

    document.getElementById("details").appendChild(ligne_detail);
  }

  formatDecimal();
})();

/**
 * Ajout ligne détail
 */
(() => {
  const modele_ligne_detail = document.querySelector("template#ligne-detail");

  document.getElementById("ajouter-ligne").onclick = () => {
    const ligne_detail =
      modele_ligne_detail.content.firstElementChild.cloneNode(true);

    ligne_detail.querySelector(".poubelle").onclick = () => {
      ligne_detail.remove();
    };

    awesompletePorts(".pol", ligne_detail);
    awesompletePorts(".pod", ligne_detail);

    document.getElementById("details").appendChild(ligne_detail);
  };
})();

/**
 * Awesomplete tiers
 */
awesompleteTiers("#armateur", { role: "maritime_armateur" });
awesompleteTiers("#affreteur", { role: "maritime_affreteur" });
awesompleteTiers("#courtier", { role: "maritime_courtier" });

/**
 * Nom du navire en lettres capitales
 */
document.getElementById("navire").oninput = (e) => {
  e.target.value = e.target.value.toUpperCase();
};

/**
 * Boutons dynamiques création/modification affrètement.
 * Récupération des parties "id=" et "copie=" de l'URL
 *  - si "id=" n'existe pas (nouvel affrètement), alors bouton "Ajouter"
 *  - si "id=" existe, alors boutons "Modifier" et "Supprimer"
 */
(() => {
  const query = new URLSearchParams(location.search);
  boutonsAMS(query.has("id"));
})();

/**
 * Validation formulaire
 */
function validerFormulaire() {
  const formulaire = document.querySelector("form");
  const inputs = formulaire.querySelectorAll("input, select");
  const champs_invalides = [];

  let valide = true;

  for (const input of inputs) {
    if (!input.checkValidity()) {
      valide = false;
      if (input.dataset.nom) {
        champs_invalides.push(input.dataset.nom);
      }
    }
  }

  if (!valide) {
    Notiflix.Notify.failure(
      "Certains champs du formulaire sont invalides : " +
        champs_invalides.join(", ")
    );
  }
  return valide;
}

/**
 * Parse les données du formulaire pour les envoyer via ```fetch()```.
 *
 * @returns {String} Données formulaire *JSON.stringified*
 */
function formatterDonneesFormulaire() {
  const formData = {
    id: new URLSearchParams(window.location.search).get("id") || null,
    // Statut
    statut: document.getElementById("statut").value,
    archive: document.getElementById("archive").checked ? 1 : 0,
    // Navire
    navire: document.getElementById("navire").value || "TBN",
    // Dates
    lc_debut: document.getElementById("lc_debut").value || null,
    lc_fin: document.getElementById("lc_fin").value || null,
    cp_date: document.getElementById("cp_date").value || null,
    // Tiers
    affreteur: document.getElementById("affreteur_value").value || null,
    armateur: document.getElementById("armateur_value").value || null,
    courtier: document.getElementById("courtier_value").value || null,
    // Montants
    fret_achat: document.getElementById("fret_achat").value || null,
    fret_vente: document.getElementById("fret_vente").value || null,
    surestaries_achat:
      document.getElementById("surestaries_achat").value || null,
    surestaries_vente:
      document.getElementById("surestaries_vente").value || null,
    // Divers
    commentaire: document.getElementById("commentaire").value,
    details: [],
  };

  document.querySelectorAll(".ligne-detail").forEach((ligne) => {
    const detail = {
      id: ligne.id,
      charter: new URLSearchParams(window.location.search).get("id") || null,
      marchandise: ligne.querySelector(".marchandise").value,
      quantite: ligne.querySelector(".quantite").value,
      bl_date: ligne.querySelector(".bl_date").value || null,
      pol: ligne.querySelector(".pol_value").value,
      pod: ligne.querySelector(".pod_value").value,
      commentaire: ligne.querySelector(".commentaire").value,
    };

    formData.details.push(detail);
  });

  return JSON.stringify(formData);
}

/**
 * Empêcher la création d'un affrètement en appuyant sur la touche "Entrée"
 */
document.querySelector("form").addEventListener("keydown", (e) => {
  if (
    e.key === "Enter" &&
    document.activeElement !== document.getElementById("commentaire")
  ) {
    e.preventDefault();
  }
});

/**
 * Nouvel affrètement
 */
document.querySelector(".bouton-ajouter").onclick = async (e) => {
  e.preventDefault();

  if (!validerFormulaire()) return;

  e.target.setAttribute("disabled", true);

  const formData = formatterDonneesFormulaire();

  const url = new URL(env.api);
  url.pathname += `chartering/charters`;

  try {
    const reponse = await fetch(url, {
      method: "POST",
      body: formData,
    });

    if (!reponse.ok) {
      throw Error(`${reponse.status}, ${reponse.statusText}`);
    }

    location = "./";
  } catch (erreur) {
    // Notification erreur
    Notiflix.Notify.failure(erreur.message);
  }

  e.target.removeAttribute("disabled");
};

/**
 * Modification affrètement
 */
document.querySelector(".bouton-modifier").onclick = async (e) => {
  e.preventDefault();

  if (!validerFormulaire()) return;

  e.target.setAttribute("disabled", true);

  const formData = formatterDonneesFormulaire();

  const id = new URLSearchParams(location.search).get("id");

  const url = new URL(env.api);
  url.pathname += `chartering/charters/${id}`;

  try {
    const reponse = await fetch(url, {
      method: "PUT",
      body: formData,
    });

    if (!reponse.ok) {
      throw Error(`${reponse.status}, ${reponse.statusText}`);
    }

    location = "./" + archives;
    // Notiflix.Notify.success("L'affrètement a été modifié");
  } catch (erreur) {
    // Notification erreur
    Notiflix.Notify.failure(erreur.message);
  }

  e.target.removeAttribute("disabled");
};

/**
 * Suppression affrètement
 */
document.querySelector(".bouton-supprimer").onclick = (e) => {
  e.preventDefault();

  const id = new URLSearchParams(window.location.search).get("id");

  if (!id) return;

  e.target.setAttribute("disabled", true);

  Notiflix.Confirm.merge({
    titleColor: "#ff5549",
    okButtonColor: "#f8f8f8",
    okButtonBackground: "#ff5549",
    cancelButtonColor: "#f8f8f8",
    cancelButtonBackground: "#a9a9a9",
  });

  // Demande de confirmation
  Notiflix.Confirm.show(
    "Suppression affrètement",
    `Voulez-vous vraiment supprimer l'affrètement ?`,
    "Supprimer",
    "Annuler",
    async function () {
      const url = new URL(env.api);
      url.pathname += `chartering/charters/${id}`;

      try {
        const reponse = await fetch(url, {
          method: "DELETE",
        });

        if (!reponse.ok)
          throw new Error(`${reponse.status}, ${reponse.statusText}`);

        location = "./" + archives;
      } catch (erreur) {
        // Notification erreur
        Notiflix.Notify.failure(erreur.message);
      }

      e.target.removeAttribute("disabled");
    },
    function () {
      e.target.removeAttribute("disabled");
    }
  );
};

/**
 * Bouton Annuler
 */
document.querySelector(".bouton-annuler").onclick = (e) => {
  e.preventDefault();
  location = "./" + archives;
};
