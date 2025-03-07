import { createFlatStore } from "../generics/flatStore";
import { consignationEscales } from "@app/stores";
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
    displayName: "",
    year: null,
    serialNumber: "",
    isActive: true,
    comments: "",
    isCrane: false,
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
    subreports: [],
    cargoEntries: [],
    storageEntries: [],
  },
  {
    additionalEvents: [consignationEscales.endpoint],
  }
);
