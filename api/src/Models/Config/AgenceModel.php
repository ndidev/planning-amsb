<?php

namespace App\Models\Config;

use App\Models\Model;

class AgenceModel extends Model
{
    /**
     * Récupère les données de l'agence.
     * 
     * @return array Données de l'agence
     */
    public function readAll(): array
    {
        $request = $this->mysql->query("SELECT * FROM config_agence");
        $agencies = $request->fetchAll();

        return $agencies;
    }

    /**
     * Récupère les données d'un service de l'agence.
     * 
     * @param string $service Service de l'agence
     * 
     * @return array Données du service
     */
    public function read(string $service): ?array
    {
        $request = $this->mysql->prepare("SELECT * FROM config_agence WHERE service = :service");
        $request->execute(["service" => $service]);
        $service = $request->fetch();

        if (!$service) return null;

        return $service;
    }

    /**
     * Met à jour les données d'un service de l'agence.
     * 
     * @param string $service Service de l'agence
     * 
     * @return array Données du service
     */
    public function update(string $service, array  $input): array
    {
        $statement =
            "UPDATE config_agence
            SET
                nom = :nom,
                adresse_ligne_1 = :adresse_ligne_1,
                adresse_ligne_2 = :adresse_ligne_2,
                cp = :cp,
                ville = :ville,
                pays = :pays,
                telephone = :telephone,
                mobile = :mobile,
                email = :email
            WHERE service = :service";

        $request = $this->mysql->prepare($statement);
        $request->execute([
            "nom" => $input["nom"],
            "adresse_ligne_1" => $input["adresse_ligne_1"],
            "adresse_ligne_2" => $input["adresse_ligne_2"],
            "cp" => $input["cp"],
            "ville" => $input["ville"],
            "pays" => $input["pays"],
            "telephone" => $input["telephone"],
            "mobile" => $input["mobile"],
            "email" => $input["email"],
            "service" => $service,
        ]);

        return $this->read($service);
    }
}
