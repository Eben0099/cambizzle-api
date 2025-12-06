<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AdPhotoEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at'];
    protected $casts   = [
        'display_order' => 'int'
    ];

    // Accesseurs
    public function getOriginalUrl(): ?string
    {
        if (!empty($this->original_url)) {
            return base_url($this->original_url);
        }
        return null;
    }

    public function getThumbnailUrl(): ?string
    {
        if (!empty($this->thumbnail_url)) {
            return base_url($this->thumbnail_url);
        }
        return null;
    }

    public function getAltText(): ?string
    {
        return $this->attributes['alt_text'] ?? null;
    }

    public function getDisplayOrder(): int
    {
        return $this->attributes['display_order'] ?? 0;
    }

    public function isMainPhoto(): bool
    {
        return $this->getDisplayOrder() === 0;
    }

    // Méthodes utilitaires
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $data = parent::toArray($onlyChanged, $cast, $recursive);

        // Ajouter les URLs complètes
        $data['full_original_url'] = $this->getOriginalUrl();
        $data['full_thumbnail_url'] = $this->getThumbnailUrl();

        return $data;
    }
}
