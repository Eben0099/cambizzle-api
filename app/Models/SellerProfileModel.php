<?php
namespace App\Models;
use CodeIgniter\Model;

class SellerProfileModel extends Model {
    protected $table = 'seller_profiles';
    protected $primaryKey = 'id';
    protected $returnType = 'App\\Entities\\SellerProfileEntity';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields    = [
        'user_id',
        'business_name',
        'business_description',
        'business_address',
        'business_phone',
        'business_email',
        'opening_hours',
        'delivery_options',
        'website_url',
        'facebook_url',
        'instagram_url',
        'logo_url',
        'is_verified',
        'verification_status',
        'rejection_reason',
        'verified_at',
        'is_active',
        'created_at',
        'updated_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Casts pour les champs booléens uniquement
    protected array $casts = [
        'is_active' => 'bool',
        'is_verified' => 'bool'
    ];

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer|is_not_unique[users.id_user]',
        'business_name' => 'required|string|max_length[255]',
        'business_description' => 'permit_empty|string|max_length[1000]',
        'business_address' => 'permit_empty|string|max_length[500]',
        'business_phone' => 'permit_empty|string|max_length[20]',
        'business_email' => 'permit_empty|valid_email|max_length[255]',
        'opening_hours' => 'permit_empty',
        'delivery_options' => 'permit_empty',
        'website_url' => 'permit_empty|valid_url|max_length[500]',
        'facebook_url' => 'permit_empty|valid_url|max_length[500]',
        'instagram_url' => 'permit_empty|valid_url|max_length[500]',
        'logo_url' => 'permit_empty|string|max_length[500]',
        'is_verified' => 'permit_empty|in_list[0,1]',
        'verification_status' => 'permit_empty|in_list[pending,verified,rejected]',
        'rejection_reason' => 'permit_empty|string',
        'verified_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'is_active' => 'permit_empty|in_list[0,1]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'L\'utilisateur est obligatoire',
            'integer' => 'ID utilisateur invalide',
            'is_not_unique' => 'Utilisateur non trouvé'
        ],
        'business_name' => [
            'required' => 'Le nom de l\'entreprise est obligatoire',
            'string' => 'Le nom doit être une chaîne de caractères',
            'max_length' => 'Le nom ne peut pas dépasser 255 caractères'
        ],
        'business_description' => [
            'string' => 'La description doit être une chaîne de caractères',
            'max_length' => 'La description ne peut pas dépasser 1000 caractères'
        ],
        'business_email' => [
            'valid_email' => 'L\'email doit être valide',
            'max_length' => 'L\'email ne peut pas dépasser 255 caractères'
        ],
        'website_url' => [
            'valid_url' => 'L\'URL du site web doit être valide',
            'max_length' => 'L\'URL ne peut pas dépasser 500 caractères'
        ],
        'facebook_url' => [
            'valid_url' => 'L\'URL Facebook doit être valide',
            'max_length' => 'L\'URL Facebook ne peut pas dépasser 500 caractères'
        ],
        'instagram_url' => [
            'valid_url' => 'L\'URL Instagram doit être valide',
            'max_length' => 'L\'URL Instagram ne peut pas dépasser 500 caractères'
        ],
        'logo_url' => [
            'string' => 'L\'URL du logo doit être une chaîne de caractères',
            'max_length' => 'L\'URL du logo ne peut pas dépasser 500 caractères'
        ],
        'is_verified' => [
            'in_list' => 'Le champ vérifié doit être 0 ou 1'
        ],
        'verification_status' => [
            'in_list' => 'Statut de vérification invalide'
        ],
        'verified_at' => [
            'valid_date' => 'Date de vérification invalide'
        ],
        'is_active' => [
            'in_list' => 'Le champ actif doit être 0 ou 1'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['encodeJsonFields'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = ['encodeJsonFields'];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = ['decodeJsonFields'];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Récupérer les profils vendeurs paginés
     */
    public function getSellerProfiles($perPage = 10, $page = 1, $search = null, $filters = [])
    {
        $builder = $this->builder();

        // Recherche
        if (!empty($search)) {
            $builder->groupStart()
                ->like('business_name', $search)
                ->orLike('business_description', $search)
                ->orLike('business_email', $search)
                ->groupEnd();
        }

        // Filtres
        if (!empty($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        // Pagination
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);

        // Tri
        $builder->orderBy('created_at', 'DESC');

        $query = $builder->get();
        $results = $query->getResultArray();

        // Décoder manuellement les champs JSON pour les résultats array
        foreach ($results as &$result) {
            if (isset($result['opening_hours']) && is_string($result['opening_hours'])) {
                $decoded = json_decode($result['opening_hours'], true);
                $result['opening_hours'] = $decoded !== null ? $decoded : $result['opening_hours'];
            }
            if (isset($result['delivery_options']) && is_string($result['delivery_options'])) {
                $decoded = json_decode($result['delivery_options'], true);
                $result['delivery_options'] = $decoded !== null ? $decoded : $result['delivery_options'];
            }
        }

        return $results;
    }

    /**
     * Compter les profils vendeurs
     */
    public function countSellerProfiles($search = null, $filters = [])
    {
        $builder = $this->builder();

        // Recherche
        if (!empty($search)) {
            $builder->groupStart()
                ->like('business_name', $search)
                ->orLike('business_description', $search)
                ->orLike('business_email', $search)
                ->groupEnd();
        }

        // Filtres
        if (!empty($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        return $builder->countAllResults();
    }

    /**
     * Callback : Encoder les champs JSON avant insertion/mise à jour
     */
    protected function encodeJsonFields(array $data): array
    {
        if (isset($data['data']['opening_hours']) && is_array($data['data']['opening_hours'])) {
            $data['data']['opening_hours'] = json_encode($data['data']['opening_hours']);
        }
        if (isset($data['data']['delivery_options']) && is_array($data['data']['delivery_options'])) {
            $data['data']['delivery_options'] = json_encode($data['data']['delivery_options']);
        }
        return $data;
    }

    /**
     * Callback : Décoder les champs JSON après récupération
     */
    protected function decodeJsonFields(array $data): array
    {
        if (isset($data['opening_hours']) && is_string($data['opening_hours'])) {
            $decoded = json_decode($data['opening_hours'], true);
            $data['opening_hours'] = $decoded !== null ? $decoded : $data['opening_hours'];
        }
        if (isset($data['delivery_options']) && is_string($data['delivery_options'])) {
            $decoded = json_decode($data['delivery_options'], true);
            $data['delivery_options'] = $decoded !== null ? $decoded : $data['delivery_options'];
        }
        return $data;
    }
}
