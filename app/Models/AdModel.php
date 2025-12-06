<?php
namespace App\Models;
use CodeIgniter\Model;

class AdModel extends Model
{
    protected $table = 'ads';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $useSoftDeletes = false; // Pas de soft deletes dans la BD
    protected $protectFields = true;

    protected $allowedFields    = [
        'user_id', 'location_id', 'subcategory_id', 'brand_id',
        'slug', 'title', 'description',
        'price', 'original_price', 'discount_percentage', 'has_discount',
        'is_negotiable', 'referral_code',
        'status', 'moderation_status', 'moderation_notes', 'moderated_at', 'moderator_id',
        'view_count', 'expires_at',
        // Champs boost
        'is_boosted', 'boost_start', 'boost_end'
    ];

    // Casts pour les champs booléens et numériques
    protected array $casts = [
        'has_discount' => 'boolean',
        'is_negotiable' => 'boolean',
        'view_count' => 'int',
        'price' => '?float',  // ? permet les valeurs NULL
        'original_price' => '?float',  // ? permet les valeurs NULL
        'discount_percentage' => '?int', // ? permet les valeurs NULL
        'brand_id' => '?int',  // ? permet les valeurs NULL
        'moderator_id' => '?int' // ? permet les valeurs NULL
    ];

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer|is_not_unique[users.id_user]',
        'location_id' => 'required|integer|is_not_unique[locations.id]',
        'subcategory_id' => 'required|integer|is_not_unique[subcategories.id]',
        'brand_id' => 'permit_empty|integer|is_not_unique[brands.id]',
        'title' => 'required|string|max_length[150]',
        'description' => 'permit_empty|string|max_length[2000]',
        'price' => 'required|numeric|greater_than[0]',
        'original_price' => 'permit_empty|numeric|greater_than[0]',
        'discount_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        'has_discount' => 'permit_empty|in_list[0,1]',
        'is_negotiable' => 'permit_empty|in_list[0,1]',
        'referral_code' => 'permit_empty|string|max_length[50]',
        'status' => 'permit_empty|in_list[active,inactive,expired,deleted]',
        'moderation_status' => 'permit_empty|in_list[pending,approved,rejected]',
        'moderation_notes' => 'permit_empty|string|max_length[255]',
        'moderator_id' => 'permit_empty|integer|is_not_unique[users.id_user]',
        'view_count' => 'permit_empty|integer|greater_than_equal_to[0]',
        'expires_at' => 'permit_empty|valid_date[Y-m-d H:i:s]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => 'L\'utilisateur est obligatoire',
            'integer' => 'ID utilisateur invalide',
            'is_not_unique' => 'Utilisateur non trouvé'
        ],
        'location_id' => [
            'required' => 'La localisation est obligatoire',
            'integer' => 'ID localisation invalide',
            'is_not_unique' => 'Localisation non trouvée'
        ],
        'subcategory_id' => [
            'required' => 'La sous-catégorie est obligatoire',
            'integer' => 'ID sous-catégorie invalide',
            'is_not_unique' => 'Sous-catégorie non trouvée'
        ],
        'brand_id' => [
            'integer' => 'ID marque invalide',
            'is_not_unique' => 'Marque non trouvée'
        ],
        'title' => [
            'required' => 'Le titre est obligatoire',
            'string' => 'Le titre doit être une chaîne de caractères',
            'max_length' => 'Le titre ne peut pas dépasser 150 caractères'
        ],
        'description' => [
            'string' => 'La description doit être une chaîne de caractères',
            'max_length' => 'La description ne peut pas dépasser 2000 caractères'
        ],
        'price' => [
            'required' => 'Le prix est obligatoire',
            'numeric' => 'Le prix doit être un nombre',
            'greater_than' => 'Le prix doit être supérieur à 0'
        ],
        'original_price' => [
            'numeric' => 'Le prix original doit être un nombre',
            'greater_than' => 'Le prix original doit être supérieur à 0'
        ],
        'discount_percentage' => [
            'integer' => 'Le pourcentage de remise doit être un entier',
            'greater_than_equal_to' => 'Le pourcentage de remise doit être positif',
            'less_than_equal_to' => 'Le pourcentage de remise ne peut pas dépasser 100%'
        ],
        'referral_code' => [
            'string' => 'Le code de parrainage doit être une chaîne de caractères',
            'max_length' => 'Le code de parrainage ne peut pas dépasser 50 caractères'
        ],
        'status' => [
            'in_list' => 'Statut invalide (active, inactive, expired, deleted)'
        ],
        'moderation_status' => [
            'in_list' => 'Statut de modération invalide (pending, approved, rejected)'
        ],
        'moderation_notes' => [
            'string' => 'Les notes de modération doivent être une chaîne de caractères',
            'max_length' => 'Les notes de modération ne peuvent pas dépasser 255 caractères'
        ],
        'moderator_id' => [
            'integer' => 'ID modérateur invalide',
            'is_not_unique' => 'Modérateur non trouvé'
        ],
        'view_count' => [
            'integer' => 'Le compteur de vues doit être un entier',
            'greater_than_equal_to' => 'Le compteur de vues doit être positif'
        ],
        'expires_at' => [
            'valid_date' => 'Date d\'expiration invalide'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateSlug'];
    protected $beforeUpdate   = ['generateSlug'];

    protected function generateSlug(array $data)
    {
        if (isset($data['data']['title']) && !empty(trim($data['data']['title']))) {
            // Utiliser la fonction helper pour générer un slug sans accents
            helper('slug_helper');
            $slug = generate_ad_slug($data['data']['title']);
            $data['data']['slug'] = $slug;
        }
        return $data;
    }

    // Relations
    public function getWithPhotos(int $adId): ?array
    {
        $ad = $this->find($adId);
        if (!$ad) return null;

        $photoModel = new AdPhotoModel();
        $ad['photos'] = $photoModel->getByAd($adId);

        return $ad;
    }

    public function getAllWithMainPhoto(): array
    {
        $ads = $this->findAll();
        $photoModel = new AdPhotoModel();

        foreach ($ads as &$ad) {
            $mainPhoto = $photoModel->getMainPhoto($ad['id']);
            $ad['main_photo'] = $mainPhoto ? $mainPhoto['original_url'] : null;
        }

        return $ads;
    }

    // Méthodes utilitaires
    public function getActiveAds($perPage = 10, $page = 1, $search = null, $filters = [])
    {
        $builder = $this->builder();

        // Recherche
        if (!empty($search)) {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        // Filtres
        $builder->where('status', 'active')
                ->where('moderation_status', 'approved');

        if (!empty($filters['subcategory_id'])) {
            $builder->where('subcategory_id', $filters['subcategory_id']);
        }

        if (!empty($filters['brand_id'])) {
            $builder->where('brand_id', $filters['brand_id']);
        }

        if (!empty($filters['location_id'])) {
            $builder->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['min_price'])) {
            $builder->where('price >=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $builder->where('price <=', $filters['max_price']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'DESC';
        $builder->orderBy($sortBy, $sortOrder);

        // Pagination
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);

        $query = $builder->get();
        return $query->getResultArray();
    }

    public function countActiveAds($search = null, $filters = [])
    {
        $builder = $this->builder();

        // Recherche
        if (!empty($search)) {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        // Filtres
        $builder->where('status', 'active')
                ->where('moderation_status', 'approved');

        if (!empty($filters['subcategory_id'])) {
            $builder->where('subcategory_id', $filters['subcategory_id']);
        }

        if (!empty($filters['brand_id'])) {
            $builder->where('brand_id', $filters['brand_id']);
        }

        if (!empty($filters['location_id'])) {
            $builder->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['min_price'])) {
            $builder->where('price >=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $builder->where('price <=', $filters['max_price']);
        }

        return $builder->countAllResults();
    }
}