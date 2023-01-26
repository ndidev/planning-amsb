<?php

namespace Api\Utils;

// Bois
// ["/bois/rdvs/[i:id]?", fn ($id = null) => new RdvBois($id)],
// ["/bois/rdvs/[i:id]/confirmation_affretement", fn ($id = null) => new ConfirmationAffretementBois($id)],
// ["/bois/rdvs/[i:id]/numero_bl", fn ($id = null) => new NumeroBLBois($id)],
// ["/bois/rdvs/[i:id]/heure", fn ($id = null) => new HeureRDVBois($id)],
// ["/bois/registre", fn () => new RegistreBois()],
// ["/bois/stats", fn () => new StatsBois()],
// ["/bois/suggestions_transporteurs", fn () => new SuggestionsTransporteurs()],

// Vrac
// ["/vrac/rdvs/[i:id]?", fn ($id = null) => new RdvVrac($id)],
// ["/vrac/produits/[i:id]?", fn ($id = null) => new VracProduit($id)],

// Consignation
// ["/consignation/escales/[i:id]?", fn ($id = null) => new EscaleConsignation($id)],
// ["/consignation/voyage", fn () => new NumVoyageConsignation()],
// ["/consignation/te", fn () => new TE()],

// Chartering
// ["/chartering/charters/[i:id]?", fn ($id = null) => new AffretementMaritime($id)],

// Utilitaires
// ["/ports/[a:locode]?", fn ($locode = null) => new Ports($locode)],
// ["/pays/[a:iso]?", fn ($iso = null) => new Pays($iso)],
// ["/marees/[i:annee]?", fn ($annee = null) => new Marees($annee)],

// Config
// ["/config/modules", fn () => new Modules()], // IDEA: ne sert à rien actuellement -> supprimer ?
// ["/config/agence/[a:service]?", fn ($service = null) => new Agence($service)],
// ["/config/bandeau-info/[i:id]?", fn ($id = null) => new BandeauInfo($id)],
// ["/config/pdf/[i:id]?", fn ($id = null) => new ConfigPDF($id)],
// ["/config/pdf/visu", fn () => new VisualiserPDF()],
// ["/config/pdf/envoi", fn () => new EnvoiPDF()],
// ["/config/rdvrapides/[i:id]?", fn ($id = null) => new RdvRapides($id)],
// ["/config/cotes/[a:cote]?", fn ($cote = null) => new Cote($cote)],

// Tiers
// ["/tiers/[i:id]?", fn ($id = null) => new Tiers($id)],
// ["/tiers/[i:id]?/nombre_rdv", fn ($id = null) => new NombreRdv($id)],

// Admin
// ["/admin/users/[a:uid]?", fn ($uid = null) => new UserAccount($uid)],
// ["/admin/users/[a:uid]/reset", fn ($uid) => new UserAccountReset($uid)],
