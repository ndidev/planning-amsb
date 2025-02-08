import { createFlatStore } from "../generics/flatStore";
import type { RdvBois, TimberFilter } from "@app/types";
import { DateUtils } from "@app/utils";

/**
 * Store RDVs bois.
 */
export const boisRdvs = createFlatStore<RdvBois>(
  "bois/rdvs",
  {
    id: null,
    attente: false,
    date_rdv: new Date().toISOString().split("T")[0],
    heure_arrivee: null,
    heure_depart: null,
    fournisseur: null,
    chargement: 1, // AMSB
    client: null,
    livraison: null,
    transporteur: null,
    affreteur: null,
    commande_prete: false,
    confirmation_affretement: false,
    numero_bl: "",
    commentaire_public: "",
    commentaire_cache: "",
    dispatch: [],
  },
  { satisfiesParams }
);

function satisfiesParams(appointment: RdvBois, searchParams: URLSearchParams) {
  const filter: { [P in keyof TimberFilter]: string } =
    Object.fromEntries(searchParams);

  const appointmentIsOnHold = appointment.attente;

  const startDateMatches =
    (filter.date_debut ?? new DateUtils().toLocaleISODateString()) <=
    appointment.date_rdv;

  const endDateMatches = (filter.date_fin ?? "9") >= appointment.date_rdv;

  const supplierMatches =
    filter.fournisseur
      ?.split(",")
      .includes(appointment.fournisseur.toString()) ?? true;

  const customerMatches =
    filter.client?.split(",").includes(appointment.client.toString()) ?? true;

  const loadingPlaceMatches =
    filter.chargement?.split(",").includes(appointment.chargement.toString()) ??
    true;

  const deliveryPlaceMatches =
    filter.livraison?.split(",").includes(appointment.livraison.toString()) ??
    true;

  const carrierMatches =
    filter.transporteur
      ?.split(",")
      .includes(appointment.transporteur.toString()) ?? true;

  const chartererMatches =
    filter.affreteur?.split(",").includes(appointment.affreteur.toString()) ??
    true;

  return (
    appointmentIsOnHold ||
    (startDateMatches &&
      endDateMatches &&
      supplierMatches &&
      customerMatches &&
      loadingPlaceMatches &&
      deliveryPlaceMatches &&
      carrierMatches &&
      chartererMatches)
  );
}
