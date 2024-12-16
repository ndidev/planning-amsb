import { createFlatStore } from "../generics/flatStore";
import type { RdvBois, TimberFilter } from "@app/types";
import { DateUtils, type FetcherOptions } from "@app/utils";

/**
 * Store RDVs bois.
 */
export const boisRdvs = (
  params: FetcherOptions["searchParams"] = new URLSearchParams()
) => createFlatStore<RdvBois>("bois/rdvs", null, { params, satisfiesParams });

function satisfiesParams(appointment: RdvBois, searchParams: URLSearchParams) {
  const filter: { [P in keyof TimberFilter]: string } =
    Object.fromEntries(searchParams);

  return (
    appointment.attente ||
    ((filter.date_debut ?? new DateUtils().toLocaleISODateString()) <=
      appointment.date_rdv &&
      (filter.date_fin ?? "9") >= appointment.date_rdv &&
      (filter.fournisseur
        ?.split(",")
        .includes(appointment.fournisseur.toString()) ??
        true) &&
      (filter.client?.split(",").includes(appointment.client.toString()) ??
        true) &&
      (filter.chargement
        ?.split(",")
        .includes(appointment.chargement.toString()) ??
        true) &&
      (filter.livraison
        ?.split(",")
        .includes(appointment.livraison.toString()) ??
        true) &&
      (filter.transporteur
        ?.split(",")
        .includes(appointment.transporteur.toString()) ??
        true) &&
      (filter.affreteur
        ?.split(",")
        .includes(appointment.affreteur.toString()) ??
        true))
  );
}
