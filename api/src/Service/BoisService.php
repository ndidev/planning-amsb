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

    public function makeRdvBois(array $rawData): RdvBois
    {
        $rdv = (new RdvBois())
            ->setId($rawData["id"] ?? null)
            ->setAttente($rawData["attente"] ?? false)
            ->setDate($rawData["date_rdv"] ?? null)
            ->setHeureArrivee($rawData["heure_arrivee"] ?? null)
            ->setHeureDepart($rawData["heure_depart"] ?? null)
            ->setFournisseur($rawData["fournisseur"] ?? [])
            ->setChargement($rawData["chargement"] ?? [])
            ->setLivraison($rawData["livraison"] ?? [])
            ->setClient($rawData["client"] ?? [])
            ->setTransporteur($rawData["transporteur"] ?? null)
            ->setAffreteur($rawData["affreteur"] ?? null)
            ->setCommandePrete($rawData["commande_prete"] ?? false)
            ->setConfirmationAffretement($rawData["confirmation_affretement"] ?? false)
            ->setNumeroBL($rawData["numero_bl"] ?? "")
            ->setCommentairePublic($rawData["commentaire_public"] ?? "")
            ->setCommentaireCache($rawData["commentaire_cache"] ?? "");

        return $rdv;
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
     * Vérifie si un RDV bois existe dans la base de données.
     * 
     * @param int $id Identifiant du RDV bois.
     */
    public function rdvExiste(int $id)
    {
        return $this->rdvBoisRepository->rdvExiste($id);
    }

    /**
     * Récupère tous les RDV bois.
     * 
     * @param array $query Paramètres de recherche.
     * 
     * @return RdvBois[] Tous les RDV récupérés.
     */
    public function getRdvs(array $query): array
    {
        return $this->rdvBoisRepository->getRdvs($query);
    }

    /**
     * Récupère un RDV bois.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?RdvBois Rendez-vous récupéré
     */
    public function getRdv(int $id): ?RdvBois
    {
        return $this->rdvBoisRepository->getRdv($id);
    }

    /**
     * Crée un RDV bois.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @return RdvBois Rendez-vous créé
     */
    public function createRdv(array $input): RdvBois
    {
        $rdv = $this->makeRdvBois($input);

        return $this->rdvBoisRepository->createRdv($rdv);
    }

    /**
     * Met à jour un RDV bois.
     * 
     * @param int   $id ID du RDV à modifier
     * @param array $input  Eléments du RDV à modifier
     * 
     * @return RdvBois RDV modifié
     */
    public function updateRdv($id, array $input): RdvBois
    {
        $rdv = $this->makeRdvBois($input)->setId($id);

        return $this->rdvBoisRepository->updateRdv($rdv);
    }

    /**
     * Met à jour certaines proriétés d'un RDV bois.
     * 
     * @param ?int   $id    id du RDV à modifier
     * @param array $input Données à modifier
     * 
     * @return null|RdvBois RDV modifié
     */
    public function patchRdv(?int $id, array $input): ?RdvBois
    {
        if (isset($input["commande_prete"])) {
            if (!$id) {
                throw new ClientException("L'identifiant du RDV est requis pour marquer la commande comme prête.");
            }

            return $this->rdvBoisRepository->setCommandePrete($id, (bool) $input["commande_prete"]);
        }

        if (isset($input["confirmation_affretement"])) {
            if (!$id) {
                throw new ClientException("L'identifiant du RDV est requis pour confirmer l'affrètement.");
            }

            return $this->rdvBoisRepository->setConfirmationAffretement($id, (bool) $input["confirmation_affretement"]);
        }

        if (isset($input["numero_bl"])) {
            return $this->rdvBoisRepository->setNumeroBL($id, $input);
        }

        if (isset($input["heure_arrivee"])) {
            if (!$id) {
                throw new ClientException("L'identifiant du RDV est requis pour enregistrer l'heure d'arrivée.");
            }

            return $this->rdvBoisRepository->setHeureArrivee($id);
        }

        if (isset($input["heure_depart"])) {
            if (!$id) {
                throw new ClientException("L'identifiant du RDV est requis pour enregistrer l'heure de départ.");
            }

            return $this->rdvBoisRepository->setHeureDepart($id);
        }
    }

    /**
     * Supprime un RDV bois.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteRdv(int $id): bool
    {
        return $this->rdvBoisRepository->deleteRdv($id);
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
