import Notiflix from "notiflix";

import env from "../lib/environment.js";
import boutonsAMS from "../lib/boutonsAMS.js";

/* Création / Modification produit (page produit.php) */
const select = document.getElementById("id");
const PRODUIT_NOM = document.getElementById("nom");
const PRODUIT_COULEUR = document.getElementById("couleur");
const PRODUIT_UNITE = document.getElementById("unite");
const QUALITES = document.getElementById("qualites");

/**
 * Construction de la liste déroulante.
 */
function raffraichirListeDeroulante() {
  return new Promise(async (resolve, reject) => {
    const url = new URL(env.api);
    url.pathname += "vrac/produits";

    const reponse = await fetch(url);
    const liste = await reponse.json();

    // Construction de la liste déroulante
    let options = `<option value="">Nouveau...</option>`;
    for (const produit of liste) {
      options += `<option value=${produit.id}>${produit.nom}</option>`;
    }
    select.innerHTML = options;

    // Au changement de la liste déroulante
    select.addEventListener("change", function () {
      // Lecture de la valeur sélectionnée dans la liste déroulante
      // puis changement des champs et boutons
      afficherDetailsProduit(
        liste.find((produit) => produit.id == select.value)
      );
    });
    resolve();
  });
}

/**
 * Affichage des propriétés des produits lors du changement de sélection.
 *
 * @param {Object} produit Produit sélectionné
 */
async function afficherDetailsProduit(produit) {
  if (!produit) {
    PRODUIT_NOM.value = "";
    PRODUIT_COULEUR.value = "#000000";
    PRODUIT_UNITE.value = "";
    QUALITES.innerHTML = "";
  } else {
    PRODUIT_NOM.value = produit.nom;
    PRODUIT_COULEUR.value = produit.couleur;
    PRODUIT_UNITE.value = produit.unite;

    // Affichage des qualités
    QUALITES.innerHTML = ""; // Remise à zéro des qualités
    // Modèle ligne qualité
    const modele_ligne_qualite = document.querySelector(
      "template#ligne-qualite"
    );

    // Remplissage + affichage ligne qualité
    for (const qualite of produit.qualites) {
      const ligne_qualite =
        modele_ligne_qualite.content.firstElementChild.cloneNode(true);
      ligne_qualite.querySelector("input[name*='id']").value = qualite.id;
      ligne_qualite.querySelector("input[name*='nom']").value = qualite.nom;
      ligne_qualite.querySelector("input[name*='couleur']").value =
        qualite.couleur;

      ligne_qualite.querySelector(".poubelle").onclick = () => {
        // Demande de confirmation
        Notiflix.Confirm.merge({
          titleColor: "#ff5549",
          okButtonColor: "#f8f8f8",
          okButtonBackground: "#ff5549",
          cancelButtonColor: "#f8f8f8",
          cancelButtonBackground: "#a9a9a9",
        });

        Notiflix.Confirm.show(
          "Suppression produit",
          `Voulez-vous vraiment supprimer la qualité ?<br />` +
            `Ceci supprimera les RDV associés.`,
          "Supprimer",
          "Annuler",
          function () {
            ligne_qualite.remove();
          }
        );
      };

      QUALITES.appendChild(ligne_qualite);
    }
  }

  // Modification des boutons
  boutonsAMS(produit);
}
/* ---------------------------------------- */

// Chargement de la page
raffraichirListeDeroulante();
boutonsAMS();

/**
 * Ajout / Suppression lignes qualités
 */
(() => {
  // Modèle ligne qualité
  const modele_ligne_qualite = document.querySelector("template#ligne-qualite");

  // Ajout ligne qualité
  document.getElementById("ajouter-qualite").onclick = () => {
    const ligne_qualite =
      modele_ligne_qualite.content.firstElementChild.cloneNode(true);

    ligne_qualite.querySelector(".poubelle").onclick = () => {
      ligne_qualite.remove();
    };

    QUALITES.appendChild(ligne_qualite);
  };
})();
/* ---------------------------------------- */

/**
 * Validation formulaire
 */
