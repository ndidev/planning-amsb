import { createFlatStore } from "./generics/flatStore";
import type { RdvVrac, ProduitVrac } from "@app/types";

/**
 * Store RDVs vrac.
 */
export const vracRdvs = createFlatStore<RdvVrac>("vrac/rdvs", {
  additionalEvents: ["vrac/produits"],
});

/**
 * Store produits vrac.
 */
export const vracProduits = createFlatStore<ProduitVrac>("vrac/produits");
