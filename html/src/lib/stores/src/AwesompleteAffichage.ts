import { readable } from "svelte/store";

export const AwesompleteAffichage = readable({
  NOM_COURT: 2 ** 1,
  VILLE: 2 ** 2,
  PAYS: 2 ** 3,
});
