/**
 * Parses a JSON string and returns the corresponding object.
 * If parsing fails, returns a default value.
 *
 * @param json - The JSON string to parse.
 * @param defaultValue - The value to return if parsing fails. Defaults to `null`.
 * @returns The parsed object, or the default value if parsing fails.
 */
export function parseJSON(json: string, defaultValue: any = null) {
  try {
    return JSON.parse(json);
  } catch (error) {
    console.error("Erreur lors de la conversion du JSON", error);
    return defaultValue;
  }
}
