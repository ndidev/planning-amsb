<?php

// Path: api/src/Core/Router/Route.php

namespace App\Core\Router;

class Route
{
    public function __construct(
        private string $path,
        private mixed $target,
        private ?string $name = null,
    ) {}

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getTarget(): mixed
    {
        return $this->target;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array{string, mixed, ?string}
     */
    public function toArray(): array
    {
        return [
            $this->path,
            $this->target,
            $this->name,
        ];
    }
}
