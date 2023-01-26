import env from "../lib/environment";
import "../lib/typedefs";
import { get } from "svelte/store";
import { rdvRapides } from "@app/stores";

/**
 * RDV rapides
 */
(async () => {
  // Rdvs
  const urlRdvs = new URL(env.api);
  urlRdvs.pathname += "config/rdvrapides";
  const reponseRdvs = await fetch(urlRdvs);
  let rdvs = await reponseRdvs.json();
  rdvs = rdvs.filter((rdv) => rdv.module === "bois");

  if (rdvs.length === 0) return;

  // Tiers
  const urlTiers = new URL(env.api);
  urlTiers.pathname += "tiers";
  const reponseTiers = await fetch(urlTiers);
  /** @type {Tiers[]} */
  const listeTiers = await reponseTiers.json();
  /** @type {Map<number, Tiers>} */
  const mapTiers = new Map();
  for (const tiers of listeTiers) {
    mapTiers.set(tiers.id, tiers);
  }

  const rdvsBois = {};

  /**
   * Trier un tableau de RDVs rapides par nom de tiers.
   * @param {string} role Rôle du tiers servant au tri.
   * @param {RdvRapideBois} a
   * @param {RdvRapideBois} b
   */
  function trierParNom(role, a, b) {
    return mapTiers.get(a[role]).nom_court < mapTiers.get(b[role]).nom_court
      ? -1
      : 1;
  }

  rdvs
    .sort((a, b) => trierParNom("client", a, b))
    .forEach((rdv) => {
      // Peuplement de l'objet rdvsBois
      if (!rdvsBois[rdv.client]) {
        rdvsBois[rdv.client] = [];
      }
      rdvsBois[rdv.client].push(rdv);
    });

  // Pour chaque client, tri suivant le nom du transporteur
  for (const [client, rdvs] of Object.entries(rdvsBois)) {
    rdvs.sort((a, b) => trierParNom("transporteur", a, b));
  }

  const clients = new Set(Object.keys(rdvsBois));

  let menu = "";

  menu += "<ul>"; // Ouverture menu

  for (const client of clients) {
    // RDV générique (sans transporteur)
    const rdv = rdvsBois[client][0];

    menu +=
      `<li class='menu-rapide'>` +
      `<a data-client='${rdv.client}'` +
      `data-chargement='${rdv.chargement}'` +
      `data-livraison='${rdv.livraison}'` +
      `data-fournisseur='${rdv.fournisseur}'` +
      `data-transporteur=''` +
      `data-affreteur='${rdv.affreteur}'>${
        mapTiers.get(rdv.client).nom_court
      }</a>`;
    menu += "<ul>";

    for (const rdv of rdvsBois[client]) {
      // Transporteurs
      menu +=
        `<li class='menu-rapide'>` +
        `<a data-client='${rdv.client}'` +
        `data-chargement='${rdv.chargement}'` +
        `data-livraison='${rdv.livraison}'` +
        `data-fournisseur='${rdv.fournisseur}'` +
        `data-transporteur='${rdv.transporteur}'` +
        `data-affreteur='${rdv.affreteur}'>${
          mapTiers.get(rdv.transporteur).nom_court
        }</a></li>`;
    }

    // Clôture client
    menu += "</ul></li>";
  }

  // Clôture menu
  menu += "</ul>";

  document.getElementById("menu-add").innerHTML += menu;

  for (const rdvRapide of document.querySelectorAll(".menu-rapide > a")) {
    rdvRapide.addEventListener("click", async (e) => {
      const url = new URL(env.api);
      url.pathname += "bois/rdvs";

      const params = {
        fournisseur: e.target.dataset.fournisseur,
        client: e.target.dataset.client,
        chargement: e.target.dataset.chargement,
        livraison: e.target.dataset.livraison,
        affreteur: e.target.dataset.affreteur,
        transporteur: e.target.dataset.transporteur,
      };

      try {
        const reponse = await fetch(url, {
          method: "POST",
          body: JSON.stringify(params),
        });

        if (!reponse.ok) {
          throw new Error(`${reponse.status} : ${reponse.statusText}`);
        }

        // Ne raffraîchir le planning que si l'on est sur la page du planning
        // sinon, afficher le planning
        if (/\/bois\/?(index\.html)?$/.test(location.pathname)) {
          const { raffraichirPlanning } = await import("../bois/index.js");
          raffraichirPlanning();
        } else {
          location.href = "./";
        }
      } catch (err) {
        Notiflix.Notify.failure(err.message);
      }
    });
  }
})();
