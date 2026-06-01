<?php

namespace App\Models\Concerns;

// Ce trait mappe les anciens noms de champs vers les nouveaux noms physiques.
trait MappeAnciensAttributs
{
    // Cette methode resout le vrai nom de colonne a utiliser.
    protected function resoudreNomAttribut(?string $key): ?string
    {
        if ($key === null) {
            return $key;
        }

        return $this->mappageAnciensAttributs[$key] ?? $key;
    }

    // Cette methode lit un attribut en tenant compte du renommage physique.
    public function getAttribute($key)
    {
        return parent::getAttribute($this->resoudreNomAttribut($key));
    }

    // Cette methode ecrit un attribut en tenant compte du renommage physique.
    public function setAttribute($key, $value)
    {
        return parent::setAttribute($this->resoudreNomAttribut($key), $value);
    }
}
