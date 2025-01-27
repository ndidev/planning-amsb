export class NumberUtils {
  static stringifyTime(time: number): string {
    const hours = Math.floor(time);
    const minutes = Math.round((time - hours) * 60);

    return `${hours}h${minutes.toString().padStart(2, "0")}`;
  }

  static formatTonnage(tonnage: number, forceSign = false): string {
    return (
      (forceSign && tonnage >= 0 ? "+" : "") +
      tonnage.toLocaleString("fr-FR", {
        minimumFractionDigits: 3,
      }) +
      " MT"
    );
  }

  static formatVolume(volume: number, forceSign = false): string {
    return (
      (forceSign && volume >= 0 ? "+" : "") +
      volume.toLocaleString("fr-FR", {
        minimumFractionDigits: 3,
      }) +
      " m³"
    );
  }

  static formatUnits(
    units: number,
    forceSign = false,
    unit: string = "colis"
  ): string {
    return (
      (forceSign && units >= 0 ? "+" : "") +
      units.toLocaleString("fr-FR") +
      " " +
      unit
    );
  }

  static getQuantityColor(quantity: number): string {
    if (quantity > 0) {
      return "text-success-500";
    } else if (quantity < 0) {
      return "text-error-500";
    } else {
      return "text-inherit";
    }
  }
}
