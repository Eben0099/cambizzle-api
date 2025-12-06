<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class ReferralCodeEntity extends Entity {
    public $id;
    public $user_id;
    public $code;
    public $expires_at;
    public $created_at;
}
