import { createFlatStore } from "../generics/flatStore";
import type { Charter } from "@app/types";

/**
 * Store affrètements maritimes.
 */
export const charteringCharters = createFlatStore<Charter>(
  "chartering/charters",
  {
    id: null,
    statut: 0,
    lc_debut: null,
    lc_fin: null,
    cp_date: null,
    navire: "TBN",
    affreteur: null,
    armateur: null,
    courtier: null,
    fret_achat: null,
    fret_vente: null,
    surestaries_achat: null,
    surestaries_vente: null,
    legs: [],
    commentaire: "",
    archive: false,
  },
  {
    satisfiesParams,
  }
);

function satisfiesParams(charter: Charter, params: URLSearchParams) {
  const archives = "archives" in Object.fromEntries(params);

  return charter.archive === archives;
}
