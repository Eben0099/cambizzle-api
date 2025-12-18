<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AdEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'expires_at', 'moderated_at'];
    protected $casts   = [
        'has_discount' => 'boolean',
        'is_negotiable' => 'boolean',
        'view_count' => 'int',
        'price' => 'decimal',
        'original_price' => 'decimal',
        'discount_percentage' => 'int'
    ];

    // Accesseurs
    public function getSlug(): string
    {
        return $this->attributes['slug'] ?? '';
    }

    public function getTitle(): string
    {
        return $this->attributes['title'] ?? '';
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function getPrice(): ?float
    {
        return $this->attributes['price'] ? (float) $this->attributes['price'] : null;
    }

    public function getDiscountPrice(): ?float
    {
        if (!$this->has_discount || !$this->original_price) {
            return $this->getPrice();
        }
        return (float) $this->original_price;
    }

    public function isActive(): bool
    {
        return ($this->attributes['status'] ?? '') === 'active';
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isBefore('now');
    }

    public function isApproved(): bool
    {
        return ($this->attributes['moderation_status'] ?? '') === 'approved';
    }

    public function isPending(): bool
    {
        return ($this->attributes['moderation_status'] ?? '') === 'pending';
    }

    public function canEdit(int $userId): bool
    {
        return ($this->attributes['user_id'] ?? 0) === $userId;
    }

    public function incrementViewCount(): void
    {
        $this->attributes['view_count'] = ($this->attributes['view_count'] ?? 0) + 1;
    }

    public function getMainPhotoUrl(): ?string
    {
        // Cette méthode pourrait être étendue pour récupérer la photo principale
        // Pour l'instant, on retourne null
        return null;
    }

    public function getAllPhotoUrls(): array
    {
        // Cette méthode pourrait être étendue pour récupérer toutes les photos
        // Pour l'instant, on retourne un tableau vide
        return [];
    }

    // Méthodes utilitaires
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $data = parent::toArray($onlyChanged, $cast, $recursive);

        // Ajouter des champs calculés
        $data['is_expired'] = $this->isExpired();
        $data['can_edit'] = isset($data['user_id']) && isset($_SESSION['user_id']) ?
            $data['user_id'] == $_SESSION['user_id'] : false;

        // Supprimer les champs sensibles si nécessaire
        if (isset($data['moderation_notes']) && !$this->isModerator()) {
            unset($data['moderation_notes']);
        }

        return $data;
    }

    private function isModerator(): bool
    {
        // Logique pour vérifier si l'utilisateur actuel est modérateur
        return false; // À implémenter selon la logique d'authentification
    }
}