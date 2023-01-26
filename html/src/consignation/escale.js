import Notiflix from "notiflix";

import env from "../lib/environment";
import boutonsAMS from "../lib/boutonsAMS";
import { awesompletePorts } from "../lib/awesompletePorts";
import { awesompleteTiers } from "../lib/awesompleteTiers";
import { formatDecimal } from "../lib/formatDecimal";

const archives = new URLSearchParams(location.search).has("archives")
  ? "?archives"
  : "";

/**
 * Récupération + affichage des informations de l'escale
 */
(async () => {
  const id = new URLSearchParams(window.location.search).get("id");

  if (!id) return; // Nouvelle escale

  const url_escale = new URL(env.api);
  url_escale.pathname += `consignation/escales/${id}`;

  const reponse = await fetch(url_escale);

  if (!reponse.ok) return;

  const escale = await reponse.json();

  document.getElementById("navire").value = escale.navire;
  document.getElementById("voyage").value = escale.voyage;
  document.getElementById("armateur_user").value = escale.armateur_nom;
  document.getElementById("armateur_value").value = escale.armateur;
  document.getElementById("eta_date").value = escale.eta_date;
  document.getElementById("eta_heure").value = escale.eta_heure;
  document.getElementById("nor_date").value = escale.nor_date;
  document.getElementById("nor_heure").value = escale.nor_heure;
  document.getElementById("pob_date").value = escale.pob_date;
  document.getElementById("pob_heure").value = escale.pob_heure;
  document.getElementById("etb_date").value = escale.etb_date;
  document.getElementById("etb_heure").value = escale.etb_heure;
  document.getElementById("ops_date").value = escale.ops_date;
  document.getElementById("ops_heure").value = escale.ops_heure;
  document.getElementById("etc_date").value = escale.etc_date;
  document.getElementById("etc_heure").value = escale.etc_heure;
  document.getElementById("etd_date").value = escale.etd_date;
  document.getElementById("etd_heure").value = escale.etd_heure;
  document.getElementById("te_arrivee").value =
    escale.te_arrivee?.toFixed(2) || null;
  document.getElementById("te_depart").value =
    escale.te_depart?.toFixed(2) || null;
  document.getElementById("last_port_user").value = escale.last_port_nom;
  document.getElementById("last_port_value").value = escale.last_port;
  document.getElementById("next_port_user").value = escale.next_port_nom;
  document.getElementById("next_port_value").value = escale.next_port;
  document.getElementById("call_port").value = escale.call_port;
  document.getElementById("quai").value = escale.quai;
  document.getElementById("commentaire").textContent = escale.commentaire;

  awesompletePorts("#last_port");
  awesompletePorts("#next_port");

  // Marchandises
  const modele_ligne_marchandise = document.querySelector(
    "template#ligne-marchandise"
  );

  for (const marchandise of escale.marchandises) {
    const ligne_marchandise =
      modele_ligne_marchandise.content.firstElementChild.cloneNode(true);
    ligne_marchandise.querySelector(".id").value = marchandise.id;
    ligne_marchandise.querySelector(".marchandise").value =
      marchandise.marchandise;
    ligne_marchandise.querySelector(".client").value = marchandise.client;
    ligne_marchandise.querySelector(".operation").value = marchandise.operation;
    marchandise.environ
      ? ligne_marchandise
          .querySelector(".environ")
          .setAttribute("checked", true)
      : ligne_marchandise.querySelector(".environ").removeAttribute("checked");
    ligne_marchandise.querySelector(".tonnage_bl").value =
      marchandise.tonnage_bl?.toFixed(3) || null;
    ligne_marchandise.querySelector(".cubage_bl").value =
      marchandise.cubage_bl?.toFixed(3) || null;
    ligne_marchandise.querySelector(".nombre_bl").value = marchandise.nombre_bl;
    ligne_marchandise.querySelector(".tonnage_outturn").value =
      marchandise.tonnage_outturn?.toFixed(3) || null;
    ligne_marchandise.querySelector(".cubage_outturn").value =
      marchandise.cubage_outturn?.toFixed(3) || null;
    ligne_marchandise.querySelector(".nombre_outturn").value =
      marchandise.nombre_outturn;

    ligne_marchandise.querySelector(".poubelle").onclick = () => {
      ligne_marchandise.remove();
    };

    document.getElementById("marchandises").appendChild(ligne_marchandise);
  }

  formatDecimal();
})();

