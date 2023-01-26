import Notiflix from "notiflix";

import env from "../lib/environment";
import { awesompleteTiers } from "../lib/awesompleteTiers";

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
 * Raffraichir la liste des RDVs.
 */
async function raffraichirStats() {
  try {
    const stats = await recupererStats();
    afficherStats(stats);
  } catch (err) {
    throw err;
  }
}

/**
 * Récupère les statistiques par année.
 *
 * @returns {Promise} Statistiques au format JSON
 */
async function recupererStats() {
  const url = new URL(env.api);
  url.pathname += "bois/stats";

  const params = JSON.parse(localStorage.getItem("filtre-stats-bois"));

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
 * Afficher les statistiques.
 *
 * @param {Object} stats Statistiques
 */
function afficherStats(stats) {
  const div_stats = document.getElementById("statistiques");

  // Si aucun RDV sur la période
  if (!stats) {
    div_stats.textContent = "Aucun RDV trouvé";
    return;
  }

  /**
   * Crée un tableau avec les données de l'objet.
   *
   * @param {Object} stats
   *
   * @returns {HTMLTableElement}
   */
  function creerTableau(stats) {
    const stats_par_annee = stats["Par année"];

    // En-tête lignes (années)
    const en_tete_lignes = [];
    for (const annee of Object.keys(stats_par_annee)) {
      en_tete_lignes.push(annee);
    }

    const en_tete_colonnes = [
      { affiche: "", data: null },
      { affiche: "Janvier", data: 1 },
      { affiche: "Février", data: 2 },
      { affiche: "Mars", data: 3 },
      { affiche: "Avril", data: 4 },
      { affiche: "Mai", data: 5 },
      { affiche: "Juin", data: 6 },
      { affiche: "Juillet", data: 7 },
      { affiche: "Août", data: 8 },
      { affiche: "Septembre", data: 9 },
      { affiche: "Octobre", data: 10 },
      { affiche: "Novembre", data: 11 },
      { affiche: "Décembre", data: 12 },
      { affiche: "Total", data: "total" },
    ];

    // thead (mois)
    const thead = document.createElement("thead");
    (() => {
      const tr = document.createElement("tr");
      for (const en_tete of en_tete_colonnes) {
        const th = document.createElement("th");
        th.scope = "col";
        th.textContent = en_tete.affiche;
        tr.appendChild(th);
      }
      thead.appendChild(tr);
    })();

    // tbody
    const tbody = document.createElement("tbody");
    (() => {
      en_tete_lignes.forEach((en_tete_ligne) => {
        const tr = document.createElement("tr");
        let total = 0;
        en_tete_colonnes.forEach((en_tete_colonne) => {
          if (en_tete_colonne.data === null) {
            // Année
            const th = document.createElement("th");
            th.scope = "row";
            th.textContent = en_tete_ligne;
            tr.appendChild(th);
          } else if (en_tete_colonne.data === "total") {
            // Colonne "Total"
            const td = document.createElement("td");
            td.classList.add("total");
            td.textContent = total.toLocaleString("fr-FR");
            tr.appendChild(td);
          } else {
            // Nombre de camions
            const td = document.createElement("td");
            const annee = en_tete_ligne;
            const mois = en_tete_colonne.data;
            const valeur = stats_par_annee[annee][mois];
            td.textContent = valeur.toLocaleString("fr-FR") || "";
            tr.appendChild(td);
            total += valeur;
          }
        });
        tbody.appendChild(tr);
      });
    })();

    // tfoot (moyennes + total général)
    const tfoot = document.createElement("tfoot");
    (() => {
      const tr = document.createElement("tr");

      en_tete_colonnes.forEach((en_tete_colonne) => {
        // Première colonne
        if (en_tete_colonne.data === null) {
          const th = document.createElement("th");
          th.textContent = "Moyenne";
          tr.appendChild(th);
          return;
        }

        // Total général
        if (en_tete_colonne.data === "total") {
          const td = document.createElement("td");
          td.id = "total_general";
          td.classList.add("total");
          td.textContent = stats.Total.toLocaleString("fr-FR");
          tr.appendChild(td);
          return;
        }

        // Moyenne pour chaque colonne
        const valeurs = [];
        en_tete_lignes.forEach((annee) => {
          if (!annee) return;

          const mois = en_tete_colonne.data;

          const valeur = stats_par_annee[annee][mois];
          if (valeur) valeurs.push(valeur);
        });

        const moyenne =
          valeurs.reduce((total, valeur) => total + valeur, 0) /
          (valeurs.length || 1);

        const td = document.createElement("td");
        td.textContent = Math.round(moyenne).toLocaleString("fr-FR");
        tr.appendChild(td);
      });

      tfoot.appendChild(tr);
    })();

    // Tableau final
    const table = document.createElement("table");
    table.appendChild(thead);
    table.appendChild(tbody);
    table.appendChild(tfoot);

    return table;
  }

  div_stats.innerHTML = null;
  div_stats.appendChild(creerTableau(stats));
}

/**
 * Awesomplete sur les champs du filtre
 * (autorisation saisie multiple)
 */
(() => {
  const filtre_enregistre = JSON.parse(
    localStorage.getItem("filtre-stats-bois")
  );

  filtres_value.date_debut.value = filtre_enregistre?.date_debut;
  filtres_value.date_fin.value = filtre_enregistre?.date_fin;

  awesompleteTiers("#filtre_fournisseur", {
    role: "bois_fournisseur",
    role_affichage: "fournisseur bois",
    valeur_initiale: filtre_enregistre?.fournisseur,
    context: document,
    tags: true,
  });

  awesompleteTiers("#filtre_client", {
    role: "bois_client",
    role_affichage: "client bois",
    valeur_initiale: filtre_enregistre?.client,
    context: document,
    tags: true,
  });

  awesompleteTiers("#filtre_chargement", {
    role: "bois_client",
    role_affichage: "lieu de chargement bois",
    valeur_initiale: filtre_enregistre?.chargement,
    context: document,
    tags: true,
  });

  awesompleteTiers("#filtre_livraison", {
    role: "bois_client",
    role_affichage: "lieu de livraison bois",
    valeur_initiale: filtre_enregistre?.livraison,
    context: document,
    tags: true,
  });

  awesompleteTiers("#filtre_transporteur", {
    role: "bois_transporteur",
    role_affichage: "transporteur bois",
    valeur_initiale: filtre_enregistre?.transporteur,
    context: document,
    tags: true,
  });

  awesompleteTiers("#filtre_affreteur", {
    role: "bois_affreteur",
    role_affichage: "affreteur bois",
    valeur_initiale: filtre_enregistre?.affreteur,
    context: document,
    tags: true,
  });
})();

/**
 * Afficher les statistiques et enregistrement du filtre.
 */
document.querySelector("button[name='afficher']").onclick = async (e) => {
  e.preventDefault();

  const donnees_filtre = {};
  let filtre_actif = false;

  for (const [key, input] of Object.entries(filtres_value)) {
    donnees_filtre[key] = input.value;
    if (input.value) filtre_actif = true;
  }

  if (filtre_actif) {
    localStorage.setItem("filtre-stats-bois", JSON.stringify(donnees_filtre));
  } else {
    localStorage.removeItem("filtre-stats-bois");
  }

  try {
    Notiflix.Block.circle("#filtre", "Récupération des données en cours...");
    await raffraichirStats();
  } catch (err) {
    console.error(err);
    Notiflix.Notify.failure("Erreur dans la récupération des données");
  } finally {
    Notiflix.Block.remove("#filtre");
  }
};

/**
 * Bouton "Effacer"
 */
document.querySelector("button[name='effacer']").onclick = (e) => {
  e.preventDefault();

  document.getElementById("statistiques").innerHTML = null;

  localStorage.removeItem("filtre-stats-bois");

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
};
