<?php

namespace App\Controllers;

use App\Core\HTTP\ETag;

/**
 * Réponse à appliquer en cas d'appel à l'endpoint "/".
 */
class RootController extends Controller
{
    public function __construct(
        private bool $_404 = false,
    ) {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->processRequest();
    }

    public function processRequest()
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->showIndex();
                break;

            default:
                $this->response->setCode(405)->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Affiche la liste des url disponibles pour l'API.
     */
    private function showIndex()
    {
        $liste_endpoints = $this->buildIndex();

        $etag = ETag::get($liste_endpoints);

        if (isset($_SERVER["HTTP_IF_NONE_MATCH"]) && $etag === $_SERVER["HTTP_IF_NONE_MATCH"]) {
            $this->response->setCode(304);
            return;
        }

        $this->response
            ->setCode($this->_404 ? 404 : 200)
            ->addHeader("ETag", $etag)
            ->setType("json")
            ->setBody(json_encode($liste_endpoints))
            ->send();
    }


    /**
     * Construit la liste des URL disponibles pour l'API.
     * 
     * @return string[] Liste des URL disponibles pour l'API.
     */
    private function buildIndex(): array
    {
        $liste_endpoints = [
            // Bois
            "rdvs_bois" => "bois/rdvs/{id}{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&client={client}&livraison={livraison}&fournisseur={fournisseur}&affreteur={affreteur}&transporteur={transporteur}}",
            "registre" => "bois/registre/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}",
            "stats" => "bois/stats/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&client={client}&livraison={livraison}&fournisseur={fournisseur}&affreteur={affreteur}&transporteur={transporteur}}",
            "suggestions-transporteurs" => "suggestions-transporteurs?chargement={id}&livraison={id}",
            // Vrac
            "rdvs_vrac" => "vrac/rdvs/{id}",
            "produits_vrac" => "vrac/produits/{id}",
            // Consignation
            "escales" => "consignation/escales/{id}",
            "escales_archives" => "consignation/escales?archives",
            "te" => "consignation/te",
            "stats" => "consignation/stats/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&armateur={armateur}}",
            "stats_details" => "consignation/stats/{periode}/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&armateur={armateur}}",
            "navires" => "consignation/navires",
            "marchandises" => "consignation/marchandises",
            "clients" => "consignation/clients",
            "navires-en-activite" => "consignation/navires-en-activite/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}",
            // Chartering
            "affretements_maritimes" => "chartering/charters/{id}",
            // Tiers
            "tiers" => "tiers/{id}{?nombre_rdv=true|false}&format={awesomplete}}",
            // Utilitaires
            "pays" => "pays/{iso}",
            "ports" => "ports/{locode}",
            "marees" => "marees/{annee}{?debut={jj/mm/aaaa}&fin={jj/mm/aaaa}",
            "marees_annees" => "marees/annees",
            // Config
            "modules" => "config/modules",
            "bandeau-info" => "config/bandeau/{id}",
            "pdf_configs" => "config/pdf",
            "pdf_visu" => "config/pdf/visu/{?module={module}&fournisseur={fournisseur}&date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}}",
            "pdf_envoi" => "config/pdf/envoi/",
            "rdvrapides" => "config/rdvrapides/{id}",
            "agence" => "config/agence/{service}",
            "cotes" => "config/cotes/{cote}",
            // Utilisateur
            "user" => "user/",
            // Administration
            "users" => "admin/users/{uid}",
        ];

        foreach ($liste_endpoints as $description => $path) {
            $liste_endpoints[$description] = $_ENV["API_URL"] . "/" . $path;
        }

        return $liste_endpoints;
    }
}
