/**
 * Get CSS rules based on selectors or for an HTML element.
 *
 * @author Derek Ziemba <https://stackoverflow.com/users/2651894/derek-ziemba>
 * @link https://stackoverflow.com/a/40298390/9310651
 *
 * @author S.B. <https://stackoverflow.com/users/1552315/s-b>
 * @link https://stackoverflow.com/a/22638396/9310651
 *
 * @param {string|HTMLElement} selector Selector string or HTMLElement.
 *
 * @returns {CSSRule[]} Array of CSS rules for the chosen selectors or HTMLElement.
 */
// Inside closure so that the inner functions don't need regeneration on every call
export const getCssRules = (function () {
  /**
   * @param {string|HTMLElement} selector
   */
  return function (selector) {
    const sheets = Array.from(window.document.styleSheets);
    const ruleArrays = sheets.map((sheet) =>
      Array.from(sheet.rules || sheet.cssRules || [])
    );
    const allRules = ruleArrays.reduce((all, rule) => all.concat(rule), []);

    if (typeof selector === "string") {
      const selectors = split(normalize(selector), ",");

      return allRules.filter((rule) =>
        containsAny(normalize(rule.selectorText), selectors)
      );
    }

    if (typeof selector === "object") {
      selector.matches =
        selector.matches ||
        selector.webkitMatchesSelector ||
        selector.mozMatchesSelector ||
        selector.msMatchesSelector ||
        selector.oMatchesSelector;

      return allRules.filter((rule) => selector.matches(rule.selectorText));
    }
  };

  /**
   * === Helper functions ===
   */

  /**
   * Normalize symbol spacing and whitespace.
   *
   * @param {string} str Selectors string.
   *
   * @returns {string} Normalized string.
   */
  function normalize(str) {
    if (!str) return "";
    str = String(str).replace(/\s*([>~+])\s*/g, " $1 "); // Normalize symbol spacing
    return str.replace(/(\s+)/g, " ").trim(); // Normalize whitespace
  }

  /**
   * Splits and cleans the selector string.
   *
   * @param {string} str Selectors string.
   * @param {string} on  Separator.
   *
   * @returns {string[]} Array of selectors.
   */
  function split(str, on) {
    return str
      .split(on)
      .map((selector) => selector.trim())
      .filter((selector) => selector);
  }

  /**
   * Checks if a selector text is in the array of selectors.
   *
   * @param {string}   selText   Selector text.
   * @param {string[]} selectors Array of selectors.
   *
   * @returns {boolean} True if the selector text is in the "ors" array, false otherwise.
   */
  function containsAny(selText, selectors) {
    return selectors.some((selector) => selText.includes(selector));
  }
})();
