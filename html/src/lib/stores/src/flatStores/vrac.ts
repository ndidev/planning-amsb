import { createFlatStore } from "../generics/flatStore";
import type { RdvVrac, ProduitVrac } from "@app/types";
import type { FetcherOptions } from "@app/utils";

/**
 * Store RDVs vrac.
 */
export const vracRdvs = (
  params: FetcherOptions["searchParams"] = new URLSearchParams()
) =>
  createFlatStore<RdvVrac>("vrac/rdvs", {
    params,
    satisfiesParams,
    additionalEvents: ["vrac/produits"],
  });

function satisfiesParams(rdv: RdvVrac, searchParams: URLSearchParams) {
  const archives = "archives" in Object.fromEntries(searchParams);

  return rdv.archive === archives;
}

/**
 * Store produits vrac.
 */
export const vracProduits = createFlatStore<ProduitVrac>("vrac/produits");
