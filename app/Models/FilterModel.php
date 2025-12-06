<?php

namespace App\Models;

use CodeIgniter\Model;

class FilterModel extends Model
{
    protected $table            = 'filters';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'subcategory_id',
        'name',
        'type',
        'is_required',
        'display_order',
        'is_active'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'subcategory_id' => 'int',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'int'
    ];
    protected array $castHandlers = [];

    // Dates - PAS de timestamps dans la table filters
    protected $useTimestamps = false;

    // Validation
    protected $validationRules      = [
        'subcategory_id' => 'required|integer|is_not_unique[subcategories.id]',
        'name' => 'required|string|max_length[100]',
        'type' => 'required|in_list[text,select,multiselect,number,boolean,date]',
        'is_required' => 'permit_empty|in_list[0,1]',
        'display_order' => 'permit_empty|integer|greater_than_equal_to[0]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];
    protected $validationMessages   = [
        'subcategory_id' => [
            'required' => 'La sous-catégorie est obligatoire',
            'integer' => 'ID sous-catégorie invalide',
            'is_not_unique' => 'Sous-catégorie non trouvée'
        ],
        'name' => [
            'required' => 'Le nom du filtre est obligatoire',
            'string' => 'Le nom doit être une chaîne de caractères',
            'max_length' => 'Le nom ne peut pas dépasser 100 caractères'
        ],
        'type' => [
            'required' => 'Le type de filtre est obligatoire',
            'in_list' => 'Le type doit être l’un de : text,select,multiselect,number,boolean,date.'
        ],
        'is_required' => [
            'in_list' => 'Le champ obligatoire doit être 0 ou 1'
        ],
        'display_order' => [
            'integer' => 'L\'ordre d\'affichage doit être un entier',
            'greater_than_equal_to' => 'L\'ordre d\'affichage doit être positif ou nul'
        ],
        'is_active' => [
            'in_list' => 'Le statut actif doit être 0 ou 1'
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

    /**
     * Définir les valeurs par défaut avant insertion
     */
    protected function setDefaults(array $data): array
    {
        if (isset($data['data'])) {
            if (!isset($data['data']['is_required'])) {
                $data['data']['is_required'] = 0;
            }
            if (!isset($data['data']['is_active'])) {
                $data['data']['is_active'] = 1;
            }
            if (!isset($data['data']['display_order'])) {
                $data['data']['display_order'] = 0;
            }
        }
        return $data;
    }
    protected $afterFind      = [];
    protected $beforeDelete   = [];

    /**
     * Récupérer les filtres d'une sous-catégorie
     */
    public function getBySubcategory(int $subcategoryId): array
    {
        return $this->where('subcategory_id', $subcategoryId)
                   ->where('is_active', true)
                   ->orderBy('display_order', 'ASC')
                   ->findAll();
    }

    /**
     * Récupérer les filtres requis d'une sous-catégorie
     */
    public function getRequiredBySubcategory(int $subcategoryId): array
    {
        return $this->where('subcategory_id', $subcategoryId)
                   ->where('is_active', true)
                   ->where('is_required', true)
                   ->orderBy('display_order', 'ASC')
                   ->findAll();
    }

    /**
     * Activer/désactiver un filtre
     */
    public function toggleActive(int $id): bool
    {
        $filter = $this->find($id);
        if (!$filter) {
            return false;
        }

        return $this->update($id, ['is_active' => !$filter['is_active']]);
    }

    /**
     * Mettre à jour l'ordre d'affichage
     */
    public function updateDisplayOrder(array $orders): bool
    {
        $this->db->transStart();

        foreach ($orders as $id => $order) {
            $this->update($id, ['display_order' => $order]);
        }

        return $this->db->transComplete();
    }
}
