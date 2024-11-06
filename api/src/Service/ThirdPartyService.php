<?php

// Path: api/src/Service/ThirdPartyService.php

namespace App\Service;

use App\Core\Component\Collection;
use App\Core\Exceptions\Server\ServerException;
use App\Core\Logger\ErrorLogger;
use App\Entity\ThirdParty;
use App\Repository\ThirdPartyRepository;

/**
 * @phpstan-type ThirdPartyData array{
 *                                id?: int,
 *                                nom_court?: string,
 *                                nom_complet?: string,
 *                                adresse_ligne_1?: string,
 *                                adresse_ligne_2?: string,
 *                                cp?: string,
 *                                ville?: string,
 *                                pays?: string,
 *                                telephone?: string,
 *                                commentaire?: string,
 *                                non_modifiable?: bool,
 *                                lie_agence?: bool,
 *                                roles?: ThirdPartyRoles,
 *                                logo?: string,
 *                                actif?: bool,
 *                                nombre_rdv?: int
 *                              }
 * 
 * @phpstan-import-type ThirdPartyRoles from \App\Entity\ThirdParty
 */
final class ThirdPartyService
{
    private ThirdPartyRepository $thirdPartyRepository;
    private CountryService $countryService;

    public function __construct()
    {
        $this->thirdPartyRepository = new ThirdPartyRepository($this);
        $this->countryService = new CountryService();
    }

    /**
     * Creates a ThirdParty object from raw data.
     * 
     * @param array{
     *          id: int,
     *          nom_court: string,
     *          nom_complet: string,
     *          adresse_ligne_1: string,
     *          adresse_ligne_2: string,
     *          cp: string,
     *          ville: string,
     *          pays: string,
     *          telephone: string,
     *          commentaire: string,
     *          non_modifiable: int,
     *          lie_agence: int,
     *          roles: string,
     *          logo: string,
     *          actif: int,
     *        } $rawData 
     * 
     * @return ThirdParty 
     */
    public function makeThirdPartyFromDatabase(array $rawData): ThirdParty
    {
        $thirdParty = (new ThirdParty())
            ->setId($rawData["id"])
            ->setShortName($rawData["nom_court"])
            ->setFullName($rawData["nom_complet"])
            ->setAddressLine1($rawData["adresse_ligne_1"])
            ->setAddressLine2($rawData["adresse_ligne_2"])
            ->setPostCode($rawData["cp"])
            ->setCity($rawData["ville"])
            ->setCountry($this->countryService->getCountry($rawData["pays"]))
            ->setPhone($rawData["telephone"])
            ->setComments($rawData["commentaire"])
            ->setIsNonEditable($rawData["non_modifiable"])
            ->setIsAgency($rawData["lie_agence"])
            ->setLogo($rawData["logo"])
            ->setIsActive($rawData["actif"]);

        $roles = json_decode($rawData["roles"] ?: "{}", true);

        foreach ($thirdParty->getRoles() as $role => $default) {
            $thirdParty->setRole($role, $roles[$role] ?? $default);
        }

        return $thirdParty;
    }

    /**
     * Creates a ThirdParty object from form data.
     * 
     * @param array $rawData 
     * 
     * @phpstan-param ThirdPartyData $rawData
     * 
     * @return ThirdParty 
     */
    public function makeThirdPartyFromForm(array $rawData): ThirdParty
    {
        $thirdParty = (new ThirdParty())
            ->setId($rawData["id"] ?? null)
            ->setShortName($rawData["nom_court"] ?? "")
            ->setFullName($rawData["nom_complet"] ?? "")
            ->setAddressLine1($rawData["adresse_ligne_1"] ?? "")
            ->setAddressLine2($rawData["adresse_ligne_2"] ?? "")
            ->setPostCode($rawData["cp"] ?? "")
            ->setCity($rawData["ville"] ?? "")
            ->setCountry($this->countryService->getCountry($rawData["pays"] ?? ''))
            ->setPhone($rawData["telephone"] ?? "")
            ->setComments($rawData["commentaire"] ?? "")
            ->setIsNonEditable($rawData["non_modifiable"] ?? false)
            ->setIsAgency($rawData["lie_agence"] ?? false)
            ->setLogo($this->saveLogo($rawData["logo"] ?? null))
            ->setIsActive($rawData["actif"] ?? true)
            ->setAppointmentCount($rawData["nombre_rdv"] ?? 0);

        foreach ($thirdParty->getRoles() as $role => $value) {
            $thirdParty->setRole($role, $rawData["roles"][$role] ?? false);
        }

        return $thirdParty;
    }

