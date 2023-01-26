import Notiflix from "notiflix";

import { env, cleanDOM, demarrerConnexionSSE } from "@app/utils";
import { supprimerElementsNonAutorises } from "@app/auth";

import { BandeauInfo } from "@app/components";

const bandeauInfo = new BandeauInfo({
  target: document.getElementById("bandeau-info"),
  props: {
    module: "vrac",
    pc: true,
  },
});

afficherPlaceholders();
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
 * Récupère la liste des RDVs.
 *
 * @returns {Promise} Liste des RDVs au format JSON
 */
async function recupererRdvs() {
  const url = new URL(env.api);
  url.pathname += "vrac/rdvs";

  const params = {
    groupe: "date",
    navires: true,
    marees: true,
  };

  url.search = new URLSearchParams(params).toString();

  try {
    const reponse = await fetch(url);

    if (!reponse.ok)
      throw new Error(`${reponse.status} : ${reponse.statusText}`);

    const rdvs = await reponse.json();
    return rdvs;
  } catch (err) {
    throw err;
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
  const planning = document.createElement("div");

  /* Récupération des templates */
  const modele_ligne_date = document.querySelector("#ligne-date");
  const modele_ligne_rdv = document.querySelector("#ligne-rdv");

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

    // Marée (point d'exclamation si supérieur à 4m)
    if (rdvs[date].te >= 4) {
      ligne_date.querySelector(".marees").style.visibility = "visible";
    }

    // Navires
    if (rdvs[date].navires.length > 0) {
      const navires = [];
      rdvs[date].navires.map((navire) => navires.push(navire));
      ligne_date.querySelector(".texte-navires").innerHTML =
        navires.join("<br>");
      ligne_date.querySelector(".navires").style.visibility = "visible";
    }

    bloc_date.appendChild(ligne_date);

    for (const rdv of rdvs[date].rdvs) {
      // Affichage des données du rdv
      const ligneRdv =
        modele_ligne_rdv.content.firstElementChild.cloneNode(true);
      ligneRdv.id = `_${rdv.id}`;
      ligneRdv.querySelector(".produit").textContent = rdv.produit_nom;
      ligneRdv.querySelector(".produit").style.color = rdv.produit_couleur;
      ligneRdv.querySelector(".qualite").textContent = rdv.qualite_nom;
      ligneRdv.querySelector(".qualite").style.color = rdv.qualite_couleur;
      ligneRdv.querySelector(".heure").textContent = rdv.heure;
      ligneRdv.querySelector(".quantite").textContent = rdv.quantite;
      ligneRdv.querySelector(".unite").textContent = rdv.unite;
      ligneRdv.querySelector(".max").textContent = rdv.max == 1 ? "max" : null;
      ligneRdv.querySelector(".client").textContent =
        rdv.client_nom + " " + rdv.client_ville;
      ligneRdv.querySelector(".transporteur").textContent =
        rdv.transporteur_nom;
      ligneRdv.querySelector(".num_commande").textContent = rdv.num_commande;
      ligneRdv.querySelector(".commentaire").innerHTML =
        rdv.commentaire.replace(/\r\n|\r|\n/g, "<br>");

      ligneRdv.querySelectorAll(
        ".copie-modif-suppr a"
      )[0].href += `?id=${rdv.id}&copie`;
      ligneRdv.querySelectorAll(
        ".copie-modif-suppr a"
      )[1].href += `?id=${rdv.id}`;
      ligneRdv.querySelectorAll(".copie-modif-suppr a")[2].onclick = () => {
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
            Notiflix.Block.dots(`#${ligneRdv.id}`);
            document.querySelector(`#_${rdv.id}`).style.minHeight = "initial";

            const url = new URL(env.api);
            url.pathname += `vrac/rdvs/${rdv.id}`;

            ligneRdv.style.backgroundColor = "#ccc";

            fetch(url, {
              method: "DELETE",
            })
              .then((reponse) => {
                if (reponse.ok) {
                  // Suppression de la ligne
                  ligneRdv.remove();
                  Notiflix.Notify.success("Le RDV a été supprimé");

                  // Suppression du bloc date s'il ne reste que la date
                  if (bloc_date.childElementCount === 1) {
                    bloc_date.remove();
                  }
                }
              })
              .catch((err) => {
                console.error(err);
                Notiflix.Notify.failure(err.message);
                Notiflix.Block.remove(`#${ligneRdv.id}`);
              });
          }
        );
      };
      bloc_date.appendChild(ligneRdv);
    }

    planning.appendChild(bloc_date);
  }

  supprimerElementsNonAutorises(planning);

  cleanDOM(planning);

  const main = document.querySelector("main");
  main.innerHTML = null;
  main.appendChild(planning);
}
