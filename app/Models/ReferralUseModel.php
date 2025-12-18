<?php

namespace App\Models;

use CodeIgniter\Model;

class ReferralUseModel extends Model
{
    protected $table            = 'referral_uses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'referral_code_id',
        'referrer_id',
        'referred_user_id',
        'ad_id',
        'bonus_earned',
        'used_at'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'used_at';
    protected $updatedField  = null;

    // Validation
    protected $validationRules      = [
        'referral_code_id' => 'required|integer|is_not_unique[referral_codes.id]',
        'referrer_id' => 'required|integer|is_not_unique[users.id_user]',
        'referred_user_id' => 'required|integer|is_not_unique[users.id_user]',
        'ad_id' => 'permit_empty|integer|is_not_unique[ads.id]',
        'bonus_earned' => 'required|decimal|greater_than_equal_to[0]',
        'used_at' => 'permit_empty|valid_date[Y-m-d H:i:s]'
    ];
    protected $validationMessages   = [
        'referral_code_id' => [
            'required' => 'Le code de parrainage est obligatoire',
            'integer' => 'ID code de parrainage invalide',
            'is_not_unique' => 'Code de parrainage non trouvé'
        ],
        'referrer_id' => [
            'required' => 'Le parrain est obligatoire',
            'integer' => 'ID parrain invalide',
            'is_not_unique' => 'Parrain non trouvé'
        ],
        'referred_user_id' => [
            'required' => 'L\'utilisateur parrainé est obligatoire',
            'integer' => 'ID utilisateur parrainé invalide',
            'is_not_unique' => 'Utilisateur parrainé non trouvé'
        ],
        'ad_id' => [
            'integer' => 'ID annonce invalide',
            'is_not_unique' => 'Annonce non trouvée'
        ],
        'bonus_earned' => [
            'required' => 'Le bonus gagné est obligatoire',
            'decimal' => 'Le bonus doit être un nombre décimal',
            'greater_than_equal_to' => 'Le bonus doit être positif ou nul'
        ],
        'used_at' => [
            'valid_date' => 'Date d\'utilisation invalide'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['setUsedAt'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Définir la date d'utilisation automatiquement
     */
    protected function setUsedAt(array $data): array
    {
        if (!isset($data['data']['used_at'])) {
            $data['data']['used_at'] = date('Y-m-d H:i:s');
        }
        return $data;
    }

    /**
     * Récupérer les utilisations d'un code de parrainage
     */
    public function getByReferralCode(int $referralCodeId): array
    {
        return $this->where('referral_code_id', $referralCodeId)
                   ->orderBy('used_at', 'DESC')
                   ->findAll();
    }

    /**
     * Récupérer les utilisations par parrain
     */
    public function getByReferrer(int $referrerId): array
    {
        return $this->where('referrer_id', $referrerId)
                   ->orderBy('used_at', 'DESC')
                   ->findAll();
    }

    /**
     * Récupérer les utilisations par utilisateur parrainé
     */
    public function getByReferredUser(int $referredUserId): array
    {
        return $this->where('referred_user_id', $referredUserId)
                   ->orderBy('used_at', 'DESC')
                   ->findAll();
    }

    /**
     * Calculer le total des bonus gagnés par un parrain
     */
    public function getTotalBonusEarned(int $referrerId): float
    {
        $result = $this->select('SUM(bonus_earned) as total')
                      ->where('referrer_id', $referrerId)
                      ->first();

        return $result ? (float) $result['total'] : 0.0;
    }

    /**
     * Compter le nombre de parrainages réussis
     */
    public function countSuccessfulReferrals(int $referrerId): int
    {
        return $this->where('referrer_id', $referrerId)
                   ->countAllResults();
    }

    /**
     * Vérifier si un utilisateur a déjà été parrainé
     */
    public function isUserAlreadyReferred(int $userId): bool
    {
        return $this->where('referred_user_id', $userId)
                   ->countAllResults() > 0;
    }
}
