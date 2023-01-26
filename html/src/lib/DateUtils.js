/**
 * Bibliothèque de fonctions permettant de :
 *  - vérifier les jours ouvrés
 *  - convertir les dates au format voulu
 *  - récupérer les jours ouvrés précédant et suivant les jours fériés et weekends
 * Utilisé dans les différents planning et l'envoi PDF
 */
export class DateUtils {
  /**
   * Convertit une date (string) en objet Date.
   *
   * Vérifie que la date passée en paramètre est bien au format 'jj/mm/aaaa' (format datepicker).
   *
   * @param {string} date Date à formatter
   *
   * @returns {Date} Date
   */
  static convertirDate(date) {
    if (date instanceof Date) {
      return date;
    }

    if (!date) {
      return new Date();
    }

    if (!date.includes("/")) {
      throw new Error("La date passée n'est pas au format jj/mm/aaaa");
    }

    const [jour, mois, annee] = date.split("/");

    return new Date(annee, mois - 1, jour);
  }

  /**
   * Renvoie la date de Pâques pour une année.
   *
   * @param {int} annee Année désirée (4 chiffres)
   *
   * @returns {Date} Objet Date avec la date de Pâques
   */
  static calculerPaques(annee) {
    const Y = annee;
    const C = Math.floor(Y / 100);
    const N = Y % 19;
    const K = Math.floor((C - 17) / 25);
    let I =
      (C - Math.floor(C / 4) - Math.floor((C - K) / 3) + 19 * N + 15) % 30;
    I =
      I -
      Math.floor(I / 28) *
        (1 -
          Math.floor(I / 28) *
            Math.floor(29 / (I + 1)) *
            Math.floor((21 - N) / 11));
    const J = (Y + Math.floor(Y / 4) + I + 2 - C + Math.floor(C / 4)) % 7;
    const L = I - J;
    const M = 3 + Math.floor((L + 40) / 44);
    const D = L + 28 - 31 * Math.floor(M / 4);

    return new Date(Y, M - 1, D);
  }

  /**
   * Construit la liste des jours fériés français pour une année.
   *
   * @see calculerPaques
   *
   * @param {number} annee Année pour laquelle calculer les jours fériés
   *
   * @returns {Object} Liste des jours fériés
   */
  static construireListeJoursFeries(annee) {
    const nouvel_an = new Date(annee, 0, 1);
    const paques = this.calculerPaques(annee);
    const lundi_paques = new Date(
      paques.getFullYear(),
      paques.getMonth(),
      paques.getDate() + 1
    );
    const fete_travail = new Date(annee, 4, 1);
    const victoire_1945 = new Date(annee, 4, 8);
    const ascension = new Date(
      paques.getFullYear(),
      paques.getMonth(),
      paques.getDate() + 39
    );
    const pentecote = new Date(
      paques.getFullYear(),
      paques.getMonth(),
      paques.getDate() + 49
    );
    const lundi_pentecote = new Date(
      paques.getFullYear(),
      paques.getMonth(),
      paques.getDate() + 50
    );
    const fete_nationale = new Date(annee, 6, 14);
    const assomption = new Date(annee, 7, 15);
    const toussaint = new Date(annee, 10, 1);
    const armistice_1918 = new Date(annee, 10, 11);
    const noel = new Date(annee, 11, 25);

    return {
      nouvel_an,
      paques,
      lundi_paques,
      fete_travail,
      victoire_1945,
      ascension,
      pentecote,
      lundi_pentecote,
      fete_nationale,
      assomption,
      toussaint,
      armistice_1918,
      noel,
    };
  }

  /**
   * Vérifie si une date est un jour férié.
   *
   * @see construireListeJoursFeries
   *
   * @param {Date} date Date à vérifier
   *
   * @returns {boolean} true si jour férié, false sinon
   */
  static verifierJourFerie(date) {
    const annee = date.getFullYear();

    const feries = this.construireListeJoursFeries(annee);

    // Vérification du jour férié
    for (const jour_ferie of Object.values(feries)) {
      if (date.valueOf() === jour_ferie.valueOf()) {
        return true;
      }
    }

    return false;
  }

  /**
   * Vérifie si la date passée en paramètre est un jour ouvré.
   *
   * @see verifierJourFerie
   *
   * @param {Date} date Date à vérifier
   *
   * @returns {boolean} true si jour ouvré, false si jour chômé.
   */
  static verifierJourOuvre(date) {
    if (this.verifierJourFerie(date)) {
      return false;
    }

    const day = date.getDay(); // renvoie le chiffre du jour (0 = dimanche ... 6 = samedi)

    switch (day) {
      case 6: // samedi
      case 0: // dimanche
        return false;
      default:
        return true;
    }
  }

  /**
   * Retourne le nombreJours_ième jour ouvré avant la date entrée en paramètre.
   *
   * La fonction décale d'un jour en arrière
   * et vérifie à chaque fois si le nouveau jour est ouvré ou non.
   * Si oui, retourne la nouvelle date.
   * Si non, nouvelle itération.
   *
   * Exemple : retourne le jeudi si la date entrée est un samedi avec nombreJours = '2'
   * (en ne supposant aucun jour férié)
   *
   * @param {Date} date        Date.
   * @param {int}  nombreJours Optionnel. Nombre de jours avant date. Défaut = 1
   *
   * @returns {Date}  Date
   */
  static jourOuvrePrecedent(date, nombreJours = 1) {
    let jourOuvrePrecedent = new Date(date);

    for (let i = 0; i < nombreJours; i++) {
      do {
        jourOuvrePrecedent = new Date(
          jourOuvrePrecedent.getFullYear(),
          jourOuvrePrecedent.getMonth(),
          jourOuvrePrecedent.getDate() - 1
        );
      } while (!this.verifierJourOuvre(jourOuvrePrecedent));
    }

    return jourOuvrePrecedent;
  }

  /**
   * Retourne le nombreJours_ième jour ouvré après la date entrée en paramètre.
   *
   * La fonction décale d'un jour en arrière
   * et vérifie à chaque fois si le nouveau jour est ouvré ou non.
   * Si oui, retourne la nouvelle date.
   * Si non, nouvelle itération.
   *
   * Exemple : retourne le mardi si la date entrée est un samedi avec nombreJours = '2'
   * (en ne supposant aucun jour férié)
   *
   * @param {Date} date        Date.
   * @param {int}  nombreJours Optionnel. Nombre de jours après date. Défaut = 1
   *
   * @returns {Date}  Date
   */
  static jourOuvreSuivant(date, nombreJours = 1) {
    let jourOuvreSuivant = new Date(date);

    for (let i = 0; i < nombreJours; i++) {
      do {
        jourOuvreSuivant = new Date(
          jourOuvreSuivant.getFullYear(),
          jourOuvreSuivant.getMonth(),
          jourOuvreSuivant.getDate() + 1
        );
      } while (!this.verifierJourOuvre(jourOuvreSuivant));
    }

    return jourOuvreSuivant;
  }
}
