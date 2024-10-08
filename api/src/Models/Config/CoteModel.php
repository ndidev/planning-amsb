<?php

namespace App\Models\Config;

use App\Models\Model;

class CoteModel extends Model
{
    /**
     * Récupère toutes les côtes.
     * 
     * @return array Toutes les côtes récupérées.
     */
    public function readAll(): array
    {
        $statement = "SELECT * FROM config_cotes";

        $donnees = $this->mysql->query($statement)->fetchAll();

        for ($i = 0; $i < count($donnees); $i++) {
            $donnees[$i]["valeur"] = (float) $donnees[$i]["valeur"];
        }

        return $donnees;
    }

    /**
     * Récupère une côte.
     * 
     * @param string $cote Nom de la côte à récupérer
     * 
     * @return array Côte récupérée
     */
    public function read(string $nom_cote): ?array
    {
        $statement = "SELECT *
      FROM config_cotes
      WHERE cote = :cote";

        $requete = $this->mysql->prepare($statement);
        $requete->execute(["cote" => $nom_cote]);
        $cote = $requete->fetch();

        if (!$cote) return null;

        $cote["valeur"] = (float) $cote["valeur"];

        $donnees = $cote;

        return $donnees;
    }

    /**
     * Met à jour une côte.
     * 
     * @param string $cote  Nom de la côte à modifier
     * @param array  $input Valeur de la côte à modifier
     * 
     * @return array Côte modifiée
     */
    public function update(string $cote, array $input): array
    {
        $statement = "UPDATE config_cotes
      SET valeur = :valeur
      WHERE cote = :cote";

        $requete = $this->mysql->prepare($statement);
        $requete->execute([
            'valeur' => (float) $input["valeur"],
            'cote' => $cote
        ]);

        return $this->read($cote);
    }
}
