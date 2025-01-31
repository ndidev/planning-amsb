import { createFlatStore } from "../generics/flatStore";
import type { RdvVrac, ProduitVrac, BulkPlanningFilter } from "@app/types";
import { DateUtils } from "@app/utils";

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
    satisfiesParams: satisfiesAppointmentParams,
    additionalEvents: ["vrac/produits"],
  }
);

function satisfiesAppointmentParams(
  appointment: RdvVrac,
  searchParams: URLSearchParams
) {
  const filter: { [P in keyof BulkPlanningFilter]: string } =
    Object.fromEntries(searchParams);

  const startDateMatches =
    (filter.date_debut ?? new DateUtils().toLocaleISODateString()) <=
    appointment.date_rdv;

  const endDateMatches = (filter.date_fin ?? "9") >= appointment.date_rdv;

  const productMatches =
    filter.produit?.split(",").includes(appointment.produit.toString()) ?? true;

  const qualityMatches =
    filter.qualite?.split(",").includes(appointment.qualite?.toString()) ??
    true;

  const supplierMatches =
    filter.fournisseur
      ?.split(",")
      .includes(appointment.fournisseur.toString()) ?? true;

  const customerMatches =
    filter.client?.split(",").includes(appointment.client.toString()) ?? true;

  const carrierMatches =
    filter.transporteur
      ?.split(",")
      .includes(appointment.transporteur.toString()) ?? true;

  const archiveMatches =
    appointment.archive === (filter.archives === "true" ? true : false);

  return (
    startDateMatches &&
    endDateMatches &&
    productMatches &&
    qualityMatches &&
    supplierMatches &&
    customerMatches &&
    carrierMatches &&
    archiveMatches
  );
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
