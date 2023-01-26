import Notiflix from "notiflix";

import env from "../lib/environment";
import boutonsAMS from "../lib/boutonsAMS";
import { awesompleteTiers } from "../lib/awesompleteTiers";

/**
 * Récupère les données du RDV.
 *
 * @returns {Promise} Données du RDV
 */
const recupererRdv = new Promise(async (resolve, reject) => {
  const id = new URLSearchParams(window.location.search).get("id");

  if (id) {
    const url = new URL(env.api);
    url.pathname += `vrac/rdvs/${id}`;

    const reponse = await fetch(url);

    if (reponse.ok) {
      const rdvs = await reponse.json();
      resolve(rdvs);
    } else {
      reject(
        Notiflix.Notify.failure(`${reponse.status}, ${reponse.statusText}`)
      );
    }
  } else {
    resolve(null);
  }
});

/**
 * Remplissage des inputs avec les données du RDV
 */
(async () => {
  const rdv = await recupererRdv;

  if (!rdv) return; // Nouveau RDV vierge

  const inputs = ["date_rdv", "heure", "quantite", "num_commande"];
  for (const input of inputs) {
    try {
      document.getElementById(input).value = rdv[input];
    } catch (error) {
      console.error(input, error.message);
    }
  }

  // Max
  document.getElementById("max").checked = rdv.max;

  // Commentaire
  document.getElementById("commentaire").textContent = rdv.commentaire;

  // Fournisseur, Client, Transporteur
  document.getElementById("fournisseur_value").value = rdv.fournisseur;
  document.getElementById("fournisseur_user").value = rdv.fournisseur_nom;

  document.getElementById("client_value").value = rdv.client;
  document.getElementById(
    "client_user"
  ).value = `${rdv.client_nom} - ${rdv.client_ville}`;

  document.getElementById("transporteur_value").value = rdv.transporteur;
  document.getElementById("transporteur_user").value = rdv.transporteur_nom;
})();

/**
 * Produits / Qualités
 */
(async () => {
  const PRODUIT_SELECT = document.getElementById("produit"); // Liste déroulante produits
  const QUALITE_SELECT = document.getElementById("qualite"); // Liste déroulante qualités
  const UNITE = document.getElementById("unite");

  const url = new URL(env.api);
  url.pathname += "vrac/produits";

  const reponse_produits = await fetch(url);
  const produits = await reponse_produits.json();

  // Construction de la liste déroulante des produits
  let options = `<option value="">Sélectionnez</option>`;
  for (const produit of produits) {
    options += `<option value="${produit.id}">${produit.nom}</option>`;
  }
  PRODUIT_SELECT.innerHTML = options;

  const rdv = await recupererRdv;
  if (rdv) PRODUIT_SELECT.value = rdv.produit;

  PRODUIT_SELECT.addEventListener("change", changeQualite);

  changeQualite(); // Sélection de la qualité

  // Affichage des qualités du produit sélectionné
  async function changeQualite() {
    const produit = produits.filter(
      (produit) => produit.id == PRODUIT_SELECT.value
    )[0];

    let options = null;

    if (!produit) {
      QUALITE_SELECT.innerHTML = null;
      QUALITE_SELECT.setAttribute("disabled", true);
    } else {
      for (const qualite of produit.qualites) {
        options += `<option value="${qualite.id}">${qualite.nom}</option>`;
      }
      QUALITE_SELECT.innerHTML = options;

      const rdv = await recupererRdv;
      if (rdv && rdv.produit == produit.id) {
        QUALITE_SELECT.value = rdv.qualite;
      }

      if (produit.qualites.length === 0) {
        QUALITE_SELECT.setAttribute("disabled", true);
      } else {
        QUALITE_SELECT.removeAttribute("disabled");
      }
    }

    UNITE.innerHTML = produit ? produit.unite : null;
  }
})();

/**
 * Awesomplete tiers
 */
awesompleteTiers("#fournisseur", { role: "vrac_fournisseur" });
awesompleteTiers("#client", { role: "vrac_client" });
awesompleteTiers("#transporteur", { role: "vrac_transporteur" });

/**
 * Boutons dynamiques création/modification RDV.
 * Récupération des parties "id=" et "copie=" de l'URL
 * - si "id=" existe mais pas "copie=", alors boutons "Modifier" et "Supprimer"
 * - sinon ("id=" n'existe pas (nouveau rdv) ou "copie=" existe), alors bouton "Ajouter"
 */
(() => {
  const query = new URLSearchParams(window.location.search);
  boutonsAMS(query.has("id") && !query.has("copie"));
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
 * Empêcher la création d'un rdv en appuyant sur la touche "Entrée"
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
 * Nouveau RDV
 */
(() => {
  document.querySelector(".bouton-ajouter").onclick = async (e) => {
    e.preventDefault();

    if (!validerFormulaire()) return;

    e.target.setAttribute("disabled", true);

    const formData = new FormData(document.querySelector("form"));

    const url = new URL(env.api);
    url.pathname += `vrac/rdvs`;

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
})();

/**
 * Modification RDV
 */
(() => {
  document.querySelector(".bouton-modifier").onclick = async (e) => {
    e.preventDefault();

    if (!validerFormulaire()) return;

    e.target.setAttribute("disabled", true);

    const formData = new FormData(document.querySelector("form"));

    const id = new URLSearchParams(window.location.search).get("id");

    const url = new URL(env.api);
    url.pathname += `vrac/rdvs/${id}`;

    try {
      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(Object.fromEntries(formData)),
      });

      if (!reponse.ok) {
        throw Error(`${reponse.status}, ${reponse.statusText}`);
      }

      location = "./";
      // Notiflix.Notify.success("Le RDV a été modifié");
    } catch (erreur) {
      // Notification erreur
      Notiflix.Notify.failure(erreur.message);
    }

    e.target.removeAttribute("disabled");
  };
})();

/**
 * Suppression RDV
 */
(() => {
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
      "Suppression RDV",
      `Voulez-vous vraiment supprimer le RDV ?`,
      "Supprimer",
      "Annuler",
      async function () {
        const url = new URL(env.api);
        url.pathname += `vrac/rdvs/${id}`;

        try {
          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok)
            throw new Error(`${reponse.status}, ${reponse.statusText}`);

          location = "./";
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
})();

/**
 * Bouton Annuler
 */
document.querySelector(".bouton-annuler").onclick = (e) => {
  e.preventDefault();
  location = "./";
};
