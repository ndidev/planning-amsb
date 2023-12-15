import type { RdvBois, Charter, EscaleConsignation } from "@app/types";

export type FiltreBois = {
  date_debut?: string;
  date_fin?: string;
  fournisseur?: RdvBois["fournisseur"][];
  client?: RdvBois["client"][];
  chargement?: RdvBois["chargement"][];
  livraison?: RdvBois["livraison"][];
  transporteur?: RdvBois["transporteur"][];
  affreteur?: RdvBois["affreteur"][];
};

export type FiltreCharter = {
  date_debut?: string;
  date_fin?: string;
  affreteur?: Charter["affreteur"][];
  armateur?: Charter["armateur"][];
  courtier?: Charter["courtier"][];
  statut?: Charter["statut"][];
};

export type FiltreConsignation = {
  date_debut?: string;
  date_fin?: string;
  navire?: EscaleConsignation["navire"][];
  armateur?: EscaleConsignation["armateur"][];
  last_port?: EscaleConsignation["last_port"][];
  next_port?: EscaleConsignation["next_port"][];
};
