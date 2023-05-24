import { readable } from "svelte/store";

export const breakpoints = [
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
 * Classe permettant de connaître le type d'appareil utilisé pour la navigation.
 */
export class Device {
  /**
   * Renvoie le type d'appareil en se basant sur la taille de l'écran.
   */
  get type() {
    return breakpoints.find((breakpoint) => {
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
    return breakpoints.find((breakpoint) => {
      return (
        window.innerWidth >= breakpoint.lowerBound &&
        window.innerWidth <= breakpoint.upperBound
      );
    }).type;
  }

  /**
   * Renvoie `true` si l'appareil est du même type que le type d'appareil passé en paramètre.
   *
   * @param type Type d'appareil
   */
  is(type: typeof this.type) {
    return this.type === type;
  }

  /**
   * Renvoie `true` si l'appareil est plus petit que le type d'appareil passé en paramètre.
   *
   * @param type Type d'appareil
   */
  isSmallerThan(type: typeof this.type) {
    return breakpoints
      .filter(
        (breakpoint) =>
          breakpoint.upperBound <
          breakpoints.find((breakpoint) => breakpoint.type === type).lowerBound
      )
      .map((breakpoint) => breakpoint.type)
      .includes(this.type);
  }

  /**
   * Renvoie `true` si l'appareil est plus grand que le type d'appareil passé en paramètre.
   *
   * @param type Type d'appareil
   */
  isLargerThan(type: typeof this.type) {
    return breakpoints
      .filter(
        (breakpoint) =>
          breakpoint.lowerBound >
          breakpoints.find((breakpoint) => breakpoint.type === type).upperBound
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
