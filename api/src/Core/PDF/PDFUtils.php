<?php

namespace App\Core\PDF;

use \DateTime;
use \tFPDF;
use App\Core\Database\MySQL;
use App\Core\DateUtils;
use App\Core\PDF\PDFVrac;
use App\Core\Logger\ErrorLogger;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

class PDFUtils
{
    /**
     * Génère un PDF client.
     * 
     * @param string   $module      id du module.
     * @param int      $fournisseur id du fournisseur.
     * @param DateTime $date_debut  Date de début des RDV.
     * @param DateTime $date_fin    Date de fin des RDV.
     * 
     * @return tFPDF 
     * @throws PDOException 
     */
    public static function generatePDF(
        string $module,
        int $fournisseur,
        DateTime $date_debut,
        DateTime $date_fin
    ): tFPDF {

        $mysql = new MySQL();

        /**
         * Récupération données fournisseur et agence
         */
        $donnees_fournisseur = $mysql->query("SELECT nom_court AS nom, logo FROM tiers WHERE id = $fournisseur")->fetch();
        $donnees_agence = $mysql->query("SELECT ville FROM config_agence WHERE service = 'transit'")->fetch();

        if (!$donnees_fournisseur) {
            return FALSE;
        }

        /**
         * Vrac
         */
        if ($module === "vrac") {
            // RDV vrac
            $requete_rdvs_vrac = $mysql->prepare(
                "SELECT
            pl.date_rdv,
            pl.heure,
            p.nom AS produit_nom,
            p.couleur AS produit_couleur,
            q.nom AS qualite_nom,
            q.couleur AS qualite_couleur,
            pl.quantite,
            p.unite,
            c.nom_court AS client_nom,
            c.ville AS client_ville,
            t.nom_court AS transporteur_nom,
            pl.num_commande
          FROM vrac_planning pl
          LEFT JOIN vrac_produits p ON p.id = pl.produit
          LEFT JOIN vrac_qualites q ON q.id = pl.qualite
          LEFT JOIN tiers c ON c.id = pl.client
          LEFT JOIN tiers t ON t.id = pl.transporteur
          WHERE date_rdv
          BETWEEN :date_debut
          AND :date_fin
          AND fournisseur = :fournisseur
          ORDER BY
            date_rdv,
            -heure DESC,
            produit_nom,
            qualite_nom,
            client_nom"
            );

            $requete_rdvs_vrac->execute([
                'fournisseur' => $fournisseur,
                'date_debut' => DateUtils::format(DateUtils::SQL_DATE, $date_debut),
                'date_fin' => DateUtils::format(DateUtils::SQL_DATE, $date_fin)
            ]);

            $rdvs = $requete_rdvs_vrac->fetchAll();

            return new PDFVrac($donnees_fournisseur, $rdvs, $date_debut, $date_fin, $donnees_agence);
        }

        /**
         * Bois
         */
        if ($module === "bois") {
            // RDV bois planifiés
            $requete_rdvs_bois_non_attente = $mysql->prepare(
                "SELECT
            pl.date_rdv,
            pl.numero_bl,
            pl.commentaire_public,
            ch.id AS chargement_id,
            ch.nom_court AS chargement_nom,
            ch.cp AS chargement_cp,
            ch.ville AS chargement_ville,
            ch.pays AS chargement_pays,
            c.id AS client_id,
            c.nom_court AS client_nom,
            c.cp AS client_cp,
            c.ville AS client_ville,
            c.pays AS client_pays,
            l.id AS livraison_id,
            l.nom_court AS livraison_nom,
            l.cp AS livraison_cp,
            l.ville AS livraison_ville,
            l.pays AS livraison_pays,
            a.nom_court AS affreteur_nom,
            a.lie_agence AS affreteur_lie_agence
          FROM bois_planning pl
          LEFT JOIN tiers c ON c.id = pl.client
          LEFT JOIN tiers ch ON ch.id = pl.chargement
          LEFT JOIN tiers l ON l.id = pl.livraison
          LEFT JOIN tiers a ON a.id = pl.affreteur
          WHERE date_rdv
          BETWEEN :date_debut
          AND :date_fin
          AND attente = 0
          AND fournisseur = :fournisseur
          ORDER BY
            date_rdv,
            numero_bl,
            client_nom"
            );

            // RDV bois en attente
            $requete_rdvs_bois_attente = $mysql->prepare(
                "SELECT
            pl.date_rdv,
            pl.commentaire_public,
            ch.id AS chargement_id,
            ch.nom_court AS chargement_nom,
            ch.cp AS chargement_cp,
            ch.ville AS chargement_ville,
            ch.pays AS chargement_pays,
            c.id AS client_id,
            c.nom_court AS client_nom,
            c.cp AS client_cp,
            c.ville AS client_ville,
            c.pays AS client_pays,
            l.id AS livraison_id,
            l.nom_court AS livraison_nom,
            l.cp AS livraison_cp,
            l.ville AS livraison_ville,
            l.pays AS livraison_pays
          FROM bois_planning pl
          LEFT JOIN tiers c ON c.id = pl.client
          LEFT JOIN tiers ch ON ch.id = pl.chargement
          LEFT JOIN tiers l ON l.id = pl.livraison
          WHERE attente = 1
          AND fournisseur = :fournisseur
          ORDER BY
            -date_rdv DESC,
            client_nom"
            );

            $requete_rdvs_bois_non_attente->execute([
                'fournisseur' => $fournisseur,
                'date_debut' => DateUtils::format(DateUtils::SQL_DATE, $date_debut),
                'date_fin' => DateUtils::format(DateUtils::SQL_DATE, $date_fin)
            ]);

            $requete_rdvs_bois_attente->execute([
                'fournisseur' => $fournisseur,
            ]);

            $rdvs = [
                "non_attente" => $requete_rdvs_bois_non_attente->fetchAll(),
                "attente" => $requete_rdvs_bois_attente->fetchAll(),
            ];

            return new PDFBois($donnees_fournisseur, $rdvs, $date_debut, $date_fin, $donnees_agence);
        }
    }

