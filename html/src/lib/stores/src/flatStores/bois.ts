import { createFlatStore } from "../generics/flatStore";
import type { RdvBois, FiltreBois } from "@app/types";
import { DateUtils, type FetcherOptions } from "@app/utils";

/**
 * Store RDVs bois.
 */
export const boisRdvs = (
  params: FetcherOptions["searchParams"] = new URLSearchParams()
) => createFlatStore<RdvBois>("bois/rdvs", { params, satisfiesParams });

function satisfiesParams(rdv: RdvBois, searchParams: URLSearchParams) {
  const filtre: { [P in keyof FiltreBois]: string } =
    Object.fromEntries(searchParams);

  return (
    rdv.attente ||
    ((filtre.date_debut ?? new DateUtils().toLocaleISODateString()) <=
      rdv.date_rdv &&
      (filtre.date_fin ?? "9") >= rdv.date_rdv &&
      (filtre.fournisseur?.split(",").includes(rdv.fournisseur.toString()) ??
        true) &&
      (filtre.client?.split(",").includes(rdv.client.toString()) ?? true) &&
      (filtre.chargement?.split(",").includes(rdv.chargement.toString()) ??
        true) &&
      (filtre.livraison?.split(",").includes(rdv.livraison.toString()) ??
        true) &&
      (filtre.transporteur?.split(",").includes(rdv.transporteur.toString()) ??
        true) &&
      (filtre.affreteur?.split(",").includes(rdv.affreteur.toString()) ?? true))
  );
}
