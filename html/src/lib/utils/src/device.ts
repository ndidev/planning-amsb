import { readable } from "svelte/store";

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
  get type() {
    return Device.breakpoints.find((breakpoint) => {
      return (
        window.innerWidth >= breakpoint.lowerBound &&
        window.innerWidth <= breakpoint.upperBound
      );
    }).type;
  }

  /**
   * Renvoie le type d'appareil en se basant sur la taille de l'écran.
   */
  static get type() {
    return this.breakpoints.find((breakpoint) => {
      return (
        window.innerWidth >= breakpoint.lowerBound &&
        window.innerWidth <= breakpoint.upperBound
      );
    }).type;
  }

  /**
   * Renvoie `true` si le type d'appareil passé en paramètre est correct.
   *
   * @param type Type d'appareil
   */
  is(type: typeof this.type) {
    return this.type === type;
  }

  /**
   * Renvoie `true` si le type d'appareil passé en paramètre est correct.
   *
   * @param type Type d'appareil
   */
  isSmallerThan(type: typeof this.type) {
    return Device.breakpoints
      .filter(
        (breakpoint) =>
          breakpoint.upperBound <
          Device.breakpoints.find((breakpoint) => breakpoint.type === type)
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
  isLargerThan(type: typeof this.type) {
    return Device.breakpoints
      .filter(
        (breakpoint) =>
          breakpoint.lowerBound >
          Device.breakpoints.find((breakpoint) => breakpoint.type === type)
            .upperBound
      )
      .map((breakpoint) => breakpoint.type)
      .includes(this.type);
  }
}

export const device = readable(new Device(), (set) => {
  let currentType = Device.type;

  window.addEventListener("resize", updateDeviceSize);

  return () => {
    window.removeEventListener("resize", updateDeviceSize);
  };

  function updateDeviceSize() {
    if (Device.type !== currentType) {
      set(new Device());
      currentType = Device.type;
    }
  }
});
