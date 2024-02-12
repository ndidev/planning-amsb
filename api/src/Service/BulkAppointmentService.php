<?php

namespace App\Service;

use App\Repository\BulkAppointmentRepository;
use App\Entity\BulkAppointment;

class BulkAppointmentService
{
    private $bulkAppointmentRepository;

    public function __construct()
    {
        $this->bulkAppointmentRepository = new BulkAppointmentRepository;
    }

    /**
     * Vérifie si un RDV vrac existe dans la base de données.
     * 
     * @param int $id Identifiant du RDV vrac.
     */
    public function appointmentExists(int $id)
    {
        return $this->bulkAppointmentRepository->appointmentExists($id);
    }

    /**
     * Récupère tous les RDV vrac.
     * 
     * @return array<int, \App\Entity\BulkAppointment> Tous les RDV récupérés
     */
    public function getAppointments(): array
    {
        return $this->bulkAppointmentRepository->getAppointments();
    }

    /**
     * Récupère un RDV vrac.
     * 
     * @param int $id ID du RDV à récupérer
     * 
     * @return ?BulkAppointment Rendez-vous récupéré
     */
    public function getAppointment($id): ?BulkAppointment
    {
        return $this->bulkAppointmentRepository->getAppointment($id);
    }

    /**
     * Crée un RDV vrac.
     * 
     * @param array $input Eléments du RDV à créer
     * 
     * @return BulkAppointment Rendez-vous créé
     */
    public function createAppointment(array $input): BulkAppointment
    {
        return $this->bulkAppointmentRepository->createAppointment($input);
    }

    /**
     * Met à jour un RDV vrac.
     * 
     * @param int   $id ID du RDV à modifier
     * @param array $input  Eléments du RDV à modifier
     * 
     * @return BulkAppointment RDV modifié
     */
    public function updateAppointment($id, array $input): BulkAppointment
    {
        return $this->bulkAppointmentRepository->updateAppointment($id, $input);
    }

    /**
     * Met à jour certaines proriétés d'un RDV vrac.
     * 
     * @param int   $id    id du RDV à modifier
     * @param array $input Données à modifier
     * 
     * @return BulkAppointment RDV modifié
     */
    public function patchAppointment(int $id, array $input): BulkAppointment
    {
        return $this->bulkAppointmentRepository->patchAppointment($id, $input);
    }

    /**
     * Supprime un RDV vrac.
     * 
     * @param int $id ID du RDV à supprimer
     * 
     * @return bool TRUE si succès, FALSE si erreur
     */
    public function deleteAppointment(int $id): bool
    {
        return $this->bulkAppointmentRepository->deleteAppointment($id);
    }
}
