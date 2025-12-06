<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;

class SellerProfileEntity extends Entity
{
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'business_name' => null,
        'business_description' => null,
        'business_address' => null,
        'business_phone' => null,
        'business_email' => null,
        'opening_hours' => null,
        'delivery_options' => null,
        'website_url' => null,
        'facebook_url' => null,
        'instagram_url' => null,
        'logo_url' => null,
        'is_verified' => null,
        'verification_status' => null,
        'rejection_reason' => null,
        'verified_at' => null,
        'is_active' => null,
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'opening_hours' => 'json-array',
        'delivery_options' => 'json-array',
    ];
}
