import type {
  RdvBois,
  Charter,
  EscaleConsignation,
  StevedoringStaff,
} from "@app/types";

export type TimberFilter = {
  date_debut?: string;
  date_fin?: string;
  fournisseur?: RdvBois["fournisseur"][];
  client?: RdvBois["client"][];
  chargement?: RdvBois["chargement"][];
  livraison?: RdvBois["livraison"][];
  transporteur?: RdvBois["transporteur"][];
  affreteur?: RdvBois["affreteur"][];
};

export type CharteringFilter = {
  date_debut?: string;
  date_fin?: string;
  affreteur?: Charter["affreteur"][];
  armateur?: Charter["armateur"][];
  courtier?: Charter["courtier"][];
  statut?: Charter["statut"][];
};

export type ShippingFilter = {
  date_debut?: string;
  date_fin?: string;
  navire?: EscaleConsignation["navire"][];
  marchandise?: EscaleConsignation["marchandises"][number]["marchandise"];
  client?: EscaleConsignation["marchandises"][number]["client"];
  armateur?: EscaleConsignation["armateur"][];
  last_port?: EscaleConsignation["last_port"][];
  next_port?: EscaleConsignation["next_port"][];
};

export type StevedoringDispatchFilter = {
  startDate?: string;
  endDate?: string;
  staff?: StevedoringStaff["id"][];
};
