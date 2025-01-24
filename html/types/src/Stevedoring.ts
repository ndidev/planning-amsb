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
   * Commentaires.
   */
  comments: string;

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
  startDate: string | null;
  endDate: string | null;
  entriesByDate: {
    [date: string]: {
      permanentStaff: StevedoringShipReportStaffEntry[];
      tempStaff: StevedoringShipReportStaffEntry[];
      equipments: StevedoringShipReportEquipmentEntry[];
      subcontracts: StevedoringShipReportSubcontractEntry[];
    };
  };
  cargoEntries: ShippingCallCargo[];
  storageEntries: StevedoringShipReportStorageEntry[];
};

/**
 * Entrée de matériel.
 */
export type StevedoringShipReportEquipmentEntry = {
  id: number;
  equipmentId: StevedoringEquipment["id"];
  date: string;
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
  hoursWorked: number;
  comments: string;
};

/**
 * Entrée de sous-traitance.
 */
export type StevedoringShipReportSubcontractEntry = {
  id: number;
  subcontractorName: string;
  type: string;
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
