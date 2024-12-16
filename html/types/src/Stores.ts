import type * as stores from "@app/stores";

export type Stores = {
  currentUser: typeof stores.currentUser;
  boisRdvs: ReturnType<typeof stores.boisRdvs>;
  vracRdvs: ReturnType<typeof stores.vracRdvs>;
  vracProduits: typeof stores.vracProduits;
  consignationEscales: ReturnType<typeof stores.consignationEscales>;
  charteringCharters: ReturnType<typeof stores.charteringCharters>;
  stevedoringStaff: typeof stores.stevedoringStaff;
  stevedoringEquipments: typeof stores.stevedoringEquipments;
  stevedoringTempWorkHours: typeof stores.stevedoringTempWorkHours;
  tiers: typeof stores.tiers;
  configBandeauInfo: typeof stores.configBandeauInfo;
  configPdf: typeof stores.configPdf;
  configAjoutsRapides: typeof stores.configAjoutsRapides;
  configAgence: typeof stores.configAgence;
  configCotes: typeof stores.configCotes;
  marees: ReturnType<typeof stores.marees>;
  mareesAnnees: typeof stores.mareesAnnees;
  ports: typeof stores.ports;
  pays: typeof stores.pays;
  adminUsers: typeof stores.adminUsers;
};
