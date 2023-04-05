/**
 * Classe permettant de connaître le type d'appareil utilisé pour la navigation.
 */
export class Device {
  static breakpoints = [
    {
      type: "mobile",
      lowerBound: 0,
      upperBound: 767,
    },
    {
      type: "desktop",
      lowerBound: 768,
      upperBound: Infinity,
    },
  ] as const;

  /**
   * Renvoie le type d'appareil en se basant sur la taille de l'écran.
   */
  static get type() {
    return this.breakpoints.find((breakpoint) => {
      return (
        document.body.offsetWidth >= breakpoint.lowerBound &&
        document.body.offsetWidth <= breakpoint.upperBound
      );
    }).type;
  }

  /**
   * Renvoie `true` si le type d'appareil passé en paramètre est correct.
   *
   * @param type Type d'appareil
   */
  static is(type: typeof Device.type) {
    return this.type === type;
  }

  /**
   * Renvoie `true` si le type d'appareil passé en paramètre est correct.
   *
   * @param type Type d'appareil
   */
  static isSmallerThan(type: typeof Device.type) {
    return this.breakpoints
      .filter(
        (breakpoint) =>
          breakpoint.upperBound <
          this.breakpoints.find((breakpoint) => breakpoint.type === type)
            .lowerBound
      )
      .map((breakpoint) => breakpoint.type)
      .includes(this.type);
  }

  /**
   * Renvoie `true` si le type d'appareil passé en paramètre est correct.
   *
   * @param type Type d'appareil
   */
  static isLargerThan(type: typeof Device.type) {
    return this.breakpoints
      .filter(
        (breakpoint) =>
          breakpoint.lowerBound >
          this.breakpoints.find((breakpoint) => breakpoint.type === type)
            .upperBound
      )
      .map((breakpoint) => breakpoint.type)
      .includes(this.type);
  }
}
