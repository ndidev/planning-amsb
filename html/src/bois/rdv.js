import Notiflix from "notiflix";

import env from "../lib/environment";
import boutonsAMS from "../lib/boutonsAMS";
import { awesompleteTiers } from "../lib/awesompleteTiers";

/** @type {HTMLInputElement} */
const inputFournisseurUser = document.getElementById("fournisseur_user");
/** @type {HTMLInputElement} */
const inputFournisseurValue = document.getElementById("fournisseur_value");
/** @type {HTMLInputElement} */
const inputChargementUser = document.getElementById("chargement_user");
/** @type {HTMLInputElement} */
const inputChargementValue = document.getElementById("chargement_value");
/** @type {HTMLInputElement} */
const inputClientUser = document.getElementById("client_user");
/** @type {HTMLInputElement} */
const inputClientValue = document.getElementById("client_value");
/** @type {HTMLInputElement} */
const inputLivraisonUser = document.getElementById("livraison_user");
/** @type {HTMLInputElement} */
const inputLivraisonValue = document.getElementById("livraison_value");
/** @type {HTMLInputElement} */
const inputTransporteurUser = document.getElementById("transporteur_user");
/** @type {HTMLInputElement} */
const inputTransporteurValue = document.getElementById("transporteur_value");
/** @type {HTMLInputElement} */
const inputAffreteurUser = document.getElementById("affreteur_user");
/** @type {HTMLInputElement} */
const inputAffreteurValue = document.getElementById("affreteur_value");
/** @type {HTMLSpanElement} */
const iconeSuggestions = document.querySelector(".icone-suggestions");
/** @type {HTMLInputElement} */
const inputNumeroBL = document.getElementById("numero_bl");

/**
 * Récupère les données du RDV.
 *
 * @returns {Promise} Données du RDV
 */
