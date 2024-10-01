<?php

// Path: api/src/Core/Component/CargoOperation.php

namespace App\Core\Component;

enum CargoOperation: string
{
    case IMPORT = 'Import';
    case EXPORT = 'Export';
}
