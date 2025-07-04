<?php

// Path: api/src/Service/ThirdPartyService.php

declare(strict_types=1);

namespace App\Service;

use App\Core\Array\ArrayHandler;
use App\Core\Component\Collection;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPRequestBody;
use App\Core\Logger\ErrorLogger;
use App\Entity\ThirdParty\ThirdParty;
use App\Entity\ThirdParty\ThirdPartyContact;
use App\Repository\ThirdPartyRepository;

/**
 * @phpstan-import-type ThirdPartyArray from \App\Entity\ThirdParty\ThirdParty
 * @phpstan-import-type ThirdPartyContactArray from \App\Entity\ThirdParty\ThirdPartyContact
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
     * @param ThirdPartyArray $rawData Raw data from the database.
     */
    public function makeThirdPartyFromDatabase(array $rawData): ThirdParty
    {
        $rawDataAH = new ArrayHandler($rawData);

        $thirdParty = new ThirdParty($rawDataAH);

        $thirdParty->country = $this->countryService->getCountry($rawDataAH->getString('pays'));

        // Logo
        $logoData = $rawDataAH->get('logo');
        if (\is_string($logoData) || null === $logoData) {
            $thirdParty->logoFilename = $logoData;
        }

        return $thirdParty;
    }

    /**
     * Creates a ThirdParty object from form data.
     * 
     * @param HTTPRequestBody $requestBody
     */
    public function makeThirdPartyFromForm(HTTPRequestBody $requestBody): ThirdParty
    {
        $thirdParty = new ThirdParty($requestBody);

        $thirdParty->country = $this->countryService->getCountry($requestBody->getString('pays'));

        $thirdParty->contacts = \array_map(
            // @phpstan-ignore argument.type
            fn($contact) => $this->makeThirdPartyContactFromForm($contact),
            $requestBody->getArray('contacts')
        );

        // Logo
        $logoData = $requestBody->get('logo');
        if (\is_array($logoData) || \is_string($logoData) || null === $logoData) {
            $thirdParty->logoFilename = $this->saveLogo($logoData);
        } else {
            $thirdParty->logoFilename = false;
        }

        return $thirdParty;
    }

    /**
     * Creates a ThirdPartyContact object from raw data.
     * 
     * @param ThirdPartyContactArray $rawData Raw data from the database.
     */
    public function makeThirdPartyContactFromDatabase(array $rawData): ThirdPartyContact
    {
        $rawDataAH = new ArrayHandler($rawData);

        $contact = new ThirdPartyContact();
        $contact->id = $rawDataAH->getInt('id');
        $contact->name = $rawDataAH->getString('nom');
        $contact->email = $rawDataAH->getString('email');
        $contact->phone = $rawDataAH->getString('telephone');
        $contact->position = $rawDataAH->getString('fonction');
        $contact->comments = $rawDataAH->getString('commentaire');

        return $contact;
    }

    /**
     * Creates a ThirdPartyContact object from raw data.
     * 
     * @param ThirdPartyContactArray $rawData Raw data from the form.
     */
    public function makeThirdPartyContactFromForm(array $rawData): ThirdPartyContact
    {
        $rawDataAH = new ArrayHandler($rawData);

        $contact = new ThirdPartyContact();
        $contact->id = $rawDataAH->getInt('id');
        $contact->name = $rawDataAH->getString('nom');
        $contact->email = $rawDataAH->getString('email');
        $contact->phone = $rawDataAH->getString('telephone');
        $contact->position = $rawDataAH->getString('fonction');
        $contact->comments = $rawDataAH->getString('commentaire');

        return $contact;
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
     */
    public function deleteThirdParty(int $id): void
    {
        $this->thirdPartyRepository->deleteThirdParty($id);
    }

    /**
     * Retrieves all contacts for a third party.
     * 
     * @param int $id ID of the third party to retrieve.
     * 
     * @return Collection<ThirdPartyContact> Contacts of the third party.
     */
    public function getThirdPartyContacts(int $id): Collection
    {
        return new Collection($this->thirdPartyRepository->fetchContactsForThirdParty($id));
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
            $data = \explode(",", $file["data"])[1];

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
