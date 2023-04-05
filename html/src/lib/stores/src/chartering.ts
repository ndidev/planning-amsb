import { createFlatStore } from "./generics/flatStore";
import type { Charter } from "@app/types";
import type { FetcherOptions } from "@app/utils";

/**
 * Store affrètements maritimes.
 */
export const charteringCharters = (
  params: FetcherOptions["params"] = new URLSearchParams()
) =>
  createFlatStore<Charter>("chartering/charters", {
    params,
    satisfiesParams,
  });

function satisfiesParams(charter: Charter, params: URLSearchParams) {
  const archives = "archives" in Object.fromEntries(params);

  return charter.archive === archives;
}
