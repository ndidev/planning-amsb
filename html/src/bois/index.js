import Notiflix from "notiflix";

import { env, cleanDOM, demarrerConnexionSSE } from "@app/utils";
import {
  supprimerElementsNonAutorises,
  utilisateurEstAutorise,
} from "@app/auth";

import { BandeauInfo } from "@app/components";

const bandeauInfo = new BandeauInfo({
  target: document.getElementById("bandeau-info"),
  props: {
    module: "bois",
    pc: true,
  },
});

afficherPlaceholders();
raffraichirPlanning();
demarrerConnexionSSE(["bois", "tiers", "config/bandeau-info"]);

document.addEventListener("planning:bois", raffraichirPlanning);
document.addEventListener("planning:tiers", raffraichirPlanning);

/**
 * Raffraichir la liste des RDVs.
 */
export async function raffraichirPlanning() {
  try {
    const rdvs = await recupererRdvs();
    if (rdvs) afficherRdvs(rdvs);
  } catch (err) {
    console.error(err);
  }
}

/**
 * Récupère la liste des RDVs.
 *
 * @returns {Promise} Liste des RDVs au format JSON
 */
async function recupererRdvs() {
  const url = new URL(env.api);
  url.pathname += "bois/rdvs";

  const params = JSON.parse(localStorage.getItem("filtre-planning-bois"));

  if (params) url.search = new URLSearchParams(params).toString();

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
 * Construit un object RDV à partir des données JSON.
 *
 * @param {Object} rdv Données du RDV au format JSON
 */
class RDV {
  constructor(rdv) {
    Object.assign(this, rdv);
  }

  get statut() {
    if (this.heure_depart) return "parti";
    if (this.heure_arrivee) return "arrive";
    return "attendu";
  }

  get ligne_chargement() {
    if (!this.chargement.id) return null;

    const ligne = [];

    // Nom
    ligne.push(this.chargement.nom_court);
    // Département
    ligne.push(
      this.chargement.pays?.toLowerCase() === "france"
        ? this.chargement.cp?.substring(0, 2)
        : ""
    );
    // Ville
    ligne.push(this.chargement.ville);
    // Pays
    ligne.push(
      this.chargement.pays?.toLowerCase() === "france"
        ? ""
        : `(${this.chargement.pays})`
    );

    return ligne.filter((champ) => champ).join(" ");
  }

  get ligne_client() {
    const ligne = [];

    // Nom
    ligne.push(this.client.nom_court);
    // Département
    ligne.push(
      this.client.pays?.toLowerCase() === "france"
        ? this.client.cp?.substring(0, 2)
        : ""
    );
    // Ville
    ligne.push(this.client.ville);
    // Pays
    ligne.push(
      this.client.pays?.toLowerCase() === "france"
        ? ""
        : `(${this.client.pays})`
    );

    return ligne.filter((champ) => champ).join(" ");
  }

  get ligne_livraison() {
    if (!this.livraison.id) return null;

    const ligne = [];

    // Nom
    ligne.push(this.livraison.nom_court);
    // Département
    ligne.push(
      this.livraison.pays?.toLowerCase() === "france"
        ? this.livraison.cp?.substring(0, 2)
        : ""
    );
    // Ville
    ligne.push(this.livraison.ville);
    // Pays
    ligne.push(
      this.livraison.pays?.toLowerCase() === "france"
        ? ""
        : `(${this.livraison.pays})`
    );

    return ligne.filter((champ) => champ).join(" ");
  }

  get adresse() {
    if (!this.livraison.id) {
      return "Pas de lieu de livraison renseigné";
    }

    const adresse_complete = [];

    adresse_complete.push(this.livraison.nom_complet);
    adresse_complete.push(this.livraison.adresse_ligne_1);
    adresse_complete.push(this.livraison.adresse_ligne_2);
    adresse_complete.push(
      [this.livraison.cp || "", this.livraison.ville || ""]
        .filter((champ) => champ)
        .join(" ")
    );
    adresse_complete.push(this.livraison.pays);
    adresse_complete.push(this.livraison.telephone);
    adresse_complete.push(this.livraison.commentaire);

    return adresse_complete.filter((champ) => champ).join("\n");
  }
}

/**
 * Affiche les placeholders avant le chargement des RDVs.
 */
function afficherPlaceholders() {
  const main = document.querySelector("main");
  main.innerHTML = null;

  /* Récupération des templates */
  const modele_placeholder_date = document.querySelector("#placeholder-date");
  const modele_placeholder_rdv = document.querySelector("#placeholder-rdv");

  /* Placeholders */

  for (let i = 5; i--; ) {
    main.appendChild(
      modele_placeholder_date.content.firstElementChild.cloneNode(true)
    );
    for (let j = 3; j--; ) {
      main.appendChild(
        modele_placeholder_rdv.content.firstElementChild.cloneNode(true)
      );
    }
  }
}

/**
 * Affiche les RDVs.
 *
 * @param {Object} rdvs Liste des RDVs au format JSON
 */
function afficherRdvs(rdvs) {
  const main = document.querySelector("main");
  main.innerHTML = null;

  /* Récupération des templates */
  const modele_ligne_date_non_attente = document.querySelector(
    "template#ligne-date-non-attente"
  );
  const modele_ligne_rdv_non_attente = document.querySelector(
    "template#ligne-rdv-non-attente"
  );
  const modele_ligne_date_attente = document.querySelector(
    "template#ligne-date-attente"
  );
  const modele_ligne_rdv_attente = document.querySelector(
    "template#ligne-rdv-attente"
  );

  const filtre = JSON.parse(localStorage.getItem("filtre-bois")) ? true : false;

  /* Affichage */
  /**
   * RDVs pas en attente
   */
  const non_attente = document.createElement("div");

  for (const date in rdvs.non_attente) {
    // Bloc date qui comprend la lige de date et les RDV du jour
    const bloc_date = document.createElement("div");
    bloc_date.id = date;
    bloc_date.classList.add("bloc-date");

    // Affichage de la date
    const ligne_date =
      modele_ligne_date_non_attente.content.firstElementChild.cloneNode(true);

    ligne_date.dataset.date = date;
    ligne_date.querySelector(".date").textContent = new Date(
      date
    ).toLocaleDateString("fr-FR", {
      weekday: "long",
      day: "numeric",
      month: "long",
      year: "numeric",
    });
    bloc_date.appendChild(ligne_date);

    // Affichage des camions
    ligne_date.querySelector(".total .camions").textContent =
      rdvs.non_attente[date].stats.total +
      (filtre ? ` (${rdvs.non_attente[date].stats.total_filtre})` : "");
    ligne_date.querySelector(".attendus .camions").textContent =
      rdvs.non_attente[date].stats.attendus +
      (filtre ? ` (${rdvs.non_attente[date].stats.attendus_filtre})` : "");
    ligne_date.querySelector(".sur_parc .camions").textContent =
      rdvs.non_attente[date].stats.sur_parc +
      (filtre ? ` (${rdvs.non_attente[date].stats.sur_parc_filtre})` : "");
    ligne_date.querySelector(".charges .camions").textContent =
      rdvs.non_attente[date].stats.charges +
      (filtre ? ` (${rdvs.non_attente[date].stats.charges_filtre})` : "");

    for (let rdv of rdvs.non_attente[date].rdvs) {
      rdv = new RDV(rdv);

      // Affichage des données du rdv
      const ligne_rdv =
        modele_ligne_rdv_non_attente.content.firstElementChild.cloneNode(true);

      ligne_rdv.id = `_${rdv.id}`;
      ligne_rdv.classList.add(rdv.statut);

      // Horloges
      // Arrivée
      ligne_rdv.querySelector(".heure-arrivee").textContent = rdv.heure_arrivee;
      ligne_rdv.querySelector(".horloge-arrivee").onclick = () =>
        renseignerHeureArrivee(rdv);
      ligne_rdv.querySelector(".horloge-arrivee").ontouchstart = () =>
        renseignerHeureArrivee(rdv);
      // Départ
      ligne_rdv.querySelector(".heure-depart").textContent = rdv.heure_depart;
      ligne_rdv.querySelector(".horloge-depart").onclick = () =>
        renseignerHeureDepart(rdv);
      ligne_rdv.querySelector(".horloge-depart").ontouchstart = () =>
        renseignerHeureDepart(rdv);

      // Chargement, client, livraison, transporteur, affreteur, fournisseur
      ligne_rdv.querySelector(".chargement .adresse").textContent =
        rdv.ligne_chargement;
      if (!rdv.chargement.id || rdv.chargement.id === 1) {
        ligne_rdv.querySelector(".chargement").remove();
      }

      ligne_rdv.querySelector(".client .adresse").textContent =
        rdv.ligne_client;
      if (rdv.client.id === rdv.livraison.id && rdv.chargement.id === 1) {
        ligne_rdv.querySelector(".client .role").remove();
      }

      ligne_rdv.querySelector(".livraison .adresse").textContent =
        rdv.ligne_livraison;
      if (
        !rdv.livraison.id ||
        (rdv.livraison.id && rdv.client.id === rdv.livraison.id)
      ) {
        ligne_rdv.querySelector(".livraison").remove();
      }

      ligne_rdv.querySelector(".tooltip-livraison").textContent = rdv.adresse;

      ligne_rdv.querySelector(".transporteur-nom").textContent =
        rdv.transporteur.nom;
      ligne_rdv.querySelector(".tooltip-transporteur").textContent =
        rdv.transporteur.telephone || "Téléphone non renseigné";
      if (rdv.transporteur.id < 11) {
        ligne_rdv.querySelector(".tooltip-transporteur").remove();
      }

      ligne_rdv.querySelector(".affreteur").textContent =
        rdv.affreteur.nom || "À affréter";
      ligne_rdv.querySelector(".affreteur").dataset.lie_agence =
        rdv.affreteur.lie_agence;

      ligne_rdv.querySelector(".fournisseur").textContent = rdv.fournisseur.nom;

      // Confirmation d'affrètement
      const conf_affr = ligne_rdv.querySelector(".confirmation_affretement");
      conf_affr.dataset.affreteur_agence = rdv.affreteur.lie_agence;
      conf_affr.dataset.confirme = rdv.confirmation_affretement;
      conf_affr.addEventListener("click", async (e) => {
        if (!utilisateurEstAutorise("bois", "edit")) return;

        // Coche case en cliquant sur zone "confirmation_affretement"
        const url = new URL(env.api);
        url.pathname += `bois/rdvs/${rdv.id}/confirmation_affretement`;

        const params = {
          envoye: Math.abs(conf_affr.dataset.confirme - 1), // Si état actuel = 0, envoie 1, sinon inverse
        };

        try {
          const reponse = await fetch(url, {
            method: "PATCH",
            body: JSON.stringify(params),
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status} : ${reponse.statusText}`);
          }

          conf_affr.dataset.confirme = params.envoye;
        } catch (err) {
          Notiflix.Notify.failure(err.message);
        }
      });

      // Numéro BL
      /** @type {HTMLDivElement} */
      const numero_bl = ligne_rdv.querySelector(".numero_bl");
      if (!utilisateurEstAutorise("bois", "edit")) {
        numero_bl.removeAttribute("contenteditable");
      }
      numero_bl.textContent = rdv.numero_bl;
      numero_bl.dataset.initial = numero_bl.textContent;
      numero_bl.onkeydown = (e) => {
        if (e.key === "Enter") numero_bl.blur();
      };
      numero_bl.onblur = async () => {
        numero_bl.textContent = numero_bl.textContent.trim();

        if (numero_bl.textContent === numero_bl.dataset.initial) return;

        const url = new URL(env.api);
        url.pathname += `bois/rdvs/${rdv.id}/numero_bl`;

        const params = {
          numero_bl: numero_bl.textContent,
          fournisseur: rdv.fournisseur.id,
          fournisseur_nom: rdv.fournisseur.nom,
        };

        try {
          const reponse = await fetch(url, {
            method: "PATCH",
            body: JSON.stringify(params),
          });

          if (!reponse.ok) {
            throw new Error(`${reponse.status} : ${reponse.statusText}`);
          }

          const resultat = await reponse.json();

          if (!resultat.erreur) {
            Notiflix.Notify.success(resultat.message);
            numero_bl.dataset.initial = numero_bl.textContent;
          }

          if (resultat.erreur) {
            Notiflix.Report.failure("Erreur", resultat.message, "OK");
            numero_bl.textContent = numero_bl.dataset.initial;
          }
        } catch (err) {
          Notiflix.Notify.failure(err.message);
          numero_bl.textContent = numero_bl.dataset.initial;
        }
      };

      // Commentaires
      ligne_rdv.querySelector(".commentaire_public").innerHTML =
        rdv.commentaire_public.replace(/\r\n|\r|\n/g, "<br>");
      ligne_rdv.querySelector(".commentaire_cache").innerHTML =
        rdv.commentaire_cache.replace(/\r\n|\r|\n/g, "<br>");

      // Boutons copie-modif-suppr
      ligne_rdv.querySelectorAll(
        ".copie-modif-suppr a"
      )[0].href += `?id=${rdv.id}&copie`;
      ligne_rdv.querySelectorAll(
        ".copie-modif-suppr a"
      )[1].href += `?id=${rdv.id}`;
      ligne_rdv.querySelectorAll(".copie-modif-suppr a")[2].onclick = () => {
        Notiflix.Confirm.merge({
          titleColor: "#ff5549",
          okButtonColor: "#f8f8f8",
          okButtonBackground: "#ff5549",
          cancelButtonColor: "#f8f8f8",
          cancelButtonBackground: "#a9a9a9",
        });

        Notiflix.Confirm.show(
          "Suppression RDV",
          "Voulez-vous vraiment supprimer le RDV ?",
          "Supprimer",
          "Annuler",
          function () {
            // Blocage de la ligne pendant la requête
            Notiflix.Block.dots(`#${ligne_rdv.id}`);
            document.querySelector(`#_${rdv.id}`).style.minHeight = "initial";

            const url = new URL(env.api);
            url.pathname += `bois/rdvs/${rdv.id}`;

            ligne_rdv.style.backgroundColor = "#ccc";

            fetch(url, {
              method: "DELETE",
            })
              .then((reponse) => {
                if (reponse.ok) {
                  // Suppression de la ligne RDV
                  ligne_rdv.remove();
                  Notiflix.Notify.success("Le RDV a été supprimé");

                  // Suppression du bloc date s'il ne reste que la date
                  if (bloc_date.childElementCount === 1) {
                    bloc_date.remove();
                  }
                } else {
                  throw new Error(`${reponse.status} : ${reponse.statusText}`);
                }
              })
              .catch((err) => {
                console.error(err);
                Notiflix.Notify.failure(err.message);
                Notiflix.Block.remove(`#${ligne_rdv.id}`);
              });
          }
        );
      };

      bloc_date.appendChild(ligne_rdv);
    }

    non_attente.appendChild(bloc_date);
  }

  /**
   * RDVs en attente
   */
  const attente = document.createElement("div");

  // Affichage de la ligne "Attente"
  const ligne_date =
    modele_ligne_date_attente.content.firstElementChild.cloneNode(true);
  attente.appendChild(ligne_date);

  // Affichage des camions
  ligne_date.querySelector(".total .camions").textContent =
    rdvs.attente.stats.total +
    (filtre ? ` (${rdvs.attente.stats.total_filtre})` : "");

  for (let rdv of rdvs.attente.rdvs) {
    // Affichage des données du rdv
    const ligne_rdv =
      modele_ligne_rdv_attente.content.firstElementChild.cloneNode(true);

    rdv = new RDV(rdv);

    ligne_rdv.id = `_${rdv.id}`;

    // Date
    ligne_rdv.querySelector(".date-rdv").textContent = rdv.date_rdv
      ? new Date(rdv.date_rdv).toLocaleDateString("fr-FR", {
          weekday: "long",
          day: "numeric",
          month: "long",
          year: "numeric",
        })
      : "Pas de date";

    // Chargement, client, livraison, transporteur, affreteur, fournisseur
    ligne_rdv.querySelector(".chargement .adresse").textContent =
      rdv.ligne_chargement;
    if (!rdv.chargement.id || rdv.chargement.id === 1) {
      ligne_rdv.querySelector(".chargement").remove();
    }

    ligne_rdv.querySelector(".client .adresse").textContent = rdv.ligne_client;
    if (rdv.client.id === rdv.livraison.id && rdv.chargement.id === 1) {
      ligne_rdv.querySelector(".client .role").remove();
    }

    ligne_rdv.querySelector(".livraison .adresse").textContent =
      rdv.ligne_livraison;
    if (
      !rdv.livraison.id ||
      (rdv.livraison.id && rdv.client.id === rdv.livraison.id)
    ) {
      ligne_rdv.querySelector(".livraison").remove();
    }

    ligne_rdv.querySelector(".tooltip-livraison").textContent = rdv.adresse;

    ligne_rdv.querySelector(".transporteur-nom").textContent =
      rdv.transporteur.nom;
    ligne_rdv.querySelector(".tooltip-transporteur").textContent =
      rdv.transporteur.telephone || "Téléphone non renseigné";
    if (rdv.transporteur.id < 11) {
      ligne_rdv.querySelector(".tooltip-transporteur").remove();
    }

    ligne_rdv.querySelector(".affreteur").textContent = rdv.affreteur.nom;
    ligne_rdv.querySelector(".affreteur").dataset.lie_agence =
      rdv.affreteur.lie_agence;

    ligne_rdv.querySelector(".fournisseur").textContent = rdv.fournisseur.nom;

    // Commentaires
    ligne_rdv.querySelector(".commentaire_public").innerHTML =
      rdv.commentaire_public.replace(/(?:\r\n|\r|\n)/g, "<br>");
    ligne_rdv.querySelector(".commentaire_cache").innerHTML =
      rdv.commentaire_cache.replace(/(?:\r\n|\r|\n)/g, "<br>");

    // Boutons copie-modif-suppr
    ligne_rdv.querySelectorAll(
      ".copie-modif-suppr a"
    )[0].href += `?id=${rdv.id}&copie`;
    ligne_rdv.querySelectorAll(
      ".copie-modif-suppr a"
    )[1].href += `?id=${rdv.id}`;
    ligne_rdv.querySelectorAll(".copie-modif-suppr a")[2].onclick = () => {
      Notiflix.Confirm.merge({
        titleColor: "#ff5549",
        okButtonColor: "#f8f8f8",
        okButtonBackground: "#ff5549",
        cancelButtonColor: "#f8f8f8",
        cancelButtonBackground: "#a9a9a9",
      });

      Notiflix.Confirm.show(
        "Suppression RDV",
        "Voulez-vous vraiment supprimer le RDV ?",
        "Supprimer",
        "Annuler",
        function () {
          // Blocage de la ligne pendant la requête
          Notiflix.Block.dots(`#${ligne_rdv.id}`);
          document.querySelector(`#_${rdv.id}`).style.minHeight = "initial";

          const url = new URL(env.api);
          url.pathname += `bois/rdvs/${rdv.id}`;

          ligne_rdv.style.backgroundColor = "#ccc";

          fetch(url, {
            method: "DELETE",
          })
            .then((reponse) => {
              if (reponse.ok) {
                // Suppression de la ligne
                // ligne_rdv.remove();
                Notiflix.Notify.success("Le RDV a été supprimé");
              }
            })
            .catch((err) => {
              console.error(err);
              Notiflix.Notify.failure(err.message);
              Notiflix.Block.remove(`#${ligne_rdv.id}`);
            });
        }
      );
    };

    attente.appendChild(ligne_rdv);
  }

  supprimerElementsNonAutorises(attente);
  supprimerElementsNonAutorises(non_attente);

  // Insertion dans le DOM
  cleanDOM(attente);
  cleanDOM(non_attente);

  main.appendChild(non_attente);
  main.appendChild(attente);
}

/**
 * Heure d'arrivée
 *
 * Insertion de l'heure en cliquant sur l'horloge
 * \+ Numéro BL automatique (Stora Enso)
 *
 * @param {RDV} rdv
 */
async function renseignerHeureArrivee(rdv) {
  if (!utilisateurEstAutorise("bois", "edit")) return;

  const url = new URL(env.api);
  url.pathname += `bois/rdvs/${rdv.id}/heure`;

  const params = {
    horloge: "arrivee",
    fournisseur_id: rdv.fournisseur.id,
    fournisseur_nom: rdv.fournisseur.nom,
  };

  try {
    const reponse = await fetch(url, {
      method: "PATCH",
      body: JSON.stringify(params),
    });

    if (!reponse.ok) {
      throw new Error(`${reponse.status} : ${reponse.statusText}`);
    }
  } catch (err) {
    Notiflix.Notify.failure(err.message);
  }
}

/**
 * Heure de départ
 *
 * Insertion de l'heure en cliquant sur l'horloge
 *
 * @param {RDV} rdv
 */
async function renseignerHeureDepart(rdv) {
  if (!utilisateurEstAutorise("bois", "edit")) return;

  if (!rdv.heure_arrivee) return;

  const url = new URL(env.api);
  url.pathname += `bois/rdvs/${rdv.id}/heure`;

  const params = {
    horloge: "depart",
  };

  try {
    const reponse = await fetch(url, {
      method: "PATCH",
      body: JSON.stringify(params),
    });

    if (!reponse.ok) {
      throw new Error(`${reponse.status} : ${reponse.statusText}`);
    }
  } catch (err) {
    Notiflix.Notify.failure(err.message);
  }
}

/**
 * Bouton registre
 *
 * Extraction du registre d'affrètement
 */
document
  .getElementById("bouton-registre")
  .addEventListener("click", async () => {
    const url = new URL(env.api);
    url.pathname += "bois/registre";

    const params = {
      date_debut: document.getElementById("date_debut").value,
      date_fin: document.getElementById("date_fin").value,
    };

    url.search = new URLSearchParams(params).toString();

    const reponse = await fetch(url);

    if (!reponse.ok) {
      Notiflix.Notify.failure(`${reponse.status} : ${reponse.statusText}`);
      return;
    }

    const blob = await reponse.blob();
    const file = URL.createObjectURL(blob);
    const filename = reponse.headers
      .get("Content-Disposition")
      .split("; filename=")[1];

    const link = document.createElement("a");
    link.href = file;
    link.download = filename;
    link.click();
  });
