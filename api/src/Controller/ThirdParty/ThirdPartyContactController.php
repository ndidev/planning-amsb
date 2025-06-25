<?php

// Path: api/src/Controller/ThirdParty/ThirdPartyContactController.php

declare(strict_types=1);

namespace App\Controller\ThirdParty;

use App\Controller\Controller;
use App\Core\Exceptions\Client\NotFoundException;
use App\Core\HTTP\ETag;
use App\Core\HTTP\HTTPResponse;
use App\Service\ThirdPartyService;

final class ThirdPartyContactController extends Controller
{
    private ThirdPartyService $thirdPartyService;

    public function __construct(
        private int $id,
    ) {
        parent::__construct("OPTIONS, HEAD, GET");
        $this->thirdPartyService = new ThirdPartyService();
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch ($this->request->getMethod()) {
            case 'OPTIONS':
                $this->response
                    ->setCode(HTTPResponse::HTTP_NO_CONTENT_204)
                    ->addHeader("Allow", $this->supportedMethods);
                break;

            case 'HEAD':
            case 'GET':
                $this->read($this->id);
                break;

            default:
                $this->response
                    ->setCode(HTTPResponse::HTTP_METHOD_NOT_ALLOWED_405)
                    ->addHeader("Allow", $this->supportedMethods);
                break;
        }
    }

    /**
     * Retrieves all contacts for a third party.
     */
    public function read(int $id): void
    {
        if (!$this->thirdPartyService->thirdPartyExists($id)) {
            throw new NotFoundException("Ce tiers n'existe pas.");
        }

        $thirdPartyContacts = $this->thirdPartyService->getThirdPartyContacts($id);

        $etag = ETag::get($thirdPartyContacts);

        if ($this->request->etag === $etag) {
            $this->response->setCode(HTTPResponse::HTTP_NOT_MODIFIED_304);
            return;
        }

        $this->response
            ->addHeader("ETag", $etag)
            ->setJSON($thirdPartyContacts);
    }
}
