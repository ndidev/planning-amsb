import { createFlatStore } from "../generics/flatStore";
import type { StevedoringStaff, StevedoringEquipment } from "@app/types";

/**
 * Store personnel de manutention.
 */
export const stevedoringStaff = createFlatStore<StevedoringStaff>(
  "manutention/personnel"
);

/**
 * Store des équipements de manutention.
 */
export const stevedoringEquipments = createFlatStore<StevedoringEquipment>(
  "manutention/equipements"
);
