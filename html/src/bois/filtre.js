import { raffraichirPlanning } from "./index.js";
import { awesompleteTiers } from "../lib/awesompleteTiers.js";

const filtres_value = {
  date_debut: document.getElementById("date_debut"),
  date_fin: document.getElementById("date_fin"),
  fournisseur: document.getElementById("filtre_fournisseur_value"),
  client: document.getElementById("filtre_client_value"),
  chargement: document.getElementById("filtre_chargement_value"),
  livraison: document.getElementById("filtre_livraison_value"),
  transporteur: document.getElementById("filtre_transporteur_value"),
  affreteur: document.getElementById("filtre_affreteur_value"),
};

const filtres_user = {
  fournisseur: document.getElementById("filtre_fournisseur_user"),
  client: document.getElementById("filtre_client_user"),
  chargement: document.getElementById("filtre_chargement_user"),
  livraison: document.getElementById("filtre_livraison_user"),
  transporteur: document.getElementById("filtre_transporteur_user"),
  affreteur: document.getElementById("filtre_affreteur_user"),
};

/**
 * Affichage/Masquage du filtre
 */
(() => {
  const filtre_enregistre = JSON.parse(
    localStorage.getItem("filtre-planning-bois")
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
    localStorage.getItem("filtre-planning-bois")
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
    localStorage.getItem("filtre-planning-bois")
  );

  filtres_value.date_debut.value = filtre_enregistre?.date_debut;
  filtres_value.date_fin.value = filtre_enregistre?.date_fin;

  awesompleteTiers("#filtre_fournisseur", {
    role: "bois_fournisseur",
    role_affichage: "fournisseur bois",
    valeur_initiale: filtre_enregistre?.fournisseur,
    context: document,
    tags: true,
    actifs: false,
  });

  awesompleteTiers("#filtre_client", {
    role: "bois_client",
    role_affichage: "client bois",
    valeur_initiale: filtre_enregistre?.client,
    context: document,
    tags: true,
    actifs: false,
  });

  awesompleteTiers("#filtre_chargement", {
    role: "bois_client",
    role_affichage: "lieu de chargement bois",
    valeur_initiale: filtre_enregistre?.chargement,
    context: document,
    tags: true,
    actifs: false,
  });

  awesompleteTiers("#filtre_livraison", {
    role: "bois_client",
    role_affichage: "lieu de livraison bois",
    valeur_initiale: filtre_enregistre?.livraison,
    context: document,
    tags: true,
    actifs: false,
  });

  awesompleteTiers("#filtre_transporteur", {
    role: "bois_transporteur",
    role_affichage: "transporteur bois",
    valeur_initiale: filtre_enregistre?.transporteur,
    context: document,
    tags: true,
    actifs: false,
  });

  awesompleteTiers("#filtre_affreteur", {
    role: "bois_affreteur",
    role_affichage: "affreteur bois",
    valeur_initiale: filtre_enregistre?.affreteur,
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
      "filtre-planning-bois",
      JSON.stringify(donnees_filtre)
    );
  } else {
    localStorage.removeItem("filtre-planning-bois");
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

    localStorage.removeItem("filtre-planning-bois");

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
