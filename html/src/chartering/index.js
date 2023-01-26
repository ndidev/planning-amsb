import { env, cleanDOM, demarrerConnexionSSE } from "@app/utils";
import { supprimerElementsNonAutorises } from "@app/auth";

import { BandeauInfo } from "@app/components";

const bandeauInfo = new BandeauInfo({
  target: document.getElementById("bandeau-info"),
  props: {
    module: "chartering",
    pc: true,
  },
});

const archives = new URLSearchParams(location.search).has("archives")
  ? "archives"
  : "";

raffraichirPlanning();
demarrerConnexionSSE(["chartering", "tiers", "config/bandeau-info"]);

document.addEventListener("planning:chartering", raffraichirPlanning);
document.addEventListener("planning:tiers", raffraichirPlanning);

/**
 * Raffraichir la liste des affrètements.
 */
export function raffraichirPlanning() {
  recupererCharters().then(afficherCharters);
}

/**
 * Récupère la liste des affrètements.
 *
 * @returns {Promise} Liste des affrètements au format JSON
 */
async function recupererCharters() {
  const url = new URL(env.api);
  url.pathname += "chartering/charters";

  const params = JSON.parse(localStorage.getItem("filtre-planning-chartering"));

  let search = {};
  if (params) search = { ...search, ...params };
  if (archives) search = { ...search, archives: true };

  url.search = new URLSearchParams(search).toString();

  try {
    const reponse = await fetch(url);

    if (!reponse.ok) {
      throw new Error(`${reponse.status} : ${reponse.statusText}`);
    }

    return await reponse.json();
  } catch (err) {
    throw err;
  }
}

/**
 * Affiche les affrètements.
 *
 * @param {Object} charters Liste des affrètements au format JSON
 */
async function afficherCharters(charters) {
  const lignes_charters = document.createElement("div");

  /* Récupération des templates */
  const modele_ligne_charter = document.querySelector("#ligne-charter");
  const modele_ligne_detail = document.querySelector("#ligne-detail");

  /* Affichage */
  for (const charter of charters) {
    // Affichage des données de l'affrètement
    const ligne_charter =
      modele_ligne_charter.content.firstElementChild.cloneNode(true);
    ligne_charter.id = charter.id;
    ligne_charter.querySelector(".navire").textContent = charter.navire;
    ligne_charter.querySelector(".armateur").textContent = charter.armateur_nom;
    ligne_charter.querySelector(".affreteur").textContent =
      charter.affreteur_nom;
    ligne_charter.querySelector(".courtier").textContent = charter.courtier_nom;

    ligne_charter.querySelector(".lc_debut").textContent = charter.lc_debut
      ? new Date(charter.lc_debut).toLocaleDateString()
      : null;
    ligne_charter.querySelector(".lc_fin").textContent = charter.lc_fin
      ? new Date(charter.lc_fin).toLocaleDateString()
      : null;
    ligne_charter.querySelector(".cp").textContent = charter.cp_date
      ? new Date(charter.cp_date).toLocaleDateString()
      : null;

    ligne_charter.querySelector(".commentaire").innerHTML =
      charter.commentaire.replace(/\r\n|\r|\n/g, "<br>");

    if (!charter.commentaire) {
      ligne_charter.querySelector(".commentaire").remove();
    }

    // Détails
    for (const detail of charter.details) {
      const ligne_detail =
        modele_ligne_detail.content.firstElementChild.cloneNode(true);
      ligne_detail.querySelector(".marchandise").textContent =
        detail.marchandise;
      ligne_detail.querySelector(".quantite").textContent = detail.quantite;
      ligne_detail.querySelector(
        ".ports"
      ).textContent = `${detail.pol_nom} > ${detail.pod_nom}`;
      ligne_detail.querySelector(".commentaire").innerHTML =
        detail.commentaire.replace(/\r\n|\r|\n/g, "<br>");

      for (const div of ligne_detail.querySelectorAll("*")) {
        if (!div.innerHTML) div.remove();
      }

      ligne_charter.querySelector(".legs").appendChild(ligne_detail);
    }

    ligne_charter.querySelectorAll(".copie-modif-suppr a")[0].href +=
      `?id=${charter.id}` + (archives ? "&archives" : "");

    if (!archives) {
      appliquerStatutCharter(ligne_charter, charter.statut);
    }

    lignes_charters.appendChild(ligne_charter);
  }

  supprimerElementsNonAutorises(lignes_charters);
  cleanDOM(lignes_charters);

  const main = document.querySelector("main");
  main.innerHTML = null;
  main.appendChild(lignes_charters);
}

/**
 * Assignation d'une classe à chaque affrètement
 * en fonction de son statut
 * afin de mettre en forme le planning
 *
 * @param {Node}   ligne_charter Ligne affrètement sur le planning
 * @param {string} statut        Statut de l'affrètement
 */
function appliquerStatutCharter(ligne_charter, statut) {
  switch (statut) {
    case 0:
      statut = "plannifie";
      break;

    case 1:
      statut = "confirme";
      break;

    case 2:
      statut = "affrete";
      break;

    case 3:
      statut = "charge";
      break;

    case 4:
      statut = "termine";
      break;

    default:
      statut = "";
      break;
  }

  if (statut) ligne_charter.classList.add(statut);
}
