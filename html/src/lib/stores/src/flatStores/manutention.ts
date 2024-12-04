import { createFlatStore } from "../generics/flatStore";
import type { StevedoringStaff } from "@app/types";

/**
 * Store personnel de manutention.
 */
export const stevedoringStaff = createFlatStore<StevedoringStaff>(
  "manutention/personnel"
);
