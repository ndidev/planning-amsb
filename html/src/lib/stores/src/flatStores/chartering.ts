import { createFlatStore } from "../generics/flatStore";
import type { Charter, CharteringFilter } from "@app/types";
import { DateUtils } from "@app/utils";

/**
 * Store affrètements maritimes.
 */
export const charteringCharters = createFlatStore<Charter>(
  "chartering/charters",
  {
    id: null,
    statut: 0,
    lc_debut: null,
    lc_fin: null,
    cp_date: null,
    navire: "TBN",
    affreteur: null,
    armateur: null,
    courtier: null,
    fret_achat: null,
    fret_vente: null,
    surestaries_achat: null,
    surestaries_vente: null,
    legs: [],
    commentaire: "",
    archive: false,
  },
  {
    satisfiesParams,
  }
);

function satisfiesParams(charter: Charter, searchParams: URLSearchParams) {
  const filter: { [P in keyof CharteringFilter]: string } =
    Object.fromEntries(searchParams);

  const startDateMatches = (filter.startDate ?? "") <= charter.lc_fin;

  const endDateMatches = (filter.endDate ?? "9") >= charter.lc_debut;

  const chartererMatches =
    filter.charterers?.split(",").includes(charter.affreteur.toString()) ??
    true;

  const shipOwnerMatches =
    filter.shipOwners?.split(",").includes(charter.armateur.toString()) ?? true;

  const brokerMatches =
    filter.brokers?.split(",").includes(charter.courtier.toString()) ?? true;

  const statusMatches =
    filter.status?.split(",").includes(charter.statut.toString()) ?? true;

  const archiveMatches =
    charter.archive === (filter.archives === "true" ? true : false);

  return (
    startDateMatches &&
    endDateMatches &&
    chartererMatches &&
    shipOwnerMatches &&
    brokerMatches &&
    statusMatches &&
    archiveMatches
  );
}
