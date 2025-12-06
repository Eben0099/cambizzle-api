<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class RoleEntity extends Entity {
    public $id;
    public $name;
    public $permissions;
}
