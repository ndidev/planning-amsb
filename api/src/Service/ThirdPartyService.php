<?php

// Path: api/src/Service/ThirdPartyService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPRequestBody;
use App\Core\Logger\ErrorLogger;
use App\Entity\ThirdParty;
use App\Repository\ThirdPartyRepository;

/**
 * @phpstan-import-type ThirdPartyArray from \App\Repository\ThirdPartyRepository
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
     * @param array $rawData 
     * 
     * @phpstan-param ThirdPartyArray $rawData
     * 
     * @return ThirdParty 
     */
    public function makeThirdPartyFromDatabase(array $rawData): ThirdParty
    {
        $rawDataAH = new ArrayHandler($rawData);

        $thirdParty = (new ThirdParty())
            ->setId($rawDataAH->getInt('id'))
            ->setShortName($rawDataAH->getString('nom_court'))
            ->setFullName($rawDataAH->getString('nom_complet'))
            ->setAddressLine1($rawDataAH->getString('adresse_ligne_1'))
            ->setAddressLine2($rawDataAH->getString('adresse_ligne_2'))
            ->setPostCode($rawDataAH->getString('cp'))
            ->setCity($rawDataAH->getString('ville'))
            ->setCountry($this->countryService->getCountry($rawDataAH->getString('pays')))
            ->setPhone($rawDataAH->getString('telephone'))
            ->setComments($rawDataAH->getString('commentaire'))
            ->setNonEditable($rawDataAH->getBool('non_modifiable', false))
            ->setIsAgency($rawDataAH->getBool('lie_agence', false))
            ->setActive($rawDataAH->getBool('actif', true));

        // Logo
        $logoData = $rawDataAH->get('logo');
        if (\is_string($logoData) || null === $logoData) {
            $thirdParty->setLogo($logoData);
        }

        // Roles
        $rolesString = $rawDataAH->getString('roles', '{}', false);
        $roles = \json_decode($rolesString, true);

        if (!\is_array($roles)) {
            $roles = [];
        }

        $rolesAH = new ArrayHandler($roles);

        foreach ($thirdParty->getRoles() as $role => $default) {
            $thirdParty->setRole($role, $rolesAH->getBool($role, $default));
        }

        return $thirdParty;
    }

    /**
     * Creates a ThirdParty object from form data.
     * 
     * @param HTTPRequestBody $requestBody 
     * 
     * @return ThirdParty 
     */
    public function makeThirdPartyFromForm(HTTPRequestBody $requestBody): ThirdParty
    {
        $thirdParty = (new ThirdParty())
            ->setId($requestBody->getInt('id'))
            ->setShortName($requestBody->getString('nom_court'))
            ->setFullName($requestBody->getString('nom_complet'))
            ->setAddressLine1($requestBody->getString('adresse_ligne_1'))
            ->setAddressLine2($requestBody->getString('adresse_ligne_2'))
            ->setPostCode($requestBody->getString('cp'))
            ->setCity($requestBody->getString('ville'))
            ->setCountry($this->countryService->getCountry($requestBody->getString('pays')))
            ->setPhone($requestBody->getString('telephone'))
            ->setComments($requestBody->getString('commentaire'))
            ->setNonEditable($requestBody->getBool('non_modifiable'))
            ->setIsAgency($requestBody->getBool('lie_agence'))
            ->setActive($requestBody->getBool('actif', true));

        // Logo
        $logoData = $requestBody->get('logo');
        if (\is_array($logoData) || \is_string($logoData) || null === $logoData) {
            $thirdParty->setLogo($this->saveLogo($logoData));
        } else {
            $thirdParty->setLogo(false);
        }

        // Roles
        $rolesInRequest = $requestBody->getArray('roles');
        $rolesAH = new ArrayHandler($rolesInRequest);

        foreach (array_keys($thirdParty->getRoles()) as $role) {
            if ($rolesAH->isSet($role)) {
                $thirdParty->setRole($role, $rolesAH->getBool($role));
            }
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
     * @param HTTPRequestBody $input Elements of the third party to create.
     * 
     * @return ThirdParty Created third party.
     */
    public function createThirdParty(HTTPRequestBody $input): ThirdParty
    {
        $thirdParty = $this->makeThirdPartyFromForm($input);

        return $this->thirdPartyRepository->createThirdParty($thirdParty);
    }

    /**
     * Updates a third party.
     * 
     * @param int             $id    ID of the third party to update.
     * @param HTTPRequestBody $input Elements of the third party to update.
     * 
     * @return ThirdParty Updated third party.
     */
    public function updateThirdParty($id, HTTPRequestBody $input): ThirdParty
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
     * @return int Number of appointments for the third party.
     */
    public function getAppointmentCountForId(int $id): int
    {
        return $this->thirdPartyRepository->fetchAppointmentCountForId($id);
    }

    /**
     * Enregistrer un logo dans le dossier images
     * et retourne le hash du fichier.
     * 
     * @param array<mixed>|string|null $file Données du fichier (null pour effacement du logo existant).
     * 
     * @return string|null|false Nom de fichier du logo si l'enregistrement a réussi, `false` sinon.
     */
    private function saveLogo(array|string|null $file): string|null|false
    {
        try {
            // Conservation du fichier existant
            if (\gettype($file) === "string") {
                return false;
            }

            // Suppression du fichier existant
            if ($file === null) {
                return null;
            }

            // Récupérer les données de l'image
            // $fichier["data"] = "data:{type mime};base64,{données}"
            if (!isset($file["data"]) || !\is_string($file["data"])) {
                throw new ServerException("Logo : Données du fichier non trouvées.");
            }
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
