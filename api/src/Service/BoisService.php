<?php

namespace App\Service;

use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\DTO\EntreeRegistreBoisDTO;
use App\Entity\Bois\RdvBois;
use App\Repository\RdvBoisRepository;

class BoisService
{
    private RdvBoisRepository $rdvBoisRepository;

    public function __construct()
    {
        $this->rdvBoisRepository = new RdvBoisRepository();
    }

    public function makeEntreeRegistreBoisDTO(array $rawData): EntreeRegistreBoisDTO
    {
        $entree = (new EntreeRegistreBoisDTO())
            ->setDateRdv($rawData["date_rdv"] ?? "")
            ->setFournisseur($rawData["fournisseur"] ?? "")
            ->setChargementNom($rawData["chargement_nom"] ?? "")
            ->setChargementVille($rawData["chargement_ville"] ?? "")
            ->setChargementPays($rawData["chargement_pays"] ?? "")
            ->setLivraisonNom($rawData["livraison_nom"] ?? "")
            ->setLivraisonCp($rawData["livraison_cp"] ?? "")
            ->setLivraisonVille($rawData["livraison_ville"] ?? "")
            ->setLivraisonPays($rawData["livraison_pays"] ?? "")
            ->setNumeroBl($rawData["numero_bl"] ?? "")
            ->setTransporteur($rawData["transporteur"] ?? "");

        return $entree;
    }


    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     * 
     * @param array $filtre 
     */
    public function getRegistreAffretement(array $filtre): string
    {
        $output = fopen("php://temp/maxmemory:" . (5 * 1024 * 1024), "r+");

        if (!$output) {
            throw new ServerException("Erreur création fichier CSV");
        }

        try {
            $entreesRegistre = $this->rdvBoisRepository->getRegistreAffretement($filtre);

            // UTF-8 BOM
            $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
            fputs($output, $bom);

            // En-tête
            $entete = [
                "Date",
                "Mois",
                "Donneur d'ordre",
                "Marchandise",
                "Chargement",
                "Livraison",
                "Numéro BL",
                "Transporteur"
            ];
            fputcsv($output, $entete, ';', '"');

            // Lignes de RDV
            foreach ($entreesRegistre as $entree) {

                $ligne = [
                    $entree->getDateRdv(),
                    $entree->getMois(),
                    $entree->getFournisseur(),
                    "1 COMPLET DE BOIS",
                    $entree->getChargement(),
                    $entree->getLivraison(),
                    $entree->getNumeroBl(),
                    $entree->getTransporteur(),
                ];

                fputcsv($output, $ligne, ';', '"');
            }

            rewind($output);

            $csv = stream_get_contents($output);

            if (!$csv) {
                throw new ServerException("Erreur écriture lignes");
            }

            return $csv;
        } catch (\Throwable $e) {
            throw new ServerException("Erreur création fichier CSV", previous: $e);
        }
    }
}
