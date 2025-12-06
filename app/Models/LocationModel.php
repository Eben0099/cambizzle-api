<?php
namespace App\Models;
use CodeIgniter\Model;

class LocationModel extends Model {
    protected $table = 'locations';
    protected $primaryKey = 'id_location';
    protected $useAutoIncrement = true;
    protected $returnType = 'App\\Entities\\LocationEntity';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'parent_id',
        'type',
        'created_at',
        'updated_at',
        'is_active', // Ajouté pour permettre la gestion du champ is_active
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|string|max_length[100]',
        'parent_id' => 'permit_empty|integer|is_not_unique[locations.id_location]',
        'type' => 'required|in_list[country,region,city]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Le nom de la localisation est obligatoire',
            'string' => 'Le nom doit être une chaîne de caractères',
            'max_length' => 'Le nom ne peut pas dépasser 100 caractères'
        ],
        'parent_id' => [
            'integer' => 'ID parent invalide',
            'is_not_unique' => 'Localisation parente non trouvée'
        ],
        'type' => [
            'required' => 'Le type de localisation est obligatoire',
            'in_list' => 'Type invalide (country, region, city)'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Récupérer les localisations par type
     */
    public function getByType(string $type): array
    {
        return $this->where('type', $type)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Récupérer les enfants d'une localisation
     */
    public function getChildren(int $parentId): array
    {
        return $this->where('parent_id', $parentId)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Récupérer le chemin complet d'une localisation
     */
    public function getFullPath(int $locationId): array
    {
        $path = [];
        $current = $this->find($locationId);

        while ($current) {
            array_unshift($path, $current);
            if ($current->parent_id) {
                $current = $this->find($current->parent_id);
            } else {
                $current = null;
            }
        }

        return $path;
    }
}
