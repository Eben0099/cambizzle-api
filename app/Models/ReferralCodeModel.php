<?php
namespace App\Models;
use CodeIgniter\Model;

class ReferralCodeModel extends Model
{
    protected $table = 'referral_codes';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields    = [
        'code', 'user_id', 'current_uses', 'is_active', 'max_uses'
    ];

    // Casts pour les champs spéciaux
    protected array $casts = [
        'max_uses' => 'int',
        'current_uses' => 'int',
        'bonus_amount' => 'float',
        'is_active' => 'boolean'
    ];

    // Dates - Seulement created_at dans la table
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = ''; // Pas de champ updated_at

    // Validation
    protected $validationRules = [
        'code' => 'required|string|max_length[50]|is_unique[referral_codes.code,id,{id}]',
        'user_id' => 'required|integer',
        // Les autres champs sont facultatifs
    ];

    protected $validationMessages = [
        'code' => [
            'required' => 'Le code est obligatoire',
            'string' => 'Le code doit être une chaîne de caractères',
            'max_length' => 'Le code ne peut pas dépasser 50 caractères',
            'is_unique' => 'Ce code existe déjà'
        ],
        'user_id' => [
            'required' => 'L\'utilisateur est obligatoire',
            'integer' => 'ID utilisateur invalide',
            'is_not_unique' => 'Utilisateur non trouvé'
        ],
        'description' => [
            'string' => 'La description doit être une chaîne de caractères',
            'max_length' => 'La description ne peut pas dépasser 255 caractères'
        ],
        'max_uses' => [
            'integer' => 'Le nombre maximum d\'utilisations doit être un entier',
            'greater_than_equal_to' => 'Le nombre maximum d\'utilisations doit être positif'
        ],
        'current_uses' => [
            'integer' => 'Le nombre d\'utilisations actuel doit être un entier',
            'greater_than_equal_to' => 'Le nombre d\'utilisations actuel doit être positif'
        ],
        'bonus_amount' => [
            'required' => 'Le montant du bonus est obligatoire',
            'numeric' => 'Le montant du bonus doit être un nombre',
            'greater_than_equal_to' => 'Le montant du bonus doit être positif ou nul'
        ],
        'is_active' => [
            'in_list' => 'Le champ actif doit être 0 ou 1'
        ],
        'expires_at' => [
            'valid_date' => 'Date d\'expiration invalide'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateCode'];

    protected function generateCode(array $data): array
    {
        if (empty($data['data']['code'])) {
            // Générer un code aléatoire unique
            do {
                $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                $exists = $this->where('code', $code)->first();
            } while ($exists);

            $data['data']['code'] = $code;
        }
        return $data;
    }

    // Méthodes utilitaires
    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    public function getActive(): array
    {
        return $this->where('is_active', 1)
                   ->where('(expires_at IS NULL OR expires_at > NOW())')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    public function incrementUses(int $id): bool
    {
        $code = $this->find($id);
        if (!$code) return false;

        $currentUses = $code['current_uses'] + 1;
        $isActive = $code['is_active'];

        // Désactiver si max_uses atteint
        if ($code['max_uses'] > 0 && $currentUses >= $code['max_uses']) {
            $isActive = false;
        }

        return $this->update($id, [
            'current_uses' => $currentUses,
            'is_active' => $isActive
        ]);
    }

    public function findByCode(string $code): ?array
    {
        return $this->where('code', $code)
                   ->where('is_active', 1)
                   ->where('(expires_at IS NULL OR expires_at > NOW())')
                   ->first();
    }
}