function validerFormulaire() {
  const formulaire = document.querySelector("form");
  const inputs = formulaire.querySelectorAll("input");
  const champs_invalides = [];

  let valide = true;
  let i = 1;

  for (const input of inputs) {
    if (!input.checkValidity()) {
      valide = false;
      if (input.dataset.nom) {
        let nom_champ = input.dataset.nom;
        if (nom_champ === "Nom de la qualité") {
          nom_champ += " " + i;
          i++;
        }
        champs_invalides.push(nom_champ);
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
    id: select.value,
    nom: PRODUIT_NOM.value,
    couleur: PRODUIT_COULEUR.value,
    unite: PRODUIT_UNITE.value,
    qualites: [],
  };

  document.querySelectorAll(".ligne-qualite").forEach((ligne) => {
    const qualite = {
      id: ligne.querySelector("input[name*='id']").value,
      nom: ligne.querySelector("input[name*='nom']").value,
      couleur: ligne.querySelector("input[name*='couleur']").value,
    };

    formData.qualites.push(qualite);
  });

  return JSON.stringify(formData);
}

/**
 * Nouveau produit
 */
(() => {
  document.querySelector(".bouton-ajouter").onclick = async (e) => {
    e.preventDefault();

    if (!validerFormulaire()) return;

    e.target.setAttribute("disabled", true);

    // const formData = new FormData(document.querySelector("form"));
    const formData = formatterDonneesFormulaire();

    const url = new URL(env.api);
    url.pathname += `vrac/produits`;

    try {
      const reponse = await fetch(url, {
        method: "POST",
        body: formData,
      });

      if (!reponse.ok)
        throw new Error(`${reponse.status} : ${reponse.statusText}`);

      // Notification produit créé
      Notiflix.Notify.success("Le produit a été créé");

      // Actualisation de la liste déroulante
      // et des boutons AMS
      Promise.all([reponse.json(), raffraichirListeDeroulante()]).then(
        (values) => {
          select.value = values[0].id;
          boutonsAMS(values[0]);
        }
      );
    } catch (erreur) {
      // Notification erreur
      Notiflix.Notify.failure(erreur.message);
    }

    e.target.removeAttribute("disabled");
  };
})();

/**
 * Modifier produit
 */
(() => {
  document.querySelector(".bouton-modifier").onclick = async (e) => {
    e.preventDefault();

    if (!validerFormulaire()) return;

    e.target.setAttribute("disabled", true);

    const formData = formatterDonneesFormulaire();

    const id = select.value;

    const url = new URL(env.api);
    url.pathname += `vrac/produits/${id}`;

    try {
      const reponse = await fetch(url, {
        method: "PUT",
        body: formData,
      });

      if (!reponse.ok)
        throw new Error(`${reponse.status} : ${reponse.statusText}`);

      // Notification produit modifié
      Notiflix.Notify.success("Le produit a été modifié");

      await raffraichirListeDeroulante();
      select.value = id;
    } catch (erreur) {
      // Notification erreur
      Notiflix.Notify.failure(erreur.message);
    }

    e.target.removeAttribute("disabled");
  };
})();

/**
 * Suppression produit
 */
(() => {
  document.querySelector(".bouton-supprimer").onclick = (e) => {
    e.preventDefault();
    e.target.setAttribute("disabled", true);

    const produit = select.options[select.selectedIndex].innerHTML;

    Notiflix.Confirm.merge({
      titleColor: "#ff5549",
      okButtonColor: "#f8f8f8",
      okButtonBackground: "#ff5549",
      cancelButtonColor: "#f8f8f8",
      cancelButtonBackground: "#a9a9a9",
    });

    // Demande de confirmation
    Notiflix.Confirm.show(
      "Suppression produit",
      `Voulez-vous vraiment supprimer le produit <b>${produit}</b> ?<br />` +
        `Ceci supprimera les RDV associés.`,
      "Supprimer",
      "Annuler",
      async function () {
        const id = select.value;

        const url = new URL(env.api);
        url.pathname += `vrac/produits/${id}`;

        try {
          const reponse = await fetch(url, {
            method: "DELETE",
          });

          if (!reponse.ok)
            throw new Error(`${reponse.status} : ${reponse.statusText}`);

          // Notification produit supprimé
          Notiflix.Notify.success("Le produit a été supprimé");

          // Remise à la sélection "Nouveau..."
          raffraichirListeDeroulante();
          select.value = "";
          afficherDetailsProduit();
        } catch (erreur) {
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
