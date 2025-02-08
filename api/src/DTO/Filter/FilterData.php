<?php

// Path: api/src/DTO/Filter/FilterData.php

declare(strict_types=1);

namespace App\DTO\Filter;

abstract readonly class FilterData implements \JsonSerializable
{
    /**
     * Get filter data.
     * 
     * @return array<string, mixed>
     */
    public function getFilterData(): array
    {
        $filterData = [];

        $reflector = new \ReflectionClass($this);
        $properties = $reflector->getProperties();

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $propertyValue = $property->getValue($this);

            $filterData[$propertyName] = $propertyValue;
        }

        return $filterData;
    }

    public function jsonSerialize(): mixed
    {
        return $this->getFilterData();
    }
}
