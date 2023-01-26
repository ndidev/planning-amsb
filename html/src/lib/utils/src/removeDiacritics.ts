/**
 * Remove diacritics from a string.
 *
 * @link https://www.davidbcalhoun.com/2019/matching-accented-strings-in-javascript/
 *
 * @param {string} string String with diacritics
 *
 * @returns {string} String without diacritics
 */
export function removeDiacritics(string: string): string {
  return string.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}