    /**
     * Renvoie un PDF sous forme `string` pour visualisation (téléchargement).
     * 
     * @param tFPDF $pdf Instance du PDF à visualiser.
     * 
     * @return string 
     * @throws Exception 
     */
    public static function stringifyPDF(tFPDF $pdf)
    {
        return $pdf->Output('S');
    }

    /**
     * Envoie un PDF par e-mail.
     * 
     * @param tFPDF    $pdf          PDF à envoyer.
     * @param string   $module       Id du module.
     * @param int      $fournisseur  Id du fournisseur.
     * @param string   $liste_emails Adresses e-mail des destinataires.
     * @param DateTime $date_debut   Date de début des RDV.
     * @param DateTime $date_fin     Date de fin des RDV.
     * 
     * @return array Résultat de l'envoi.
     */
    public static function sendPDF(
        tFPDF $pdf,
        string $module,
        int $fournisseur,
        string $liste_emails,
        DateTime $date_debut,
        DateTime $date_fin
    ): array {
        /**
         * @var array $resultat Résultat de l'envoi.
         */
        $resultat = [
            "module" => $module,
            "fournisseur" => $fournisseur,
            "statut" => null,
            "message" => null,
            "adresses" => null,
            "erreur" => null
        ];

        // Infos agence
        $agence = (new MySQL)->query("SELECT * FROM config_agence WHERE service = 'transit'")->fetch();

        try {
            // Création e-mail
            $mail = new PDFMailer(
                $pdf,
                $date_debut,
                $date_fin,
                $agence
            );

            // Adresses
            $mail->ajouterAdresses(to: explode(PHP_EOL, $liste_emails));

            $mail->send();
            $mail->smtpClose();

            $resultat["statut"] = "succes";
            $resultat["message"] = "Le PDF a été envoyé avec succès.";
            $resultat["adresses"] = $mail->getAllAddresses();
        } catch (PHPMailerException $e) {
            $resultat["statut"] = "echec";
            $resultat["message"] = "Erreur : " . $mail->ErrorInfo;
            $resultat["erreur"] = $e->errorMessage();
            ErrorLogger::log($e);
        } catch (\Exception $e) {
            $resultat["statut"] = "echec";
            $resultat["message"] = "Erreur : " . $mail->ErrorInfo;
            ErrorLogger::log($e);
        } finally {
            unset($mail);

            return $resultat;
        }
    }
}
