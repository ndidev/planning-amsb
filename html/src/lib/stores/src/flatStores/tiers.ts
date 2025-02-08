import { createFlatStore } from "../generics/flatStore";
import type { Tiers } from "@app/types";

/**
 * Store tiers.
 */
export const tiers = createFlatStore<Tiers>("tiers", {
  id: null,
  nom_court: "",
  nom_complet: "",
  adresse_ligne_1: "",
  adresse_ligne_2: "",
  cp: "",
  ville: "",
  pays: "",
  telephone: "",
  commentaire: "",
  roles: {
    bois_fournisseur: false,
    bois_client: false,
    bois_transporteur: false,
    bois_affreteur: false,
    vrac_fournisseur: false,
    vrac_client: false,
    vrac_transporteur: false,
    maritime_armateur: false,
    maritime_affreteur: false,
    maritime_courtier: false,
  },
  non_modifiable: false,
  lie_agence: false,
  logo: null,
  actif: true,
  nombre_rdv: 0,
});
