/**
 * Crée un objet Date.
 *
 * @param {Object} escale Données de l'escale (JSON)
 * @param {string} etx    Type de l'ET ('eta', 'etb', 'etc', 'etd')
 *
 * @return {Date} Date au format 'YYY-MM-DDTHH:MM'
 */
function creationDate(escale, etx) {
  let date = escale[`${etx}_date`] || "2000-01-01"; // Date mise au format 'YYYY-MM-DD'
  let heure = escale[`${etx}_heure`];
  let regexp_heure = /^((([01][0-9]|[2][0-3]):[0-5][0-9])|24:00)/;
  if (!regexp_heure.test(heure)) {
    heure = "00:00"; // Si heure non renseignée
  } else {
    heure = heure.substring(0, 5);
  }
  return new Date(date + "T" + heure); // Date au format 'YYYY-MM-DDTHH:MM' ('T' pour compatibilité IE11)
}

/**
 * Assignation classe à chaque escale en fonction de la date
 * afin de mettre en forme le planning
 *
 * @param {Node}   ligne_escale   Ligne escale
 * @param {Object} donnees_escale Données de l'escale (JSON)
 */
export default function appliquerStatutEscale(ligne_escale, donnees_escale) {
  ligne_escale.classList.remove(
    "atsea",
    "arrived",
    "berthed",
    "inops",
    "completed",
    "departed"
  );

  let eta = creationDate(donnees_escale, "eta");
  let etb = creationDate(donnees_escale, "etb");
  let ops = creationDate(donnees_escale, "ops");
  let etc = creationDate(donnees_escale, "etc");
  let etd = creationDate(donnees_escale, "etd");
  let maintenant = new Date();

  if (eta.getTime() == new Date("2000-01-01T00:00").getTime()) {
    // ETA non renseigné, ne rien faire
  } else if (eta > maintenant) {
    ligne_escale.classList.add("atsea");
  } else if (etb > maintenant) {
    ligne_escale.classList.add("arrived");
  } else if (ops > maintenant) {
    ligne_escale.classList.add("berthed");
  } else if (etc > maintenant) {
    ligne_escale.classList.add("inops");
  } else if (etd > maintenant) {
    ligne_escale.classList.add("completed");
  } else if (etd < maintenant) {
    ligne_escale.classList.add("departed");
  }
}
