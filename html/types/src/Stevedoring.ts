import type { EscaleConsignation, ShippingCallCargo } from "@app/types";

/**
 * Personnel de manutention.
 */
export type StevedoringStaff = {
  /**
   * Identifiant du personnel.
   */
  id: number | null;

  /**
   * Prénom.
   */
  firstname: string;

  /**
   * Nom de famille.
   */
  lastname: string;

  /**
   * Nom complet.
   */
  fullname: string;

  /**
   * Téléphone.
   */
  phone: string;

  /**
   * Type de contrat.
   */
  type: "mensuel" | "interim";

  /**
   * Agency d'intérim.
   */
  tempWorkAgency: string | null;

  /**
   * Actif.
   */
  isActive: boolean;

  /**
   * Commentaires.
   */
  comments: string;

  /**
   * Date de suppression.
   */
  deletedAt: Date | null;
};

/**
 * Equipement de manutention.
 */
export type StevedoringEquipment = {
  /**
   * Identifiant du personnel.
   */
  id: number | null;

  /**
   * Type.
   */
  type: string;

  /**
   * Marque.
   */
  brand: string;

  /**
   * Modèle.
   */
  model: string;

  /**
   * Numéro interne.
   */
  internalNumber: string;

  /**
   * Numéro de série.
   */
  serialNumber: string;

  /**
   * Nom d'affichage.
   */
  displayName: string;

  /**
   * Année de fabrication.
   */
  year: number | null;

  /**
   * Commentaires.
   */
  comments: string;

  /**
   * `true` si l'équipement est une grue.
   */
  isCrane: boolean;

  /**
   * Actif.
   */
  isActive: boolean;
};

export type TempWorkHours = {
  /**
   * Identifiant de l'enregistrement.
   */
  id: number | null;

  /**
   * Identifiant de l'employé intérimaire.
   */
  staffId: number;

  /**
   * Date.
   */
  date: string;

  /**
   * Nombre d'heures travaillées.
   */
  hoursWorked: number;

  /**
   * Commentaires.
   */
  comments: string;

  /**
   * Détails des heures travaillées.
   */
  details?: string;
};

/**
 * Rapport navire.
 */
export type StevedoringShipReport = {
  id: number;
  isArchive: boolean;
  linkedShippingCallId: EscaleConsignation["id"];
  ship: string;
  port: string;
  berth: string;
  comments: string;
  invoiceInstructions: string;
  customers?: string[];
  startDate: string | null;
  endDate: string | null;
  cargoEntries: ShippingCallCargo[];
  cargoTotals?: {
    bl: {
      tonnage: number;
      volume: number;
      units: number;
    };
    outturn: {
      tonnage: number;
      volume: number;
      units: number;
    };
    difference: {
      tonnage: number;
      volume: number;
      units: number;
    };
  };
  storageEntries: StevedoringShipReportStorageEntry[];
  storageTotals?: {
    tonnage: number;
    volume: number;
    units: number;
  };
  subreports: {
    id: number;
    entriesByDate: {
      [date: string]: {
        cranes: StevedoringShipReportEquipmentEntry[];
        equipments: StevedoringShipReportEquipmentEntry[];
        permanentStaff: StevedoringShipReportStaffEntry[];
        tempStaff: StevedoringShipReportStaffEntry[];
        trucking: StevedoringShipReportSubcontractEntry[];
        otherSubcontracts: StevedoringShipReportSubcontractEntry[];
      };
    };
    cargoIds: number[];
    // cargoEntries: StevedoringShipReport["cargoEntries"];
    cargoTotals?: StevedoringShipReport["cargoTotals"];
    storageEntries?: StevedoringShipReport["storageEntries"];
    storageTotals?: StevedoringShipReport["storageTotals"];
  }[];
};

/**
 * Entrée de matériel.
 */
export type StevedoringShipReportEquipmentEntry = {
  id: number;
  equipmentId: StevedoringEquipment["id"];
  date: string;
  hoursHint: string;
  hoursWorked: number;
  comments: string;
};

/**
 * Entrée de personnel.
 */
export type StevedoringShipReportStaffEntry = {
  id: number;
  staffId: StevedoringStaff["id"];
  date: string;
  hoursHint: string;
  hoursWorked: number;
  comments: string;
};

/**
 * Entrée de sous-traitance.
 */
export type StevedoringShipReportSubcontractEntry = {
  id: number;
  subcontractorName: string;
  type?: string;
  date: string;
  hoursWorked: number | null;
  cost: number | null;
  comments: string;
};

/**
 * Entrée de stockage.
 */
export type StevedoringShipReportStorageEntry = {
  id: number;
  cargoId: ShippingCallCargo["id"];
  storageName: string;
  tonnage: number;
  volume: number;
  units: number;
  comments: string;
};

export type StevedoringShipReportFilter = Partial<{
  startDate: string;
  endDate: string;
  isArchive: boolean;
  ships: string[];
  ports: string[];
  berths: string[];
  cargoes: string[];
  strictCargoes: boolean;
  customers: string[];
  storageNames: string[];
}>;

export type StevedoringShipReportFilterData = {
  ships: string[];
  ports: string[];
  berths: string[];
  cargoes: string[];
  customers: string[];
  storageNames: string[];
};
