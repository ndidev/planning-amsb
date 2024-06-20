<?php

// Path: api/src/Controller/Utils/CountryController.php

namespace App\Controller\Utils;

use App\Service\CountryService;
use App\Controller\Controller;
use App\Core\HTTP\ETag;
use App\Entity\Country;

class CountryController extends Controller
{
    private CountryService $countryService;

    public function __construct(
        private ?string $iso
    ) {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->countryService = new CountryService();
        $this->processRequest();
    }

    public function processRequest(): void
    {
        switch ($this->request->method) {
            case 'OPTIONS':
                $this->response->setCode(204)->addHeader("Allow", $this->supported_methods);
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
                $this->response->setCode(405)->addHeader("Allow", $this->supported_methods);
                break;
        }

        // Send the HTTP response.
        $this->response->send();
    }

    /**
     * Fetch all countries.
     */
    private function readAll()
    {
        $countries = $this->countryService->getCountries();

        $etag = ETag::get($countries);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

        $this->response
            ->setHeaders($this->headers)
            ->setJSON(array_map(fn (Country $country) => $country->toArray(), $countries));
    }

    /**
     * Fetch a country.
     *
     * @param string $iso     ISO code of the country to fetch.
     * @param bool   $dryRun Fetch the resource without returning the HTTP response.
     */
    private function read(string $iso)
    {
        $country = $this->countryService->getCountry($iso);

        if (!$country) {
            $message = "Not Found";
            $documentation = $_ENV["API_URL"] . "/doc/#/Consignation/lireEscaleConsignation";
            $body = json_encode(["message" => $message, "documentation_url" => $documentation]);
            $this->response->setCode(404)->setBody($body);
            return;
        }

        $etag = ETag::get($country);

        if ($this->request->etag === $etag) {
            $this->response->setCode(304);
            return;
        }

        $this->headers["ETag"] = $etag;
        $this->headers["Cache-control"] = "max-age=31557600, must-revalidate";

        $this->response
            ->setHeaders($this->headers)
            ->setJSON($country);
    }
}
