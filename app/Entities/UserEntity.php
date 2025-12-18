<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class UserEntity extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'otp_expires_at', 'identity_verified_at'];
    protected $casts   = [
        'is_verified' => 'boolean',
        'is_identity_verified' => 'boolean',
    ];

    // Mutateurs
    public function setPassword(string $pass)
    {
        $this->attributes['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        return $this;
    }

    // Accesseurs
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getPhotoUrl(): ?string
    {
        if (!empty($this->photo_url)) {
            return base_url($this->photo_url);
        }
        return null;
    }

    public function getIdentityDocumentUrl(): ?string
    {
        if (!empty($this->identity_document_url)) {
            return base_url($this->identity_document_url);
        }
        return null;
    }

    // Méthodes utilitaires
    public function isVerified(): bool
    {
        return $this->is_verified === true;
    }

    public function isIdentityVerified(): bool
    {
        return $this->is_identity_verified === true;
    }

    public function isAdmin(): bool
    {
        return $this->role_id === 1;
    }

    public function isSeller(): bool
    {
        return $this->role_id === 2;
    }

    public function canCreateAds(): bool
    {
        return $this->isVerified() && ($this->isSeller() || $this->isAdmin());
    }

    // Masquer les champs sensibles lors de la sérialisation
    public function toArray(bool $onlyChanged = false, bool $cast = true, bool $recursive = false): array
    {
        $data = parent::toArray($onlyChanged, $cast, $recursive);
        
        // Supprimer les champs sensibles
        unset($data['password_hash']);
        unset($data['otp_code']);
        unset($data['verification_token']);
        
        return $data;
    }
}
