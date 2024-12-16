import { createFlatStore } from "../generics/flatStore";
import type {
  StevedoringStaff,
  StevedoringEquipment,
  TempWorkHours,
} from "@app/types";

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

/**
 * Store des heures intérimaires.
 */
export const stevedoringTempWorkHours = createFlatStore<TempWorkHours>(
  "manutention/heures-interimaires",
  {
    id: null,
    staffId: null,
    date: null,
    hoursWorked: 0,
    comments: "",
  }
);
