import { createFlatStore } from "./generics/flatStore";
import type { RdvBois, FiltreBois } from "@app/types";
import type { FetcherOptions } from "@app/utils";

/**
 * Store RDVs bois.
 */
export const boisRdvs = (
  params: FetcherOptions["params"] = new URLSearchParams()
) => createFlatStore<RdvBois>("bois/rdvs", { params, satisfiesParams });

function satisfiesParams(rdv: RdvBois, params: URLSearchParams) {
  const filtre: { [P in keyof FiltreBois]: string } =
    Object.fromEntries(params);

  return (
    (filtre.date_debut ??
      new Date().toLocaleDateString().split("/").reverse().join("-")) <=
      rdv.date_rdv &&
    (filtre.date_fin ?? "9") >= rdv.date_rdv &&
    (filtre.fournisseur?.split(",").includes(rdv.fournisseur.toString()) ??
      true) &&
    (filtre.client?.split(",").includes(rdv.client.toString()) ?? true) &&
    (filtre.chargement?.split(",").includes(rdv.chargement.toString()) ??
      true) &&
    (filtre.livraison?.split(",").includes(rdv.livraison.toString()) ?? true) &&
    (filtre.transporteur?.split(",").includes(rdv.transporteur.toString()) ??
      true) &&
    (filtre.affreteur?.split(",").includes(rdv.affreteur.toString()) ?? true)
  );
}
