<?php

namespace App\Models;

use CodeIgniter\Model;

class BrandModel extends Model
{
    protected $table            = 'brands';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'subcategory_id',
        'name',
        'description',
        'logo_url',
        'is_active'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'subcategory_id' => 'int',
        'is_active' => 'boolean'
    ];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'subcategory_id' => 'required|integer|is_not_unique[subcategories.id]',
        'name' => 'required|string|max_length[100]',
        'description' => 'permit_empty|string|max_length[500]',
        'logo_url' => 'permit_empty|string|max_length[255]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];
    protected $validationMessages   = [
        'subcategory_id' => [
            'required' => 'La sous-catégorie est obligatoire',
            'integer' => 'ID de sous-catégorie invalide',
            'is_not_unique' => 'Sous-catégorie non trouvée'
        ],
        'name' => [
            'required' => 'Le nom de la marque est obligatoire',
            'string' => 'Le nom doit être une chaîne de caractères',
            'max_length' => 'Le nom ne peut pas dépasser 100 caractères'
        ],
        'description' => [
            'string' => 'La description doit être une chaîne de caractères',
            'max_length' => 'La description ne peut pas dépasser 500 caractères'
        ],
        'logo_url' => [
            'string' => 'L\'URL du logo doit être une chaîne de caractères',
            'max_length' => 'L\'URL du logo ne peut pas dépasser 255 caractères'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setDefaults'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Définir les valeurs par défaut avant insertion
     */
    protected function setDefaults(array $data): array
    {
        if (isset($data['data'])) {
            if (!isset($data['data']['is_active'])) {
                $data['data']['is_active'] = 1;
            }
        }
        return $data;
    }

    /**
     * Récupérer les marques par sous-catégorie
     */
    public function getBySubcategory(int $subcategoryId): array
    {
        return $this->where('subcategory_id', $subcategoryId)
                   ->where('is_active', true)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }

    /**
     * Rechercher des marques
     */
    public function search(string $query, int $limit = 10): array
    {
        return $this->like('name', $query)
                   ->where('is_active', true)
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Vérifier si une marque existe pour une sous-catégorie
     */
    public function existsInSubcategory(string $name, int $subcategoryId, ?int $excludeId = null): bool
    {
        $builder = $this->where('name', $name)
                       ->where('subcategory_id', $subcategoryId);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Activer/désactiver une marque
     */
    public function toggleActive(int $id): bool
    {
        $brand = $this->find($id);
        if (!$brand) {
            return false;
        }

        return $this->update($id, ['is_active' => !$brand->is_active]);
    }
}
