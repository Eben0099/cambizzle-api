<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class VerificationEntity extends Entity {
    // Propriétés alignées sur le schéma Mermaid
    public $id;
    public $user_id;
    public $type;
    public $document_path;
    public $status;
    public $created_at;
    public $updated_at;
    protected $attributes = [
        'id' => null,
        'user_id' => null,
        'type' => null,
        'document_path' => null,
        'status' => null,
        'created_at' => null,
        'updated_at' => null,
    ];
}
