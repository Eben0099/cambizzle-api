<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class LocationEntity extends Entity {
    public $id;
    public $name;
    public $parent_id;
    public $type;
    public $created_at;
    public $updated_at;
}
