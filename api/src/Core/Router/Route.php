<?php

// Path: api/src/Core/Router/Route.php

declare(strict_types=1);

namespace App\Core\Router;

final class Route
{
    public function __construct(
        public private(set) string $path,
        public private(set) mixed $target,
        public private(set) ?string $name = null,
    ) {}

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
