/**
 * Bibliothèque de fonctions permettant de :
 *  - vérifier les jours ouvrés
 *  - convertir les dates au format voulu
 *  - récupérer les jours ouvrés précédant et suivant les jours fériés et weekends
 * Utilisé dans les différents planning et l'envoi PDF
 */
export class DateUtils {
  date: Date;

  constructor(date: Date | string = new Date()) {
    if (typeof date === "string") date = new Date(date);

    this.date = date;
  }

  /**
   * Renvoie la date de Pâques pour une année.
   *
   * @param year Année désirée (4 chiffres)
   *
   * @returns Objet `Date` avec la date de Pâques
   */
  static getEasterSunday(year: number) {
    const Y = year;
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
   * @see getEasterSunday
   *
   * @param annee Année pour laquelle calculer les jours fériés
   *
   * @returns Liste des jours fériés
   */
  static construireListeJoursFeries(annee: number) {
    const nouvelAn = new Date(annee, 0, 1);
    const paques = this.getEasterSunday(annee);
    const lundiPaques = new Date(
      paques.getFullYear(),
      paques.getMonth(),
      paques.getDate() + 1
    );
    const feteTravail = new Date(annee, 4, 1);
    const victoire1945 = new Date(annee, 4, 8);
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
    const lundiPentecote = new Date(
      paques.getFullYear(),
      paques.getMonth(),
      paques.getDate() + 50
    );
    const feteNationale = new Date(annee, 6, 14);
    const assomption = new Date(annee, 7, 15);
    const toussaint = new Date(annee, 10, 1);
    const armistice1918 = new Date(annee, 10, 11);
    const noel = new Date(annee, 11, 25);

    return {
      nouvelAn,
      paques,
      lundiPaques,
      feteTravail,
      victoire1945,
      ascension,
      pentecote,
      lundiPentecote,
      feteNationale,
      assomption,
      toussaint,
      armistice1918,
      noel,
    };
  }

  /**
   * Vérifie si une date est un jour férié.
   *
   * @see construireListeJoursFeries
   *
   * @returns `true` si jour férié, `false` sinon
   */
  isBankHoliday() {
    const annee = this.date.getFullYear();

    const feries = DateUtils.construireListeJoursFeries(annee);

    // Vérification du jour férié
    for (const jour_ferie of Object.values(feries)) {
      if (this.date.valueOf() === jour_ferie.valueOf()) {
        return true;
      }
    }

    return false;
  }

  /**
   * Vérifie si la date passée en paramètre est un jour ouvré.
   *
   * @see isBankHoliday
   *
   * @returns `true` si jour ouvré, `false` si jour chômé.
   */
  isWorkingDay() {
    if (this.isBankHoliday()) {
      return false;
    }

    const day = this.date.getDay(); // renvoie le chiffre du jour (0 = dimanche ... 6 = samedi)

    switch (day) {
      case 6: // samedi
      case 0: // dimanche
        return false;
      default:
        return true;
    }
  }

  /**
   * Retourne le ènième jour avant/après la date entrée en paramètre.

   * @param offset Nombre de jours de déclage (+/-).
   *
   * @returns Date
   */
  offset(offset = 0) {
    return new DateUtils(
      new Date(
        this.date.getFullYear(),
        this.date.getMonth(),
        this.date.getDate() + offset
      )
    );
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
   * @param nombreJours Optionnel. Nombre de jours avant date. Défaut = 1
   *
   * @returns Date
   */
  getPreviousWorkingDay(nombreJours = 1) {
    let jourOuvrePrecedent = new Date(this.date);

    for (let i = 0; i < nombreJours; i++) {
      do {
        jourOuvrePrecedent = new DateUtils(jourOuvrePrecedent).offset(-1).date;
      } while (!new DateUtils(jourOuvrePrecedent).isWorkingDay());
    }

    return new DateUtils(jourOuvrePrecedent);
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
   * @param date        Date.
   * @param nombreJours Optionnel. Nombre de jours après date. Défaut = 1
   *
   * @returns Date
   */
  getNextWorkingDay(nombreJours = 1) {
    let jourOuvreSuivant = new Date(this.date);

    for (let i = 0; i < nombreJours; i++) {
      do {
        jourOuvreSuivant = new DateUtils(jourOuvreSuivant).offset(+1).date;
      } while (!new DateUtils(jourOuvreSuivant).isWorkingDay());
    }

    return new DateUtils(jourOuvreSuivant);
  }

  /**
   * Retourne la date au format `yyyy-mm-dd`.
   */
  toLocaleISODateString(locale: Intl.LocalesArgument = "fr-FR") {
    return this.date.toLocaleDateString(locale).split("/").reverse().join("-");
  }

  /**
   * Retourne la date au format textuel long.
   */
  toLongLocaleDateString(locale: Intl.LocalesArgument = "fr-FR") {
    return this.date.toLocaleDateString(locale, {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  /**
   * Retourne une date formattée en différent formats.
   */
  format(locale: Intl.LocalesArgument = "fr-FR") {
    return {
      iso: this.toLocaleISODateString(locale),
      short: new Date(this.date).toLocaleDateString(locale),
      long: this.toLongLocaleDateString(locale),
    };
  }

  static getMondayOfPreviousWeek() {
    const today = new Date();
    const previousWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
    const day = previousWeek.getDay();
    const diffToMonday = previousWeek.getDate() - day + (day == 0 ? -6 : 1);

    return new Date(previousWeek.setDate(diffToMonday));
  }

  static getSundayOfPreviousWeek() {
    const today = new Date();
    const previousWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
    let day = previousWeek.getDay();
    let diffToMonday = previousWeek.getDate() - day + (day == 0 ? -6 : 1);

    return new Date(previousWeek.setDate(diffToMonday + 6));
  }
}
