<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class PaymentEntity extends Entity {
    public $id;
    public $user_id;
    public $ad_id;
    public $amount;
    public $currency;
    public $reference;
    public $status;
    public $created_at;
}