/**
 * Ajout ligne marchandise
 */
(() => {
  // Ajout ligne marchandise
  const modele_ligne_marchandise = document.querySelector(
    "template#ligne-marchandise"
  );

  document.getElementById("ajouter-marchandise").onclick = () => {
    const ligne_marchandise =
      modele_ligne_marchandise.content.firstElementChild.cloneNode(true);

    ligne_marchandise.querySelector(".poubelle").onclick = () => {
      ligne_marchandise.remove();
    };

    document.getElementById("marchandises").appendChild(ligne_marchandise);

    formatDecimal();
  };
})();

/**
 * Awesomplete tiers
 */
awesompleteTiers("#armateur", { role: "maritime_armateur" });

/**
 * Nom du navire en lettres capitales
 */
document.getElementById("navire").oninput = (e) => {
  e.target.value = e.target.value.toUpperCase();
};

/**
 * Formattage heure ETX (majuscules + rajout auto ':' heure)
 */
for (const inputHeure of document.querySelectorAll(".heure")) {
  inputHeure.addEventListener("change", () => {
    let contenu = inputHeure.value.trim().toUpperCase();
    let heure_formattee = contenu;
    let regexp_HHMM = /^((([01][0-9]|2[0-3])([:H]?)[0-5][0-9])|24([:H]?)00)\b/;

    if (regexp_HHMM.test(contenu)) {
      let separateur =
        contenu.charAt(2) === ":" || contenu.charAt(2) === "H" ? 3 : 2;
      heure_formattee =
        contenu.substr(0, 2) + ":" + contenu.substr(separateur, contenu.length);
    }
    inputHeure.value = heure_formattee;
  });
}

/**
 * N° voyage automatique
 * Laisser APRÈS le formattage de la date
 */
for (const input of document.querySelectorAll(
  "#navire, #eta_date, #etc_date"
)) {
  input.addEventListener("change", async function () {
    const navire = document.getElementById("navire").value.trim();
    const id = new URLSearchParams(location.search).get("id") || "";
    const eta_annee = document.getElementById("eta_date").value.substr(0, 4);

    if (navire === "" || navire === "TBN") {
      // Si navire non nommé, pas de numéro de voyage
      document.getElementById("voyage").value = "";
    } else {
      // Récupération du dernier numéro de voyage pour ce navire
      const url = new URL(env.api);
      url.pathname += "consignation/voyage";

      const params = {
        navire,
        id,
      };

      url.search = new URLSearchParams(params).toString();

      const reponse = await fetch(url);
      const json = await reponse.json();
      const voyage = json.voyage;

      // Nouveau voyage par défaut (navire jamais venu)
      const nouveau_voyage = {
        annee: new Date().getFullYear(),
        numero: 1,
      };

      if (voyage) {
        // Le navire est déjà venu
        const dernier_voyage = {
          annee: voyage.substr(0, 4),
          numero: parseInt(voyage.substr(5)) || 0,
        };

        nouveau_voyage.annee = eta_annee || new Date().getFullYear();

        nouveau_voyage.numero =
          nouveau_voyage.annee == dernier_voyage.annee
            ? dernier_voyage.numero + 1 // Le navire est déjà venu cette année
            : 1; // Le navire n'est pas venu cette année
      }

      document.getElementById("voyage").value =
        nouveau_voyage.annee + "/" + nouveau_voyage.numero;
    }
  });
}

/**
 * Boutons dynamiques création/modification escale.
 * Récupération des parties "id=" et "copie=" de l'URL
 *  - si "id=" n'existe pas (nouvelle escale), alors bouton "Ajouter"
 *  - si "id=" existe, alors boutons "Modifier" et "Supprimer"
 */
