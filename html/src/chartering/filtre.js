import { raffraichirPlanning } from "./index.js";
import { awesompleteTiers } from "../lib/awesompleteTiers.js";

const filtres_value = {
  date_debut: document.getElementById("date_debut"),
  date_fin: document.getElementById("date_fin"),
  statut: document.getElementById("filtre_statut"),
  affreteur: document.getElementById("filtre_affreteur_value"),
  armateur: document.getElementById("filtre_armateur_value"),
  courtier: document.getElementById("filtre_courtier_value"),
};

const filtres_user = {
  affreteur: document.getElementById("filtre_affreteur_user"),
  armateur: document.getElementById("filtre_armateur_user"),
  courtier: document.getElementById("filtre_courtier_user"),
};

/**
 * Affichage/Masquage du filtre
 */
(() => {
  const filtre_enregistre = JSON.parse(
    localStorage.getItem("filtre-planning-chartering")
  );

  if (filtre_enregistre) {
    document.getElementById("checkbox-filtre").checked = true;
  }

  document
    .querySelector("#bandeau-filtre legend")
    .addEventListener("click", () => {
      const checkbox = document.getElementById("checkbox-filtre");
      checkbox.checked = !checkbox.checked;
    });
})();

/**
 * Coloration bandeau filtre si filtre actif
 */
function colorationFiltre() {
  const filtre_enregistre = JSON.parse(
    localStorage.getItem("filtre-planning-chartering")
  );

  if (filtre_enregistre) {
    document.getElementById("filtre").classList.add("filtre_actif");
  } else {
    document.getElementById("filtre").classList.remove("filtre_actif");
  }
}

/**
 * Awesomplete sur les champs du filtre
 * (autorisation saisie multiple)
 */
(() => {
  const filtre_enregistre = JSON.parse(
    localStorage.getItem("filtre-planning-chartering")
  );

  filtres_value.date_debut.value = filtre_enregistre?.date_debut;
  filtres_value.date_fin.value = filtre_enregistre?.date_fin;
  filtres_value.statut.value = filtre_enregistre?.statut;

  awesompleteTiers("#filtre_affreteur", {
    role: "maritime_affreteur",
    role_affichage: "affréteur",
    valeur_initiale: filtre_enregistre?.affreteur,
    context: document,
    tags: true,
    actifs: false,
  });

  awesompleteTiers("#filtre_armateur", {
    role: "maritime_armateur",
    role_affichage: "armateur",
    valeur_initiale: filtre_enregistre?.armateur,
    context: document,
    tags: true,
    actifs: false,
  });

  awesompleteTiers("#filtre_courtier", {
    role: "maritime_courtier",
    role_affichage: "courtier d'affrètement",
    valeur_initiale: filtre_enregistre?.courtier,
    context: document,
    tags: true,
    actifs: false,
  });

  colorationFiltre();
})();

/**
 * Enregistrement du filtre
 */
document.querySelector("#filtre button[name='filtrer']").onclick = (e) => {
  e.preventDefault();

  const donnees_filtre = {};
  let filtre_actif = false;

  for (const [key, input] of Object.entries(filtres_value)) {
    donnees_filtre[key] = input.value;
    if (input.value) filtre_actif = true;
  }

  if (filtre_actif) {
    localStorage.setItem(
      "filtre-planning-chartering",
      JSON.stringify(donnees_filtre)
    );
  } else {
    localStorage.removeItem("filtre-planning-chartering");
  }

  colorationFiltre();

  raffraichirPlanning();
};

/**
 * Suppression du filtre
 */
document
  .querySelector("#filtre button[name='supprimer_filtre']")
  .addEventListener("click", (e) => {
    e.preventDefault();

    localStorage.removeItem("filtre-planning-chartering");

    for (const key of Object.keys(filtres_value)) {
      filtres_value[key].value = "";

      if (key in filtres_user) {
        filtres_user[key].value = "";
      }
    }

    // Envoie un événement "filtre-supprime" pour supprimer les tags d'Awesomplete
    document
      .getElementById("filtre")
      .dispatchEvent(new Event("filtre-supprime", { bubbles: true }));

    colorationFiltre();

    raffraichirPlanning();
  });
