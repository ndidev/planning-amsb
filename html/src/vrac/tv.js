import { env, demarrerConnexionSSE } from "@app/utils";

import { BandeauInfo } from "@app/components";

const bandeauInfo = new BandeauInfo({
  target: document.getElementById("bandeau-info"),
  props: {
    module: "vrac",
    tv: true,
  },
});

raffraichirPlanning();
demarrerConnexionSSE(["vrac", "tiers", "config/bandeau-info"]);

document.addEventListener("planning:vrac", raffraichirPlanning);
document.addEventListener("planning:tiers", raffraichirPlanning);

/**
 * Raffraichir la liste des RDVs.
 */
async function raffraichirPlanning() {
  try {
    const rdvs = await recupererRdvs();
    if (rdvs) afficherRdvs(rdvs);
  } catch (err) {
    console.error(err);
  }
}

/**
 * Récupère la liste des RDVs via l'API.
 *
 * @returns {Object} Liste des RDVs au format JSON
 */
async function recupererRdvs() {
  const url = new URL(env.api);
  url.pathname += "vrac/rdvs";

  const params = {
    tri: "produit",
    groupe: "date",
  };

  url.search = new URLSearchParams(params).toString();

  try {
    const reponse = await fetch(url, {
      headers: {
        "X-API-Key": new URLSearchParams(location.search).get("api_key"),
      },
    });

    if (!reponse.ok)
      throw new Error(`${reponse.status} : ${reponse.statusText}`);

    const rdvs = await reponse.json();
    return rdvs;
  } catch (err) {
    throw err;
  }
}

/**
 * Affiche les RDVs.
 *
 * @param {Object} rdvs Liste des RDVs au format JSON
 */
function afficherRdvs(rdvs) {
  const planning = document.createElement("div");

  /* Récupération des templates */
  const modele_ligne_rdv = document.querySelector("template#ligne-rdv");
  const modele_ligne_date = document.querySelector("template#ligne-date");

  /* Affichage */
  for (const date in rdvs) {
    // Bloc date qui comprend la date et les RDV du jour
    const bloc_date = document.createElement("div");
    bloc_date.id = date;
    bloc_date.classList.add("bloc-date");

    // Ligne date
    const ligne_date =
      modele_ligne_date.content.firstElementChild.cloneNode(true);

    ligne_date.dataset.date = date;
    ligne_date.querySelector(".date").textContent = new Date(
      date
    ).toLocaleDateString("fr-FR", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });

    bloc_date.appendChild(ligne_date);

    for (const rdv of rdvs[date].rdvs) {
      // Affichage des données du rdv
      const ligne_rdv =
        modele_ligne_rdv.content.firstElementChild.cloneNode(true);
      ligne_rdv.id = rdv.rdv_id;
      ligne_rdv.querySelector(".produit").textContent = rdv.produit_nom;
      ligne_rdv.querySelector(".produit").style.color = rdv.produit_couleur;
      ligne_rdv.querySelector(".qualite").textContent = rdv.qualite_nom;
      ligne_rdv.querySelector(".qualite").style.color = rdv.qualite_couleur;
      ligne_rdv.querySelector(".heure").textContent = rdv.heure;
      ligne_rdv.querySelector(".quantite").textContent = rdv.quantite;
      ligne_rdv.querySelector(".unite").textContent = rdv.unite;
      ligne_rdv.querySelector(".max").textContent = rdv.max == 1 ? "max" : null;
      ligne_rdv.querySelector(".client").textContent =
        rdv.client_nom + " " + rdv.client_ville;
      ligne_rdv.querySelector(".transporteur").textContent =
        rdv.transporteur_nom;
      ligne_rdv.querySelector(".num_commande").textContent = rdv.num_commande;
      ligne_rdv.querySelector(".commentaire").textContent = rdv.commentaire;

      bloc_date.appendChild(ligne_rdv);
    }

    planning.appendChild(bloc_date);
  }

  const main = document.querySelector("main");
  main.innerHTML = null;
  main.appendChild(planning);
}
