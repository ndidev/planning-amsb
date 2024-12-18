import { createFlatStore } from "../generics/flatStore";
import type { EscaleConsignation } from "@app/types";
import { DateUtils } from "@app/utils";

/**
 * Store escales consignation.
 */
export const consignationEscales = createFlatStore<EscaleConsignation>(
  "consignation/escales",
  {
    id: null,
    navire: "TBN",
    voyage: null,
    armateur: null,
    eta_date: null,
    eta_heure: "",
    nor_date: null,
    nor_heure: "",
    pob_date: null,
    pob_heure: "",
    etb_date: null,
    etb_heure: "",
    ops_date: null,
    ops_heure: "",
    etc_date: null,
    etc_heure: "",
    etd_date: null,
    etd_heure: "",
    te_arrivee: null,
    te_depart: null,
    last_port: "",
    next_port: "",
    call_port: "Le Légué",
    quai: "",
    marchandises: [],
    commentaire: "",
  },
  {
    satisfiesParams,
  }
);

function satisfiesParams(
  escale: EscaleConsignation,
  searchParams: URLSearchParams
) {
  const archives = "archives" in Object.fromEntries(searchParams);

  if (!archives) {
    return (
      escale.etd_date === null ||
      escale.etd_date >= new DateUtils().decaler(-1).toLocaleISODateString()
    );
  }

  if (archives) {
    return (
      escale.etd_date <= new DateUtils().decaler(-1).toLocaleISODateString()
    );
  }
}
