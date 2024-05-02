<?php

namespace App\Core\Interfaces;

/**
 * Interface Arrayable
 * 
 * Cette interface définit une méthode toArray() qui permet de convertir un objet en un tableau associatif.
 */
interface Arrayable
{
    /**
     * Convertit l'objet en un tableau associatif.
     * 
     * @return array Le tableau associatif représentant l'objet.
     */
    public function toArray(): array;
}
