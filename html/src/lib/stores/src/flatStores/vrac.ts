import { createFlatStore } from "../generics/flatStore";
import type { RdvVrac, ProduitVrac } from "@app/types";

/**
 * Store RDVs vrac.
 */
export const vracRdvs = createFlatStore<RdvVrac>(
  "vrac/rdvs",
  {
    id: null,
    date_rdv: new Date().toISOString().split("T")[0],
    heure: "",
    produit: null,
    qualite: null,
    quantite: 0,
    max: false,
    commande_prete: false,
    fournisseur: null,
    client: null,
    transporteur: null,
    num_commande: "",
    commentaire_public: "",
    commentaire_prive: "",
    showOnTv: true,
    archive: false,
    dispatch: [],
  },
  {
    satisfiesParams,
    additionalEvents: ["vrac/produits"],
  }
);

function satisfiesParams(rdv: RdvVrac, searchParams: URLSearchParams) {
  const archives = searchParams.has("archives");

  return rdv.archive === archives;
}

/**
 * Store produits vrac.
 */
export const vracProduits = createFlatStore<ProduitVrac>("vrac/produits", {
  id: null,
  nom: "",
  couleur: "#000000",
  unite: "",
  qualites: [],
});
