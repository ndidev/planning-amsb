import type {
  EscaleConsignation,
  ShippingCallCargo,
  StevedoringShipReportEquipmentEntry,
  StevedoringShipReportStaffEntry,
  StevedoringShipReportSubcontractEntry,
  StevedoringShipReportStorageEntry,
} from "@app/types";

/**
 * Rapport navire.
 */
export default class StevedoringShipReport {
  /**
   * Identifiant du rapport navire.
   */
  id: number = null;

  /**
   * Identifiant de l'escale liée.
   */
  linkedShippingCallId: EscaleConsignation["id"] = null;

  /**
   * Nom du navire.
   */
  ship: string = "";

  /**
   * Port.
   */
  port: string = "";

  /**
   * Quai.
   */
  berth: string = "";

  /**
   * Commentaires.
   */
  comments: string = "";

  /**
   * Date de début de manutention.
   */
  startDate: string = null;

  /**
   * Date de fin de manutention.
   */
  endDate: string = null;

  /**
   * Entrées de matériel.
   */
  equipmentEntries: StevedoringShipReportEquipmentEntry[] = [];

  /**
   * Entrées de personnel.
   */
  staffEntries: StevedoringShipReportStaffEntry[] = [];

  /**
   * Entrées de sous-traitance.
   */
  subcontractEntries: StevedoringShipReportSubcontractEntry[] = [];

  /**
   * Entrées de cargaison.
   */
  cargoEntries: ShippingCallCargo[] = [];

  /**
   * Entrées de stockage.
   */
  storageEntries: StevedoringShipReportStorageEntry[] = [];

  constructor(data: Partial<StevedoringShipReport> = {}) {
    Object.assign(this, data);
  }
}
