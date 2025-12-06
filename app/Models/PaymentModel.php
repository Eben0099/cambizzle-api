<?php
namespace App\Models;
use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields    = [
        'user_id', 'ad_id', 'reference', 'amount', 'phone',
        'payment_method', 'status', 'description', 'metadata', 'processed_at'
    ];

    // Casts pour les champs spéciaux
    protected array $casts = [
        'amount' => 'float',
        // 'metadata' => 'json-array' // Désactivé temporairement à cause du double encodage
    ];

    // Dates
    protected $useTimestamps = true;
    protected $createdField = 'created_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer|is_not_unique[users.id_user]',
        'ad_id' => 'required|integer|is_not_unique[ads.id]',
        'reference' => 'required|string|max_length[100]',
        'amount' => 'required|numeric|greater_than[0]',
        'phone' => 'required|string|max_length[30]',
        'payment_method' => 'required|string|max_length[50]',
        'status' => 'permit_empty|in_list[pending,paid,failed,refunded]',
        'description' => 'permit_empty|string|max_length[255]',
        'processed_at' => 'permit_empty|valid_date[Y-m-d H:i:s]'
    ];

    protected $validationMessages = [
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
        'reference' => [
            'required' => 'La référence est obligatoire',
            'string' => 'La référence doit être une chaîne de caractères',
            'max_length' => 'La référence ne peut pas dépasser 100 caractères'
        ],
        'amount' => [
            'required' => 'Le montant est obligatoire',
            'numeric' => 'Le montant doit être un nombre',
            'greater_than' => 'Le montant doit être positif'
        ],
        'phone' => [
            'required' => 'Le numéro de téléphone est obligatoire',
            'string' => 'Le numéro de téléphone doit être une chaîne de caractères',
            'max_length' => 'Le numéro de téléphone ne peut pas dépasser 30 caractères'
        ],
        'payment_method' => [
            'required' => 'La méthode de paiement est obligatoire',
            'string' => 'La méthode de paiement doit être une chaîne de caractères',
            'max_length' => 'La méthode de paiement ne peut pas dépasser 50 caractères'
        ],
        'status' => [
            'in_list' => 'Statut invalide (pending, paid, failed, refunded)'
        ],
        'description' => [
            'string' => 'La description doit être une chaîne de caractères',
            'max_length' => 'La description ne peut pas dépasser 255 caractères'
        ],
        'processed_at' => [
            'valid_date' => 'Date de traitement invalide'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;

    // Méthodes utilitaires
    public function getByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->where('user_id', $userId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    public function getByAd(int $adId, int $limit = 50, int $offset = 0): array
    {
        return $this->where('ad_id', $adId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    public function getPending(): array
    {
        return $this->where('status', 'pending')
                   ->orderBy('created_at', 'ASC')
                   ->findAll();
    }

    public function markAsPaid(int $id): bool
    {
        return $this->update($id, [
            'status' => 'paid',
            'processed_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function markAsFailed(int $id): bool
    {
        return $this->update($id, ['status' => 'failed']);
    }

    public function markAsRefunded(int $id): bool
    {
        return $this->update($id, [
            'status' => 'refunded',
            'processed_at' => date('Y-m-d H:i:s')
        ]);
    }
}
