<?php

// Path: api/src/Controller/Utils/CountryController.php

namespace App\Controller\Utils;

use App\Controller\Controller;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Entity\Country;
use App\Service\CountryService;

final class CountryController extends Controller
{
    private CountryService $countryService;

    public function __construct(
        private ?string $iso = null,
    ) {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->countryService = new CountryService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                if ($this->iso) {
                    $this->read($this->iso);
                } else {
                    $this->readAll();
                }
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Récupère tous les pays.
     */
    public function readAll(): void
    {
        $countries = $this->countryService->getCountries();

        $etag = ETag::get($countries);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->addHeader("Cache-control", "max-age=31557600, must-revalidate")
            ->setJSON(array_map(fn(Country $country) => $country->toArray(), $countries));
    }

    /**
     * Récupère un pays.
     * 
     * @param string $iso Code ISO du pays à récupérer.
     */
    public function read(string $iso): void
    {
        $country = $this->countryService->getCountry($iso);

        if (!$country) {
            throw new NotFoundException("Ce pays n'existe pas.");
        }

        $etag = ETag::get($country);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->addHeader("Cache-control", "max-age=31557600, must-revalidate")
            ->setJSON($country);
    }
}