    /**
     * Vérifie si un tiers existe dans la base de données.
     * 
     * @param int $id Identifiant du tiers.
     */
    public function thirdPartyExists(int $id): bool
    {
        return $this->thirdPartyRepository->thirdPartyExists($id);
    }

    /**
     * Récupère tous les tiers.
     * 
     * @return Collection<ThirdParty> Liste des tiers.
     */
    public function getThirdParties(): Collection
    {
        return $this->thirdPartyRepository->fetchAllThirdParties();
    }

    /**
     * Récupère un tiers.
     * 
     * @param ?int $id ID du tiers à récupérer.
     * 
     * @return ?ThirdParty Tiers récupéré.
     */
    public function getThirdParty(?int $id): ?ThirdParty
    {
        if ($id === null) {
            return null;
        }

        return $this->thirdPartyRepository->fetchThirdParty($id);
    }

    /**
     * Creates a third party.
     * 
     * @param array $input Elements of the third party to create.
     * 
     * @phpstan-param ThirdPartyData $input
     * 
     * @return ThirdParty Created third party.
     */
    public function createThirdParty(array $input): ThirdParty
    {
        $thirdParty = $this->makeThirdPartyFromForm($input);

        return $this->thirdPartyRepository->createThirdParty($thirdParty);
    }

    /**
     * Updates a third party.
     * 
     * @param int   $id    ID of the third party to update.
     * @param array $input Elements of the third party to update.
     * 
     * @phpstan-param ThirdPartyData $input
     * 
     * @return ThirdParty Updated third party.
     */
    public function updateThirdParty($id, array $input): ThirdParty
    {
        $thirdParty = $this->makeThirdPartyFromForm($input)->setId($id);

        return $this->thirdPartyRepository->updateThirdParty($thirdParty);
    }

    /**
     * Deletes a third party.
     * 
     * @param int $id ID of the third party to delete.
     * 
     * @return bool TRUE if successful, FALSE if error.
     */
    public function deleteThirdParty(int $id): bool
    {
        return $this->thirdPartyRepository->deleteThirdParty($id);
    }

    /**
     * Retrieves the number of appointments for a third party or all third parties.
     * 
     * @param int $id ID of the third party to retrieve.
     * 
     * @return int|false Number of appointments for the third party(s).
     *                   `false` if the third party does not exist.
     */
    public function getAppointmentCountForId(int $id): int|false
    {
        return $this->thirdPartyRepository->getAppointmentCountForId($id);
    }

    /**
     * Enregistrer un logo dans le dossier images
     * et retourne le hash du fichier.
     * 
     * @param array{data: string}|string|null $file Données du fichier (null pour effacement du logo existant).
     * 
     * @return string|null|false Nom de fichier du logo si l'enregistrement a réussi, `false` sinon.
     */
    private function saveLogo(array|string|null $file): string|null|false
    {
        try {
            // Conservation du fichier existant
            if (gettype($file) === "string") {
                return false;
            }

            // Suppression du fichier existant
            if ($file === null) {
                return null;
            }

            // Récupérer les données de l'image
            // $fichier["data"] = "data:{type mime};base64,{données}"
            $data = explode(",", $file["data"])[1];

            // Création de l'image depuis les données
            $imageString = base64_decode($data);
            $image = imagecreatefromstring(base64_decode($data));

            if (!$image) {
                throw new ServerException("Logo : Erreur dans la création de l'image (imagecreatefromstring)");
            }


            // Redimensionnement
            define("MAX_HEIGHT", 500); // Hauteur maximale de l'image à enregistrer.

            $imageInfo = getimagesizefromstring($imageString);

            if (!$imageInfo) {
                ErrorLogger::log(
                    new ServerException("Logo : Erreur dans la récupération des informations de l'image (getimagesizefromstring)")
                );

                return false;
            }

            [$width, $height] = $imageInfo;
            $percent = min(MAX_HEIGHT / $height, 1);
            $newWidth = (int) ($width * $percent);
            $imageResized = imagescale($image, $newWidth);

            if (!$imageResized) {
                ErrorLogger::log(
                    new ServerException("Logo : Erreur dans le redimensionnement de l'image (imagescale)")
                );

                return false;
            }


            // Enregistrement
            $hash = hash("md5", $data);
            $filename = $hash . ".webp";
            $filepath = LOGOS . "/$filename";
            if (imagewebp($imageResized, $filepath, 100) === false) {
                throw new ServerException("Erreur dans l'enregistrement du logo (imagewebp)");
            }

            return $filename;
        } catch (\Throwable $e) {
            ErrorLogger::log($e);
            return false;
        }
    }
}
