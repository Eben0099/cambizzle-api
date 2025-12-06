<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table            = 'messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'App\Entities\MessageEntity';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields = [
        'user_id',
        'ad_id',
        'parent_id',
        'type',
        'content',
        'rating',
        'images',
        'status'
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
        'user_id' => 'required|integer|is_not_unique[users.id_user]',
        'ad_id' => 'required|integer|is_not_unique[ads.id]',
        'parent_id' => 'permit_empty|integer|is_not_unique[messages.id]',
        'type' => 'required|in_list[comment,question,answer,review]',
        'content' => 'required|string|max_length[2000]',
        'rating' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[5]',
        'images' => 'permit_empty|string',
        'status' => 'permit_empty|in_list[visible,hidden,deleted]'
    ];
    protected $validationMessages   = [
        'user_id' => [
            'required' => 'L\'utilisateur est obligatoire',
            'integer' => 'ID utilisateur invalide',
            'is_not_unique' => 'Utilisateur non trouvé'
        ],
        'ad_id' => [
            'required' => 'L\'annonce est obligatoire',
            'integer' => 'ID annonce invalide',
            'is_not_unique' => 'Annonce non trouvée'
        ],
        'parent_id' => [
            'integer' => 'ID parent invalide',
            'is_not_unique' => 'Message parent non trouvé'
        ],
        'type' => [
            'required' => 'Le type de message est obligatoire',
            'in_list' => 'Type de message invalide (comment, question, answer, review)'
        ],
        'content' => [
            'required' => 'Le contenu du message est obligatoire',
            'string' => 'Le contenu doit être une chaîne de caractères',
            'max_length' => 'Le contenu ne peut pas dépasser 2000 caractères'
        ],
        'rating' => [
            'integer' => 'La note doit être un entier',
            'greater_than' => 'La note doit être supérieure à 0',
            'less_than_equal_to' => 'La note ne peut pas dépasser 5'
        ],
        'status' => [
            'in_list' => 'Statut invalide (visible, hidden, deleted)'
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
     * Récupérer les messages d'une annonce
     */
    public function getByAd(int $adId, int $limit = 50, int $offset = 0): array
    {
        return $this->where('ad_id', $adId)
                   ->where('status !=', 'deleted')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Récupérer les messages d'un utilisateur
     */
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->where('user_id', $userId)
                   ->where('status !=', 'deleted')
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Récupérer les messages visibles d'un utilisateur
     */
    public function getVisibleByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
                   ->where('status', 'visible')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Compter les messages visibles
     */
    public function countVisibleByUser(int $userId): int
    {
        return $this->where('user_id', $userId)
                   ->where('status', 'visible')
                   ->countAllResults();
    }

    /**
     * Récupérer les réponses à un message
     */
    public function getReplies(int $parentId): array
    {
        return $this->where('parent_id', $parentId)
                   ->where('status !=', 'deleted')
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }

    /**
     * Marquer un message comme visible
     */
    public function markAsVisible(int $id): bool
    {
        return $this->update($id, ['status' => 'visible']);
    }

    /**
     * Masquer un message
     */
    public function hide(int $id): bool
    {
        return $this->update($id, ['status' => 'hidden']);
    }

    /**
     * Supprimer un message (soft delete)
     */
    public function softDelete(int $id): bool
    {
        return $this->update($id, ['status' => 'deleted']);
    }

    /**
     * Récupérer les messages avec notation
     */
    public function getReviewsByAd(int $adId): array
    {
        return $this->where('ad_id', $adId)
                   ->where('type', 'review')
                   ->where('rating IS NOT NULL')
                   ->where('status !=', 'deleted')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * Calculer la moyenne des notes d'une annonce
     */
    public function getAverageRating(int $adId): ?float
    {
        $result = $this->select('AVG(rating) as avg_rating')
                      ->where('ad_id', $adId)
                      ->where('type', 'review')
                      ->where('rating IS NOT NULL')
                      ->where('status !=', 'deleted')
                      ->first();

        return $result ? (float) $result->avg_rating : null;
    }
}
