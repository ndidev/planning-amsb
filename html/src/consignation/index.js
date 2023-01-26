import { env, cleanDOM, demarrerConnexionSSE } from "@app/utils";
import { supprimerElementsNonAutorises } from "@app/auth";
import appliquerStatutEscale from "./statut_escale";

import { BandeauInfo } from "@app/components";
import { CoteCesson } from "@app/components";

const bandeauInfo = new BandeauInfo({
  target: document.getElementById("bandeau-info"),
  props: {
    module: "consignation",
    pc: true,
  },
});

const coteCesson = new CoteCesson({
  target: document.getElementById("cote-cesson"),
});

const archives = new URLSearchParams(location.search).has("archives")
  ? "archives"
  : "";

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
 * Raffraichir la liste des escales.
 */
function raffraichirPlanning() {
  recupererEscales()
    .then(afficherEscales)
    .catch((err) => console.error(err));
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
    url.search = archives;

    const reponse = await fetch(url);

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
async function afficherEscales(escales) {
  const lignes_escales = document.createElement("div");

  /* Récupération des templates */
  const modele_ligne_escale = document.querySelector("#ligne-escale");
  const modele_ligne_marchandise = document.querySelector("#ligne-marchandise");
  const modele_ligne_total = document.querySelector("#ligne-total");

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
    ligne_escale.querySelector(".armateur").textContent = escale.armateur_nom;

    // ETX
    ligne_escale.querySelector(".eta .date").textContent = escale.eta_date
      ? new Date(escale.eta_date).toLocaleDateString()
      : null;
    ligne_escale.querySelector(".eta .heure").textContent = escale.eta_heure;

    ligne_escale.querySelector(".etb .date").textContent = escale.etb_date
      ? new Date(escale.etb_date).toLocaleDateString()
      : null;
    ligne_escale.querySelector(".etb .heure").textContent = escale.etb_heure;

    ligne_escale.querySelector(".ops .date").textContent = escale.ops_date
      ? new Date(escale.ops_date).toLocaleDateString()
      : null;
    ligne_escale.querySelector(".ops .heure").textContent = escale.ops_heure;

    ligne_escale.querySelector(".etc .date").textContent = escale.etc_date
      ? new Date(escale.etc_date).toLocaleDateString()
      : null;
    ligne_escale.querySelector(".etc .heure").textContent = escale.etc_heure;

    ligne_escale.querySelector(".etd .date").textContent = escale.etd_date
      ? new Date(escale.etd_date).toLocaleDateString()
      : null;
    ligne_escale.querySelector(".etd .heure").textContent = escale.etd_heure;

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
    ligne_escale.querySelector(".call_port").textContent = escale.call_port;
    ligne_escale.querySelector(".quai").textContent = escale.quai;

    // Commentaire
    ligne_escale.querySelector(".commentaire").innerHTML =
      escale.commentaire.replace(/\r\n|\r|\n/g, "<br>");
    if (!escale.commentaire) {
      ligne_escale.querySelector(".commentaire").remove();
    }

    // Marchandises
    let tonnage_total = 0;
    let cubage_total = 0;
    let nombre_total = 0;
    for (const marchandise of escale.marchandises) {
      const ligne_marchandise =
        modele_ligne_marchandise.content.firstElementChild.cloneNode(true);
      ligne_marchandise.querySelector(".marchandise_nom").textContent =
        marchandise.marchandise;
      ligne_marchandise.querySelector(".client").textContent =
        marchandise.client;
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

    // Boutons modifications
    ligne_escale.querySelectorAll(".copie-modif-suppr a")[0].href +=
      `?id=${escale.id}` + (archives ? "&archives" : "");

    if (escale.call_port === "Tréguier") {
      ligne_escale.classList.add("treguier");
    }

    if (!archives) {
      appliquerStatutEscale(ligne_escale, escale);
    }

    lignes_escales.appendChild(ligne_escale);
  }

  // Effacement des liens/boutons pour lequel l'utilisateur n'est pas autorisé
  supprimerElementsNonAutorises(lignes_escales);

  cleanDOM(lignes_escales);

  const main = document.querySelector("main");
  main.innerHTML = null;
  main.appendChild(lignes_escales);
}
