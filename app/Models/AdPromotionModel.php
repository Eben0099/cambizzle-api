<?php

namespace App\Models;

use CodeIgniter\Model;

class AdPromotionModel extends Model
{
    protected $table            = 'ad_promotions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'App\Entities\AdPromotionEntity';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ad_id',
        'promotion_type',
        'starts_at',
        'expires_at',
        'price_paid',
        'payment_reference',
        'is_active'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'ad_id' => 'required|integer|is_not_unique[ads.id]',
        'promotion_type' => 'required|in_list[featured,urgent,highlighted]',
        'starts_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'expires_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'price_paid' => 'required|decimal|greater_than[0]',
        'payment_reference' => 'required|string|max_length[100]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];
    protected $validationMessages   = [
        'ad_id' => [
            'required' => 'L\'annonce est obligatoire',
            'integer' => 'ID annonce invalide',
            'is_not_unique' => 'Annonce non trouvée'
        ],
        'promotion_type' => [
            'required' => 'Le type de promotion est obligatoire',
            'in_list' => 'Type de promotion invalide (featured, urgent, highlighted)'
        ],
        'starts_at' => [
            'valid_date' => 'Date de début invalide'
        ],
        'expires_at' => [
            'valid_date' => 'Date de fin invalide'
        ],
        'price_paid' => [
            'required' => 'Le prix payé est obligatoire',
            'decimal' => 'Le prix doit être un nombre décimal',
            'greater_than' => 'Le prix doit être positif'
        ],
        'payment_reference' => [
            'required' => 'La référence de paiement est obligatoire',
            'string' => 'La référence doit être une chaîne de caractères',
            'max_length' => 'La référence ne peut pas dépasser 100 caractères'
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
     * Récupérer les promotions d'une annonce
     */
    public function getByAd(int $adId): array
    {
        return $this->where('ad_id', $adId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Récupérer les promotions actives
     */
    public function getActive(): array
    {
        return $this->where('is_active', true)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Récupérer les promotions actives d'une annonce
     */
    public function getActiveByAd(int $adId): array
    {
        return $this->where('ad_id', $adId)
                   ->where('is_active', true)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Vérifier si une annonce a une promotion active d'un type spécifique
     */
    public function hasActivePromotion(int $adId, string $promotionType): bool
    {
        return $this->where('ad_id', $adId)
                   ->where('promotion_type', $promotionType)
                   ->where('is_active', true)
                   ->where('expires_at >', date('Y-m-d H:i:s'))
                   ->countAllResults() > 0;
    }

    /**
     * Désactiver une promotion
     */
    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => false]);
    }

    /**
     * Désactiver toutes les promotions expirées
     */
    public function deactivateExpired(): int
    {
        return $this->where('is_active', true)
                   ->where('expires_at <=', date('Y-m-d H:i:s'))
                   ->set('is_active', false)
                   ->update();
    }

    /**
     * Calculer les revenus par type de promotion
     */
    public function getRevenueByType(string $startDate = null, string $endDate = null): array
    {
        $builder = $this->select('promotion_type, SUM(price_paid) as total_revenue, COUNT(*) as count')
                       ->groupBy('promotion_type');

        if ($startDate) {
            $builder->where('created_at >=', $startDate);
        }

        if ($endDate) {
            $builder->where('created_at <=', $endDate);
        }

        $results = $builder->findAll();

        $revenue = [];
        foreach ($results as $result) {
            $revenue[$result->promotion_type] = [
                'total_revenue' => (float) $result->total_revenue,
                'count' => (int) $result->count
            ];
        }

        return $revenue;
    }
}
