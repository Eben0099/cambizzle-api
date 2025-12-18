<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class CategoryEntity extends Entity {
    public $id;
    public $name;
    public $slug;
    public $description;
    public $created_at;
    public $updated_at;
}
