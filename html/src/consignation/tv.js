import env from "../lib/environment";
import appliquerStatutEscale from "./statut_escale";
import { demarrerConnexionSSE } from "../lib/sse";

import { BandeauInfo } from "@app/components";
import { CoteCesson } from "@app/components";

const bandeauInfo = new BandeauInfo({
  target: document.getElementById("bandeau-info"),
  props: {
    module: "consignation",
    tv: true,
  },
});

const coteCesson = new CoteCesson({
  target: document.getElementById("cote-cesson"),
});

raffraichirPlanning();
demarrerConnexionSSE([
  "consignation",
  "tiers",
  "config/bandeau-info",
  "config/cotes",
]);

document.addEventListener("planning:consignation", raffraichirPlanning);
document.addEventListener("planning:tiers", raffraichirPlanning);

/**
 * Raffraîchir escales
 */
async function raffraichirPlanning() {
  try {
    const escales = await recupererEscales();
    if (escales) afficherEscales(escales);
  } catch (error) {
    console.error(error);
  }
}

/**
 * Récupère la liste des escales.
 *
 * @returns {Promise} Liste des escales au format JSON
 */
function recupererEscales() {
  return new Promise(async (resolve, reject) => {
    const url = new URL(env.api);
    url.pathname += "consignation/escales";

    const reponse = await fetch(url, {
      headers: {
        "X-API-Key": new URLSearchParams(location.search).get("api_key"),
      },
    });

    if (reponse.ok) {
      const escales = await reponse.json();
      resolve(escales);
    } else {
      reject(`${reponse.status} : ${reponse.statusText}`);
    }
  });
}

/**
 * Affiche les escales.
 *
 * @param {Object} escales Liste des escales au format JSON
 */
function afficherEscales(escales) {
  const lignes_escales = document.createElement("div");

  /* Récupération des templates */
  const modele_ligne_escale = document.querySelector("template#ligne-escale");
  const modele_ligne_marchandise = document.querySelector(
    "template#ligne-marchandise"
  );
  const modele_ligne_total = document.querySelector("template#ligne-total");

  /* Affichage */
  for (const escale of escales) {
    // Affichage des données de l'escale
    const ligne_escale =
      modele_ligne_escale.content.firstElementChild.cloneNode(true);
    ligne_escale.id = escale.id;
    ligne_escale.querySelector(".navire").textContent = escale.navire;
    ligne_escale.querySelector(".voyage").textContent = escale.voyage
      ? `(escale n°${escale.voyage})`
      : null;
    if (!escale.voyage) ligne_escale.querySelector(".voyage").remove();

    // ETA
    ligne_escale.querySelector(".eta .date").textContent = escale.eta_date
      ? new Date(escale.eta_date).toLocaleDateString()
      : null;
    ligne_escale.querySelector(".eta .heure").textContent = escale.eta_heure;

    // TE
    ligne_escale.querySelector(".te_arrivee").textContent = escale.te_arrivee
      ? escale.te_arrivee.toLocaleString(undefined, {
          minimumFractionDigits: 2,
        }) + " m"
      : null;
    ligne_escale.querySelector(".te_depart").textContent = escale.te_depart
      ? escale.te_depart.toLocaleString(undefined, {
          minimumFractionDigits: 2,
        }) + " m"
      : null;

    // Ports
    ligne_escale.querySelector(".last_port").textContent = escale.last_port_nom;
    ligne_escale.querySelector(".next_port").textContent = escale.next_port_nom;
    ligne_escale.querySelector(".quai").textContent = escale.quai;

    // Marchandises
    let tonnage_total = 0;
    let cubage_total = 0;
    let nombre_total = 0;
    for (const marchandise of escale.marchandises) {
      const ligne_marchandise =
        modele_ligne_marchandise.content.firstElementChild.cloneNode(true);
      ligne_marchandise.querySelector(".marchandise_nom").textContent =
        marchandise.marchandise;
      ligne_marchandise.querySelector(".environ").textContent =
        marchandise.environ ? "~" : null;
      ligne_marchandise.querySelector(".tonnage").textContent =
        marchandise.tonnage_bl
          ? marchandise.tonnage_bl.toLocaleString(undefined, {
              minimumFractionDigits: 3,
            }) + " MT"
          : null;
      ligne_marchandise.querySelector(".cubage").textContent =
        marchandise.cubage_bl
          ? marchandise.cubage_bl.toLocaleString(undefined, {
              minimumFractionDigits: 3,
            }) + " m3"
          : null;
      ligne_marchandise.querySelector(".nombre").textContent =
        marchandise.nombre_bl
          ? marchandise.nombre_bl.toLocaleString() + " colis"
          : null;

      tonnage_total += marchandise.tonnage_bl;
      cubage_total += marchandise.cubage_bl;
      nombre_total += marchandise.nombre_bl;

      for (const div of ligne_marchandise.querySelectorAll("*")) {
        if (!div.innerHTML) div.remove();
      }

      ligne_escale
        .querySelector(".marchandises")
        .appendChild(ligne_marchandise);
    }

    // Ligne total
    if (escale.marchandises.length > 1) {
      const ligne_total =
        modele_ligne_total.content.firstElementChild.cloneNode(true);
      ligne_total.querySelector(".tonnage").textContent = tonnage_total
        ? tonnage_total.toLocaleString(undefined, {
            minimumFractionDigits: 3,
          }) + " MT"
        : null;
      ligne_total.querySelector(".cubage").textContent = cubage_total
        ? cubage_total.toLocaleString(undefined, { minimumFractionDigits: 3 }) +
          " m3"
        : null;
      ligne_total.querySelector(".nombre").textContent = nombre_total
        ? nombre_total.toLocaleString() + " colis"
        : null;

      ligne_escale.querySelector(".marchandises").appendChild(ligne_total);
    }

    appliquerStatutEscale(ligne_escale, escale);

    if (escale.call_port === "Tréguier") {
      ligne_escale.classList.add("treguier");
    }

    lignes_escales.appendChild(ligne_escale);
  }

  const main = document.querySelector("main");
  main.innerHTML = null;
  main.appendChild(lignes_escales);
}
