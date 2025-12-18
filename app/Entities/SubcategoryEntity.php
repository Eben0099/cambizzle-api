<?php
namespace App\Entities;
use CodeIgniter\Entity\Entity;
class SubcategoryEntity extends Entity {
    public $id;
    public $category_id;
    public $name;
    public $slug;
    public $description;
    public $created_at;
    public $updated_at;
}
