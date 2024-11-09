<?php

// Path: api/src/Controller/RootController.php

declare(strict_types=1);

namespace App\Controller;

use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;

/**
 * Réponse à appliquer en cas d'appel à l'endpoint "/".
 */
final class RootController extends Controller
{
    public function __construct(
        private bool $_404 = false,
    ) {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->showIndex();
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Affiche la liste des url disponibles pour l'API.
     */
    private function showIndex(): void
    {
        $endpointsList = $this->buildIndex();

        $etag = ETag::get($endpointsList);

        if (isset($_SERVER["HTTP_IF_NONE_MATCH"]) && $etag === $_SERVER["HTTP_IF_NONE_MATCH"]) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->setCode($this->_404 ? 404 : 200)
            ->addHeader("ETag", $etag)
            ->setJSON($endpointsList);
    }


    /**
     * Construit la liste des URL disponibles pour l'API.
     * 
     * @return string[] Liste des URL disponibles pour l'API.
     */
    private function buildIndex(): array
    {
        $endpointsList = [
            // Bois
            "rdvs_bois" => "bois/rdvs/{id}{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&client={client}&livraison={livraison}&fournisseur={fournisseur}&affreteur={affreteur}&transporteur={transporteur}}",
            "registre" => "bois/registre/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}",
            "stats_bois" => "bois/stats/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&client={client}&livraison={livraison}&fournisseur={fournisseur}&affreteur={affreteur}&transporteur={transporteur}}",
            "suggestions-transporteurs" => "suggestions-transporteurs?chargement={id}&livraison={id}",
            "check-delivery-note-available" => "check-delivery-note-available?supplierId={id}&deliveryNoteNumber={number}&currentAppointmentId={id}",
            // Vrac
            "rdvs_vrac" => "vrac/rdvs/{id}",
            "produits_vrac" => "vrac/produits/{id}",
            // Consignation
            "escales" => "consignation/escales/{id}",
            "escales_archives" => "consignation/escales?archives",
            "te" => "consignation/te",
            "stats_consignation" => "consignation/stats/{?date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}&armateur={armateur}}",
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
            "bandeau-info" => "config/bandeau",
            "pdf_configs" => "config/pdf",
            "pdf_visu" => "config/pdf/generer/{?config={configId}&date_debut={jj/mm/aaaa}&date_fin={jj/mm/aaaa}}",
            "pdf_envoi" => "config/pdf/generer/",
            "rdvrapides" => "config/rdvrapides/{id}",
            "agence" => "config/agence/{service}",
            "cotes" => "config/cotes/{cote}",
            // Utilisateur
            "user" => "user/",
            // Administration
            "users" => "admin/users/{uid}",
        ];

        foreach ($endpointsList as $description => $path) {
            $endpointsList[$description] = $_ENV["API_URL"] . "/" . $path;
        }

        return $endpointsList;
    }
}
