<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class SellerEntity extends Entity {
    // Propriétés alignées sur le schéma Mermaid
    public $id;
    public $user_id;
    public $business_name;
    public $business_description;
    public $business_address;
    public $business_phone;
    public $business_email;
    public $opening_hours;
    public $delivery_options;
    public $website_url;
    public $facebook_url;
    public $instagram_url;
    public $is_active;
    public $created_at;
    public $updated_at;
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'business_name' => null,
        'business_logo' => null,
        'address' => null,
        'phone' => null,
        'created_at' => null,
        'updated_at' => null,
    ];
}
