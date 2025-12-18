<?php

namespace App\Models;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $table            = 'reports';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'App\Entities\ReportEntity';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields = [
        'reporter_id',
        'reported_user_id',
        'reported_ad_id',
        'report_type',
        'report_reason',
        'description',
        'evidence_files',
        'status',
        'admin_notes',
        'handled_by',
        'handled_at'
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
        'reporter_id' => 'required|integer|is_not_unique[users.id_user]',
        'reported_user_id' => 'permit_empty|integer|is_not_unique[users.id_user]',
        'reported_ad_id' => 'permit_empty|integer|is_not_unique[ads.id]',
        'report_type' => 'required|in_list[user,ad]',
        'report_reason' => 'required|in_list[spam,fraud,abuse,other]',
        'description' => 'required|string|max_length[1000]',
        'evidence_files' => 'permit_empty|string',
        'status' => 'permit_empty|in_list[pending,handled,rejected]',
        'admin_notes' => 'permit_empty|string|max_length[1000]',
        'handled_by' => 'permit_empty|integer|is_not_unique[users.id_user]'
    ];
    protected $validationMessages   = [
        'reporter_id' => [
            'required' => 'Le rapporteur est obligatoire',
            'integer' => 'ID rapporteur invalide',
            'is_not_unique' => 'Rapporteur non trouvé'
        ],
        'reported_user_id' => [
            'integer' => 'ID utilisateur signalé invalide',
            'is_not_unique' => 'Utilisateur signalé non trouvé'
        ],
        'reported_ad_id' => [
            'integer' => 'ID annonce signalée invalide',
            'is_not_unique' => 'Annonce signalée non trouvée'
        ],
        'report_type' => [
            'required' => 'Le type de signalement est obligatoire',
            'in_list' => 'Type de signalement invalide (user, ad)'
        ],
        'report_reason' => [
            'required' => 'La raison du signalement est obligatoire',
            'in_list' => 'Raison invalide (spam, fraud, abuse, other)'
        ],
        'description' => [
            'required' => 'La description est obligatoire',
            'string' => 'La description doit être une chaîne de caractères',
            'max_length' => 'La description ne peut pas dépasser 1000 caractères'
        ],
        'evidence_files' => [
            'string' => 'Les fichiers de preuve doivent être une chaîne JSON'
        ],
        'status' => [
            'in_list' => 'Statut invalide (pending, handled, rejected)'
        ],
        'admin_notes' => [
            'string' => 'Les notes admin doivent être une chaîne de caractères',
            'max_length' => 'Les notes admin ne peuvent pas dépasser 1000 caractères'
        ],
        'handled_by' => [
            'integer' => 'ID admin invalide',
            'is_not_unique' => 'Admin non trouvé'
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
     * Récupérer les signalements en attente
     */
    public function getPending(int $limit = 50, int $offset = 0): array
    {
        return $this->where('status', 'pending')
                   ->orderBy('created_at', 'ASC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Récupérer les signalements par type
     */
    public function getByType(string $type, int $limit = 50, int $offset = 0): array
    {
        return $this->where('report_type', $type)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Récupérer les signalements d'un utilisateur
     */
    public function getByReporter(int $reporterId, int $limit = 50, int $offset = 0): array
    {
        return $this->where('reporter_id', $reporterId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Récupérer les signalements concernant un utilisateur
     */
    public function getByReportedUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->where('reported_user_id', $userId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Récupérer les signalements concernant une annonce
     */
    public function getByReportedAd(int $adId, int $limit = 50, int $offset = 0): array
    {
        return $this->where('reported_ad_id', $adId)
                   ->orderBy('created_at', 'DESC')
                   ->limit($limit, $offset)
                   ->findAll();
    }

    /**
     * Traiter un signalement (le marquer comme traité)
     */
    public function handle(int $id, int $adminId, string $notes = null): bool
    {
        $data = [
            'status' => 'handled',
            'handled_by' => $adminId,
            'handled_at' => date('Y-m-d H:i:s')
        ];

        if ($notes) {
            $data['admin_notes'] = $notes;
        }

        return $this->update($id, $data);
    }

    /**
     * Rejeter un signalement
     */
    public function reject(int $id, int $adminId, string $notes = null): bool
    {
        $data = [
            'status' => 'rejected',
            'handled_by' => $adminId,
            'handled_at' => date('Y-m-d H:i:s')
        ];

        if ($notes) {
            $data['admin_notes'] = $notes;
        }

        return $this->update($id, $data);
    }

    /**
     * Compter les signalements par statut
     */
    public function countByStatus(): array
    {
        $result = $this->select('status, COUNT(*) as count')
                      ->groupBy('status')
                      ->findAll();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row->status] = (int) $row->count;
        }

        return $counts;
    }

    /**
     * Vérifier si un utilisateur a déjà signalé une annonce/utilisateur
     */
    public function hasAlreadyReported(int $reporterId, ?int $userId = null, ?int $adId = null): bool
    {
        $builder = $this->where('reporter_id', $reporterId);

        if ($userId) {
            $builder->where('reported_user_id', $userId);
        }

        if ($adId) {
            $builder->where('reported_ad_id', $adId);
        }

        return $builder->countAllResults() > 0;
    }
}
