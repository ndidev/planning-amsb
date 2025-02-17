<?php

// Path: api/src/Core/Twig/Twig.php

declare(strict_types=1);

namespace App\Core\Twig;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;

final class Twig extends Environment
{
    private const DEFAULT_TEMPLATE_DIR = API . '/src/templates';

    public function __construct(
        private string $templateDir = self::DEFAULT_TEMPLATE_DIR,
        private bool $debug = false,
    ) {
        $loader = new FilesystemLoader($this->templateDir);

        parent::__construct($loader, [
            'debug' => $this->debug,
        ]);

        $this->addExtension(new IntlExtension());

        if ($this->debug) {
            $this->addExtension(new DebugExtension());
        }
    }
}
