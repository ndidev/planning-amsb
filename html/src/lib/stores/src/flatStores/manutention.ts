import { createFlatStore } from "../generics/flatStore";
import type {
  StevedoringStaff,
  StevedoringEquipment,
  TempWorkHours,
  StevedoringShipReport,
} from "@app/types";

/**
 * Store personnel de manutention.
 */
export const stevedoringStaff = createFlatStore<StevedoringStaff>(
  "manutention/personnel",
  {
    id: null,
    firstname: "",
    lastname: "",
    fullname: "",
    phone: "",
    type: "mensuel",
    tempWorkAgency: null,
    isActive: true,
    comments: "",
    deletedAt: null,
  }
);

/**
 * Store des équipements de manutention.
 */
export const stevedoringEquipments = createFlatStore<StevedoringEquipment>(
  "manutention/equipements",
  {
    id: null,
    brand: "",
    model: "",
    type: "",
    internalNumber: "",
    serialNumber: "",
    isActive: true,
    comments: "",
  }
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

export const stevedoringShipReports = createFlatStore<StevedoringShipReport>(
  "manutention/rapports-navires",
  {
    id: null,
    isArchive: false,
    linkedShippingCallId: null,
    ship: "",
    port: "",
    berth: "",
    comments: "",
    invoiceInstructions: "",
    startDate: null,
    endDate: null,
    entriesByDate: {},
    cargoEntries: [],
    storageEntries: [],
  },
  {
    additionalEvents: ["consignation/planning"],
  }
);
