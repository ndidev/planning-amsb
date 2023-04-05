import { createFlatStore } from "./generics/flatStore";
import type { EscaleConsignation } from "@app/types";
import { DateUtils, type FetcherOptions } from "@app/utils";

/**
 * Store escales consignation.
 */
export const consignationEscales = (
  params: FetcherOptions["params"] = new URLSearchParams()
) =>
  createFlatStore<EscaleConsignation>("consignation/escales", {
    params,
    satisfiesParams,
  });

function satisfiesParams(escale: EscaleConsignation, params: URLSearchParams) {
  const archives = "archives" in Object.fromEntries(params);

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
