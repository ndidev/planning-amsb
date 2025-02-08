export class Filter<
  T extends { [k: string]: (string | number | boolean) | string[] | number[] }
> {
  data: T;

  constructor(data: T) {
    this.data = structuredClone(data);

    for (const [key, value] of Object.entries(data)) {
      this[key] = value;
    }
  }

  /**
   * Convertir les données du filtre en paramètres de requête.
   */
  toSearchParams() {
    const params = {};

    for (let [key, value] of Object.entries(this.data)) {
      value = Array.isArray(value) ? value.join(",") : String(value || "");

      if (value) {
        params[key] = value;
      }
    }

    return new URLSearchParams(params);
  }

  /**
   * Renvoie `true` si le filtre contient des données.
   */
  hasData() {
    return Object.entries(this.data).length > 0;
  }
}