const recupererRdv = new Promise(async (resolve, reject) => {
  const id = new URLSearchParams(window.location.search).get("id");

  if (id) {
    const url = new URL(env.api);
    url.pathname += `bois/rdvs/${id}`;

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

  if (!rdv) {
    // Nouveau RDV vierge
    inputChargementValue.value = 1; // AMSB
    inputChargementUser.value = `AMSB - Saint-Brieuc`;
    return;
  }

  const inputs = ["date_rdv", "heure_arrivee", "heure_depart", "numero_bl"];
  for (const input of inputs) {
    try {
      document.getElementById(input).value = rdv[input];
    } catch (error) {
      console.error(input, error.message);
    }
  }

  // Attente
  document.getElementById("attente").checked = rdv.attente;
  verifierSiDateRdvRequise();

  // Confirmation d'affrètement
  document.getElementById("confirmation_affretement").checked =
    rdv.confirmation_affretement;

  // Commentaires
  document.getElementById("commentaire_public").textContent =
    rdv.commentaire_public;
  document.getElementById("commentaire_cache").textContent =
    rdv.commentaire_cache;

  // Fournisseur, Chargement, Client, Livraison, Transporteur, Affréteur
  inputFournisseurValue.value = rdv.fournisseur.id;
  inputFournisseurUser.value = rdv.fournisseur.nom;

  if (rdv.chargement.id) {
    inputChargementValue.value = rdv.chargement.id;
    inputChargementUser.value = `${rdv.chargement.nom_court} - ${rdv.chargement.ville}`;
  }

  inputClientValue.value = rdv.client.id;
  inputClientUser.value = `${rdv.client.nom_court} - ${rdv.client.ville}`;

  if (rdv.livraison.id) {
    inputLivraisonValue.value = rdv.livraison.id;
    inputLivraisonUser.value = `${rdv.livraison.nom_court} - ${rdv.livraison.ville}`;
  }

  inputTransporteurValue.value = rdv.transporteur.id;
  inputTransporteurUser.value = rdv.transporteur.nom;

  inputAffreteurValue.value = rdv.affreteur.id;
  inputAffreteurUser.value = rdv.affreteur.nom;

  // Affichage de l'icône des suggestions
  afficherIconeSuggestionsSiPossible();
})();

/**
 * Awesomplete tiers
 */
awesompleteTiers("#fournisseur", { role: "bois_fournisseur" });
awesompleteTiers("#chargement", {
  role: "bois_client",
  role_affichage: "lieu de chargement bois",
});
awesompleteTiers("#client", {
  role: "bois_client",
  role_affichage: "client bois",
});
awesompleteTiers("#livraison", {
  role: "bois_client",
  role_affichage: "lieu de livraison bois",
});
awesompleteTiers("#transporteur", { role: "bois_transporteur" });
awesompleteTiers("#affreteur", {
  role: "bois_affreteur",
  role_affichage: "affréteur bois",
});

/**
 * Rendre la saisie de date optionnelle si le rdv est "En attente"
 *
 * @param {HTMLElement} checkbox
 */
function verifierSiDateRdvRequise() {
  const checkbox = document.getElementById("attente");

  if (checkbox.checked) {
    document.querySelector("#date_rdv").removeAttribute("required");
    document.querySelector(`label[for=date_rdv]`).classList.remove("requis");
  } else {
    document.querySelector("#date_rdv").setAttribute("required", "");
    document.querySelector(`label[for=date_rdv]`).classList.add("requis");
  }
}

document.getElementById("attente").onchange = verifierSiDateRdvRequise;

/**
 * Afficher l'icône des suggestions transporteur
 * quand le chargement et la livraison son renseignés
 */
function afficherIconeSuggestionsSiPossible() {
  iconeSuggestions.hidden =
    inputChargementValue.value === "" || inputLivraisonValue.value === "";
}

inputChargementUser.addEventListener(
  "change",
  afficherIconeSuggestionsSiPossible
);
inputLivraisonUser.addEventListener(
  "change",
  afficherIconeSuggestionsSiPossible
);

/**
 * Affichage des suggestions de transporteurs
 */
iconeSuggestions.addEventListener("click", async () => {
  if (inputChargementValue.value === "" || inputLivraisonValue.value === "") {
    Notiflix.Notify.failure(
      "Le chargement et la livraison doivent être renseignés pour obtenir des suggestions"
    );
    return;
  }

  const url = new URL(env.api);
  url.pathname += "bois/suggestions_transporteurs";

  const params = {
    chargement: inputChargementValue.value,
    livraison: inputLivraisonValue.value,
  };

  url.search = new URLSearchParams(params);

  try {
    const reponse = await fetch(url);

    if (!reponse.ok) {
      throw new Error(`${reponse.status} - ${reponse.statusText}`);
    }

    /** @type {Array} */
    const suggestions = await reponse.json();

    let ul = document.createElement("ul");
    ul.classList.add("suggestions");

    suggestions.transporteurs.forEach((transporteur) => {
      const li = document.createElement("li");
      li.classList.add("suggestion");
      // li.style.listStyle = "none";

      const spanTransporteur = document.createElement("span");
      spanTransporteur.classList.add("suggestion-transporteur");
      spanTransporteur.textContent = transporteur.nom;

      const spanTelephone = document.createElement("span");
      spanTelephone.classList.add("suggestion-telephone");
      spanTelephone.textContent =
        transporteur.telephone || "Téléphone non renseigné";

      // li.textContent =
      //   transporteur.nom +
      //   " - " +
      //   (transporteur.telephone || "Téléphone non renseigné");

      li.append(spanTransporteur, " - ", spanTelephone);
      ul.appendChild(li);
    });

    Notiflix.Report.info(
      "Suggestions de transporteurs",
      ul.outerHTML,
      "Fermer",
      {
        messageMaxLength: Infinity,
        width: "min(400px, 95%)",
        info: {
          backOverlayColor: "hsla(200, 100%, 20%, 0.1)",
        },
      }
    );
  } catch (erreur) {
    Notiflix.Notify.failure(erreur.message);
  }
});

/**
 * Remplissage automatique de la livraison
 * lors de la saisie du client
 * si le champ livraison est vide
 */
inputClientUser.addEventListener("change", () => {
  if (inputClientValue.value && !inputLivraisonValue.value) {
    inputLivraisonUser.value = inputClientUser.value;
    inputLivraisonValue.value = inputClientValue.value;
  }

  afficherIconeSuggestionsSiPossible();
});

/**
 * Boutons dynamiques création/modification RDV.
 * Récupération des parties "id=" et "copie=" de l'URL
 * - si "id=" existe mais pas "copie=", alors boutons "Modifier" et "Supprimer"
 * - sinon ("id=" n'existe pas (nouveau rdv) ou "copie=" existe), alors bouton "Ajouter"
 */
const query = new URLSearchParams(window.location.search);
boutonsAMS(query.has("id") && !query.has("copie"));

/**
 * Vérification du numéro BL directement pour éviter doublon
 */
inputNumeroBL.addEventListener("change", async (e) => {
  const rdv = await recupererRdv;

  const url = new URL(env.api);
  url.pathname += `bois/rdvs/${rdv.id}/numero_bl`;

  const params = {
    numero_bl: inputNumeroBL.value,
    fournisseur: rdv.fournisseur.id,
    fournisseur_nom: rdv.fournisseur.nom,
    dry_run: true,
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

    if (resultat.erreur) {
      Notiflix.Report.failure("Erreur", resultat.message, "OK", () => {
        inputNumeroBL.value = rdv.numero_bl;
      });
    }
  } catch (err) {
    Notiflix.Notify.failure(err.message);
    inputNumeroBL.value = rdv.numero_bl;
  }
});

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
    document.activeElement !== document.getElementById("commentaire_public") &&
    document.activeElement !== document.getElementById("commentaire_cache")
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
    url.pathname += `bois/rdvs`;

    try {
      const reponse = await fetch(url, {
        method: "POST",
        body: formData,
      });

      if (!reponse.ok) {
        throw Error(`${reponse.status}, ${reponse.statusText}`);
      }

      // Notiflix.Notify.success("Le RDV a été créé");
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
    url.pathname += `bois/rdvs/${id}`;

    try {
      const reponse = await fetch(url, {
        method: "PUT",
        body: JSON.stringify(Object.fromEntries(formData)),
      });

      if (!reponse.ok) {
        switch (reponse.status) {
          case 404:
            throw Error(`Le RDV n'existe pas.`);

          default:
            throw Error(`${reponse.status}, ${reponse.statusText}`);
        }
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
        url.pathname += `bois/rdvs/${id}`;

        try {
          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok)
            switch (reponse.status) {
              case 404:
                throw Error(`Le RDV n'existe pas.`);

              default:
                throw Error(`${reponse.status}, ${reponse.statusText}`);
            }

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
