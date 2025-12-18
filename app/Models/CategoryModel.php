<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id'; // Clé primaire correcte
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'name',
        'description',
        'icon',
        'slug',
        'icon_path',
        'is_active',
        'display_order'
    ];

    // Dates - PAS de timestamps dans la table categories
    protected $useTimestamps    = false;

    // Casts
    protected array $casts = [
        'is_active' => 'boolean',
        'display_order' => 'int'
    ];

    // Validation
    protected $validationRules = [
    'name' => 'required|min_length[2]|max_length[100]',
    'slug' => 'permit_empty|min_length[2]|max_length[120]|is_unique[categories.slug,id,{id}]',
        'description' => 'permit_empty|max_length[500]',
        'icon_path' => 'permit_empty|max_length[255]',
        'is_active' => 'permit_empty|in_list[0,1]',
        'display_order' => 'permit_empty|integer|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Le nom de la catégorie est obligatoire',
            'min_length' => 'Le nom doit contenir au moins 2 caractères',
            'max_length' => 'Le nom ne peut pas dépasser 100 caractères'
        ],
        'slug' => [
            'required' => 'Le slug est obligatoire',
            'min_length' => 'Le slug doit contenir au moins 2 caractères',
            'max_length' => 'Le slug ne peut pas dépasser 120 caractères',
            'is_unique' => 'Ce slug existe déjà'
        ],
        'description' => [
            'max_length' => 'La description ne peut pas dépasser 500 caractères'
        ],
        'icon_path' => [
            'max_length' => 'Le chemin de l\'icône ne peut pas dépasser 255 caractères'
        ],
        'is_active' => [
            'in_list' => 'Le statut doit être 0 ou 1'
        ],
        'display_order' => [
            'integer' => 'L\'ordre d\'affichage doit être un entier',
            'greater_than_equal_to' => 'L\'ordre d\'affichage doit être positif ou nul'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setDefaults'];
    protected $beforeUpdate   = [];

    /**
     * Définir les valeurs par défaut avant insertion
     */
    protected function setDefaults(array $data): array
    {
        if (isset($data['data'])) {
            if (!isset($data['data']['is_active'])) {
                $data['data']['is_active'] = 1;
            }
            if (!isset($data['data']['display_order'])) {
                $data['data']['display_order'] = 0;
            }
            // Générer le slug automatiquement si absent ou vide
            if (!isset($data['data']['slug']) || empty($data['data']['slug'])) {
                if (isset($data['data']['name'])) {
                    $data['data']['slug'] = \App\Services\SlugService::generate($data['data']['name']);
                }
            }
        }
        return $data;
    }

    // Relation avec les sous-catégories
    public function subcategories()
    {
        // Une catégorie a plusieurs sous-catégories
        // category_id dans subcategories fait référence à id_category dans categories
        return $this->hasMany(SubcategoryModel::class, 'category_id', 'id_category');
    }
}