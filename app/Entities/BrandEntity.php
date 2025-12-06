<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BrandEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'is_active' => 'boolean'
    ];

    // Accesseurs
    public function getName(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function getLogoUrl(): ?string
    {
        if (!empty($this->logo_url)) {
            return base_url($this->logo_url);
        }
        return null;
    }

    public function isActive(): bool
    {
        return $this->attributes['is_active'] ?? true;
    }

    // Méthodes utilitaires
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $data = parent::toArray($onlyChanged, $cast, $recursive);

        // Ajouter l'URL complète du logo
        $data['full_logo_url'] = $this->getLogoUrl();

        return $data;
    }
}
