<?php

// Path: api/src/Controller/ErrorController.php

namespace App\Controller;

use App\Core\Exceptions\Client\ClientException;
use App\Core\Exceptions\Server\ServerException;
use App\Core\HTTP\HTTPResponse;
use App\Core\Logger\ErrorLogger;

final class ErrorController extends Controller
{
    public function __construct(
        private \Throwable $exception,
    ) {
        parent::__construct(error: true);
        $this->processRequest();
    }

    private function processRequest(): void
    {
        switch (true) {
            case ($this->exception instanceof ClientException):
                $this->handleClientException();
                break;

            case ($this->exception instanceof ServerException):
                $this->handleServerException();
                break;

            default:
                $this->handleThrowable();
                break;
        }
    }

    private function handleClientException(): void
    {
        if (!$this->exception instanceof ClientException) {
            $exceptionClass = $this->exception::class;
            throw new \Exception("L'exception n'est pas une instance de ClientException ({$exceptionClass})");
        }

        $this->response
            ->setCode($this->exception->httpStatus)
            ->setType("text")
            ->setBody($this->exception->getMessage());
    }

    private function handleServerException(): void
    {
        if (!$this->exception instanceof ServerException) {
            $exceptionClass = $this->exception::class;
            throw new \Exception("L'exception n'est pas une instance de ServerException ({$exceptionClass})");
        }

        ErrorLogger::log($this->exception);

        $this->response
            ->setCode($this->exception->httpStatus)
            ->setType("text")
            ->setBody("Erreur serveur");
    }

    private function handleThrowable(): void
    {
        ErrorLogger::log($this->exception);

        $this->response
            ->setCode(HTTPResponse::HTTP_INTERNAL_SERVER_ERROR_500)
            ->setType("text")
            ->setBody("Erreur serveur");
    }

    public static function handleEmergency(\Throwable $exception): void
    {
        $serverException = new ServerException(previous: $exception);
        $controller = new static($serverException);
        $controller->response->send();
    }
}
