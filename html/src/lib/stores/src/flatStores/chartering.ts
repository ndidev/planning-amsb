import { createFlatStore } from "../generics/flatStore";
import type { Charter } from "@app/types";
import type { FetcherOptions } from "@app/utils";

/**
 * Store affrètements maritimes.
 */
export const charteringCharters = (
  params: FetcherOptions["searchParams"] = new URLSearchParams()
) =>
  createFlatStore<Charter>("chartering/charters", null, {
    params,
    satisfiesParams,
  });

function satisfiesParams(charter: Charter, params: URLSearchParams) {
  const archives = "archives" in Object.fromEntries(params);

  return charter.archive === archives;
}