(() => {
  const query = new URLSearchParams(window.location.search);
  boutonsAMS(query.has("id"));
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
 * Parse les données du formulaire pour les envoyer via ```fetch()```.
 *
 * @returns {String} Données formulaire *JSON.stringified*
 */
function formatterDonneesFormulaire() {
  const formData = {
    id: new URLSearchParams(window.location.search).get("id") || null,
    navire: document.getElementById("navire").value,
    voyage: document.getElementById("voyage").value,
    armateur: document.getElementById("armateur_value").value,
    eta_date: document.getElementById("eta_date").value || null,
    eta_heure: document.getElementById("eta_heure").value,
    nor_date: document.getElementById("nor_date").value || null,
    nor_heure: document.getElementById("nor_heure").value,
    pob_date: document.getElementById("pob_date").value || null,
    pob_heure: document.getElementById("pob_heure").value,
    etb_date: document.getElementById("etb_date").value || null,
    etb_heure: document.getElementById("etb_heure").value,
    ops_date: document.getElementById("ops_date").value || null,
    ops_heure: document.getElementById("ops_heure").value,
    etc_date: document.getElementById("etc_date").value || null,
    etc_heure: document.getElementById("etc_heure").value,
    etd_date: document.getElementById("etd_date").value || null,
    etd_heure: document.getElementById("etd_heure").value,
    te_arrivee: parseFloat(document.getElementById("te_arrivee").value),
    te_depart: parseFloat(document.getElementById("te_depart").value),
    last_port: document.getElementById("last_port_value").value,
    next_port: document.getElementById("next_port_value").value,
    call_port: document.getElementById("call_port").value,
    quai: document.getElementById("quai").value,
    commentaire: document.getElementById("commentaire").value,
    marchandises: [],
  };

  document.querySelectorAll(".ligne-marchandise").forEach((ligne) => {
    const marchandise = {
      id: ligne.querySelector(".id").value,
      escale_id: new URLSearchParams(window.location.search).get("id") || null,
      marchandise: ligne.querySelector(".marchandise").value,
      client: ligne.querySelector(".client").value,
      operation: ligne.querySelector(".operation").value,
      environ: ligne.querySelector(".environ").checked,
      tonnage_bl: parseFloat(ligne.querySelector(".tonnage_bl").value) || null,
      cubage_bl: parseFloat(ligne.querySelector(".cubage_bl").value) || null,
      nombre_bl: parseInt(ligne.querySelector(".nombre_bl").value) || null,
      tonnage_outturn:
        parseFloat(ligne.querySelector(".tonnage_outturn").value) || null,
      cubage_outturn:
        parseFloat(ligne.querySelector(".cubage_outturn").value) || null,
      nombre_outturn:
        parseInt(ligne.querySelector(".nombre_outturn").value) || null,
    };

    formData.marchandises.push(marchandise);
  });

  return JSON.stringify(formData);
}

/**
 * Empêcher la création d'une escale en appuyant sur la touche "Entrée"
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
 * Nouvelle escale
 */
document.querySelector(".bouton-ajouter").onclick = async (e) => {
  e.preventDefault();

  if (!validerFormulaire()) return;

  e.target.setAttribute("disabled", true);

  const formData = formatterDonneesFormulaire();

  const url = new URL(env.api);
  url.pathname += `consignation/escales`;

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

/**
 * Modification escale
 */
document.querySelector(".bouton-modifier").onclick = async (e) => {
  e.preventDefault();

  if (!validerFormulaire()) return;

  e.target.setAttribute("disabled", true);

  const formData = formatterDonneesFormulaire();

  const id = new URLSearchParams(window.location.search).get("id");

  const url = new URL(env.api);
  url.pathname += `consignation/escales/${id}`;

  try {
    const reponse = await fetch(url, {
      method: "PUT",
      body: formData,
    });

    if (!reponse.ok) {
      throw Error(`${reponse.status}, ${reponse.statusText}`);
    }

    location = "./" + archives;
    // Notiflix.Notify.success("L'escale a été modifiée");
  } catch (erreur) {
    // Notification erreur
    Notiflix.Notify.failure(erreur.message);
  }

  e.target.removeAttribute("disabled");
};

/**
 * Suppression escale
 */
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
    "Suppression escale",
    `Voulez-vous vraiment supprimer l'escale ?`,
    "Supprimer",
    "Annuler",
    async function () {
      const url = new URL(env.api);
      url.pathname += `consignation/escales/${id}`;

      try {
        const reponse = await fetch(url, {
          method: "DELETE",
        });

        if (!reponse.ok)
          throw new Error(`${reponse.status}, ${reponse.statusText}`);

        location = "./" + archives;
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

/**
 * Bouton Annuler
 */
document.querySelector(".bouton-annuler").onclick = (e) => {
  e.preventDefault();
  location = "./" + archives;
};
