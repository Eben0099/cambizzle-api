<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AdPromotionEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'starts_at', 'expires_at'];
    protected $casts   = [
        'is_active' => 'boolean',
        'price_paid' => 'decimal'
    ];

    // Accesseurs
    public function getPromotionType(): string
    {
        return $this->attributes['promotion_type'] ?? '';
    }

    public function getPricePaid(): ?float
    {
        return $this->attributes['price_paid'] ? (float) $this->attributes['price_paid'] : null;
    }

    public function getPaymentReference(): ?string
    {
        return $this->attributes['payment_reference'] ?? null;
    }

    public function isActive(): bool
    {
        return $this->attributes['is_active'] ?? false;
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isBefore('now');
    }

    public function isValid(): bool
    {
        return $this->isActive() && !$this->isExpired();
    }

    public function getDaysRemaining(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return now()->diffInDays($this->expires_at, false);
    }

    // Méthodes utilitaires
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $data = parent::toArray($onlyChanged, $cast, $recursive);

        // Ajouter des informations calculées
        $data['is_expired'] = $this->isExpired();
        $data['is_valid'] = $this->isValid();
        $data['days_remaining'] = $this->getDaysRemaining();

        return $data;
    }
}
