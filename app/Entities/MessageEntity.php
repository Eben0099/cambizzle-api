<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MessageEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at'];
    protected $casts   = [
        'rating' => 'int'
    ];

    // Accesseurs
    public function getContent(): ?string
    {
        return $this->attributes['content'] ?? null;
    }

    public function getType(): string
    {
        return $this->attributes['type'] ?? 'message';
    }

    public function getRating(): ?int
    {
        return $this->attributes['rating'] ?? null;
    }

    public function getImages(): array
    {
        $images = $this->attributes['images'] ?? '[]';
        if (is_string($images)) {
            return json_decode($images, true) ?? [];
        }
        return is_array($images) ? $images : [];
    }

    public function isRead(): bool
    {
        return ($this->attributes['status'] ?? '') === 'read';
    }

    public function isReply(): bool
    {
        return !empty($this->attributes['parent_id']);
    }

    public function hasRating(): bool
    {
        return !empty($this->attributes['rating']);
    }

    public function isValidRating(): bool
    {
        $rating = $this->getRating();
        return $rating !== null && $rating >= 1 && $rating <= 5;
    }

    // Méthodes utilitaires
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $data = parent::toArray($onlyChanged, $cast, $recursive);

        // Décoder les images JSON
        $data['images'] = $this->getImages();
        $data['is_read'] = $this->isRead();
        $data['is_reply'] = $this->isReply();
        $data['has_rating'] = $this->hasRating();

        return $data;
    }
}
