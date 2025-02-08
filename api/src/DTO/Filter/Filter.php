<?php

// Path: api/src/DTO/Filter/Filter.php

declare(strict_types=1);

namespace App\DTO\Filter;

abstract readonly class Filter
{
    /**
     * Split string parameters.
     * 
     * Encapsulate each parameter in single quotes and separate them with commas.
     * 
     * Eg: "1,2,3" => "'1','2','3'"
     * 
     * @param string $param Parameter string.
     * 
     * @return string Encapsulated parameters separated by commas.
     */
    protected function splitStringParameters(string $param): string
    {
        return (string) preg_replace("/([^,]+),?/", "'$1',", $param);
    }

    abstract public function getSqlFilter(): string;
}
