<?php

namespace App\Models;

use CodeIgniter\Model;

class FilterOptionModel extends Model
{
    protected $table            = 'filter_options';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'filter_id',
        'value',
        'display_order',
        'is_active'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'filter_id' => 'required|integer|is_not_unique[filters.id]',
        'value' => 'required|string|max_length[255]',
        'display_order' => 'permit_empty|integer|greater_than_equal_to[0]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];
    protected $validationMessages   = [
        'filter_id' => [
            'required' => 'Le filtre est obligatoire',
            'integer' => 'ID filtre invalide',
            'is_not_unique' => 'Filtre non trouvé'
        ],
        'value' => [
            'required' => 'La valeur de l\'option est obligatoire',
            'string' => 'La valeur doit être une chaîne de caractères',
            'max_length' => 'La valeur ne peut pas dépasser 255 caractères'
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
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Récupérer les options d'un filtre
     */
    public function getByFilter(int $filterId): array
    {
        return $this->where('filter_id', $filterId)
                   ->where('is_active', true)
                   ->orderBy('display_order', 'ASC')
                   ->findAll();
    }

    /**
     * Activer/désactiver une option
     */
    public function toggleActive(int $id): bool
    {
        $option = $this->find($id);
        if (!$option) {
            return false;
        }

        return $this->update($id, ['is_active' => !$option['is_active']]);
    }

    /**
     * Supprimer toutes les options d'un filtre
     */
    public function deleteByFilter(int $filterId): bool
    {
        return $this->where('filter_id', $filterId)->delete() !== false;
    }

    /**
     * Mettre à jour l'ordre d'affichage des options
     */
    public function updateDisplayOrder(int $filterId, array $optionOrders): bool
    {
        $this->db->transStart();

        foreach ($optionOrders as $optionId => $order) {
            $this->where('id', $optionId)
                 ->where('filter_id', $filterId)
                 ->set('display_order', $order)
                 ->update();
        }

        return $this->db->transComplete();
    }
}
