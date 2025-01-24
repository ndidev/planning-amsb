export class NumberUtils {
  static stringifyTime(time: number): string {
    const hours = Math.floor(time);
    const minutes = Math.round((time - hours) * 60);

    return `${hours}h${minutes.toString().padStart(2, "0")}`;
  }

  static formatTonnage(tonnage: number): string {
    return (
      tonnage.toLocaleString("fr-FR", {
        minimumFractionDigits: 3,
      }) + " MT"
    );
  }

  static formatVolume(volume: number): string {
    return (
      volume.toLocaleString("fr-FR", {
        minimumFractionDigits: 3,
      }) + " m³"
    );
  }

  static formatUnits(units: number, unit: string = "colis"): string {
    return units.toLocaleString("fr-FR") + " " + unit;
  }
}
