<?php

// Path: api/src/Controller/ErrorController.php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Exceptions\AppException;
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
            case ($this->exception instanceof AppException):
                $this->handleAppException();
                break;

            default:
                $this->handleThrowable();
                break;
        }
    }

    private function handleAppException(): void
    {
        if (!$this->exception instanceof AppException) {
            $exceptionClass = $this->exception::class;
            throw new \Exception("L'exception n'est pas une instance de AppException ({$exceptionClass})");
        }

        $this->response
            ->setCode($this->exception->httpStatus)
            ->setType("text")
            ->setBody($this->exception->getMessage());

        if ($this->exception instanceof ServerException) {
            ErrorLogger::log($this->exception);
        }
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
