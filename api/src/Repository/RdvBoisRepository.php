<?php

namespace App\Repository;

use App\Core\DateUtils;
use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\DB\DBException;
use App\DTO\EntreeRegistreBoisDTO;
use App\Entity\Bois\RdvBois;
use App\Service\BoisService;

class RdvBoisRepository extends Repository
{

    /**
     * Renvoie l'extrait du registre d'affrètement avec le filtre appliqué.
     *
     * @param array $filtre 
     * 
     * @return EntreeRegistreBoisDTO[] Extrait du registre d'affrètement.
     */
    public function getRegistreAffretement(array $filtre): array
    {
        $date_debut_defaut = DateUtils::format(DateUtils::SQL_DATE, DateUtils::previousWorkingDay(new \DateTimeImmutable()));
        $date_fin_defaut = date("Y-m-d");

        // Filtre
        $date_debut = isset($filtre['date_debut'])
            ? ($filtre['date_debut'] ?: $date_debut_defaut)
            : $date_debut_defaut;

        $date_fin = isset($filtre['date_fin'])
            ? ($filtre['date_fin'] ?: $date_fin_defaut)
            : $date_fin_defaut;

        $statement =
            "SELECT
                p.date_rdv,
                f.nom_court AS fournisseur,
                c.nom_court AS chargement_nom,
                c.ville AS chargement_ville,
                cpays.nom AS chargement_pays,
                l.nom_court AS livraison_nom,
                l.cp AS livraison_cp,
                l.ville AS livraison_ville,
                lpays.nom AS livraison_pays,
                p.numero_bl,
                t.nom_court AS transporteur
            FROM bois_planning p
            LEFT JOIN tiers AS c ON p.chargement = c.id
            LEFT JOIN tiers AS l ON p.livraison = l.id
            LEFT JOIN tiers AS a ON p.affreteur = a.id
            LEFT JOIN tiers AS f ON p.fournisseur = f.id
            LEFT JOIN tiers AS t ON p.transporteur = t.id
            LEFT JOIN utils_pays cpays ON c.pays = cpays.iso
            LEFT JOIN utils_pays lpays ON l.pays = lpays.iso
            WHERE a.lie_agence = 1
                AND (date_rdv BETWEEN :date_debut AND :date_fin)
                AND attente = 0
            ORDER BY
            date_rdv,
            numero_bl";

        $requete = $this->mysql->prepare($statement);

        $requete->execute([
            "date_debut" => $date_debut,
            "date_fin" => $date_fin
        ]);

        $rdvsRaw = $requete->fetchAll();

        $boisService = new BoisService();

        $entreesRegistre = array_map(
            fn (array $rdvRaw) => $boisService->makeEntreeRegistreBoisDTO($rdvRaw),
            $rdvsRaw
        );

        return $entreesRegistre;
    }
}
