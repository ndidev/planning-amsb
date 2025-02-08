/**
 * Calcul automatique de la couleur de texte.
 *
 * La couleur du texte des éléments sélectionnés est calculée
 * en fonction de la couleur d'arrière-plan.
 *
 * Le texte est noir ou blanc.
 */
export const luminance = Object.freeze({
  /**
   * Renvoie la couleur du texte en fonction de la couleur d'arrière-plan.
   *
   * @param {string} backgroundColor Couleur d'arrière-plan du texte (format `rgb(r,g,b)`).
   *
   * @return {("white"|"black")} Couleur du texte
   */
  getTextColor(backgroundColor: string): "white" | "black" {
    // Extraction des composantes
    const [r, g, b] = this.extractRGB(backgroundColor) || [255, 255, 255];

    // Calcul de la luminance perçue
    const luminance = 1 - (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    // Choix de la couleur du texte (noir/blanc) en fonction de la luminance
    const textColor = luminance < 0.5 ? "black" : "white";

    return textColor;
  },

  /**
   * Extracts the red, green, and blue components of a color.
   *
   * @param {string} color `#RRGGBB` or `rgb(r,g,b)` format
   *
   * @return {number[]|undefined} Array with `[r,g,b]` values or undefined if unknown color format as parameter
   */
  extractRGB(color: string): number[] | undefined {
    // RGB
    const rgbRegex = new RegExp(/rgb\((\d{1,3}), ?(\d{1,3}), ?(\d{1,3})\)/);
    const rgbComponents = rgbRegex.exec(color);
    if (rgbComponents) {
      return [
        parseInt(rgbComponents[1]),
        parseInt(rgbComponents[2]),
        parseInt(rgbComponents[3]),
      ];
    }

    // Hexadecimal
    const hexRegex = new RegExp(/#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})/);
    const hexComponents = hexRegex.exec(color);
    if (hexComponents) {
      return [
        parseInt(hexComponents[1], 16),
        parseInt(hexComponents[2], 16),
        parseInt(hexComponents[3], 16),
      ];
    }

    // HSL
    const hslRegex = new RegExp(/hsla?\((\d{1,3}), ?(\d{1,3})%, ?(\d{1,3})%/);
    const hslComponents = hslRegex.exec(color);
    if (hslComponents) {
      const h = parseInt(hslComponents[1]) / 360;
      const s = parseInt(hslComponents[2]) / 100;
      const l = parseInt(hslComponents[3]) / 100;

      // Conversion HSL to RGB
      const c = (1 - Math.abs(2 * l - 1)) * s;
      const x = c * (1 - Math.abs(((h * 6) % 2) - 1));
      const m = l - c / 2;

      let r = 0;
      let g = 0;
      let b = 0;

      if (h < 1 / 6) {
        r = c;
        g = x;
      } else if (h < 2 / 6) {
        r = x;
        g = c;
      } else if (h < 3 / 6) {
        g = c;
        b = x;
      } else if (h < 4 / 6) {
        g = x;
        b = c;
      } else if (h < 5 / 6) {
        r = x;
        b = c;
      } else {
        r = c;
        b = x;
      }

      return [
        Math.round((r + m) * 255),
        Math.round((g + m) * 255),
        Math.round((b + m) * 255),
      ];
    }

    // Unknown format
    return undefined;
  },
});
