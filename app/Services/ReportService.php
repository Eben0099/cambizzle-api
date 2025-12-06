<?php

namespace App\Services;

use App\Models\ReportModel;
use App\Models\UserModel;
use App\Models\AdModel;

class ReportService
{
    protected $reportModel;
    protected $userModel;
    protected $adModel;
    protected $uploadService;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
        $this->userModel = new UserModel();
        $this->adModel = new AdModel();
        $this->uploadService = service('uploadService');
    }

    /**
     * Créer un signalement
     */
    public function createReport(int $reporterId, array $data): int
    {
        // Validation des données
        $this->validateReportData($data);

        // Vérifier que l'utilisateur ne signale pas sa propre annonce/utilisateur
        if (isset($data['reported_ad_id'])) {
            $ad = $this->adModel->find($data['reported_ad_id']);
            if ($ad && $ad->user_id === $reporterId) {
                throw new \RuntimeException('Vous ne pouvez pas signaler votre propre annonce');
            }
        }

        if (isset($data['reported_user_id']) && $data['reported_user_id'] === $reporterId) {
            throw new \RuntimeException('Vous ne pouvez pas vous signaler vous-même');
        }

        // Vérifier que l'utilisateur n'a pas déjà fait ce signalement
        if ($this->reportModel->hasAlreadyReported($reporterId, $data['reported_user_id'] ?? null, $data['reported_ad_id'] ?? null)) {
            throw new \RuntimeException('Vous avez déjà signalé cet élément');
        }

        // Préparer les données
        $reportData = [
            'reporter_id' => $reporterId,
            'report_type' => $data['report_type'],
            'report_reason' => $data['report_reason'],
            'description' => $data['description'],
            'status' => 'pending'
        ];

        if (isset($data['reported_user_id'])) {
            $reportData['reported_user_id'] = $data['reported_user_id'];
        }

        if (isset($data['reported_ad_id'])) {
            $reportData['reported_ad_id'] = $data['reported_ad_id'];
        }

        // Traiter les fichiers de preuve
        if (isset($data['evidence_files']) && is_array($data['evidence_files'])) {
            $uploadedFiles = [];
            foreach ($data['evidence_files'] as $file) {
                if ($file instanceof \CodeIgniter\HTTP\Files\UploadedFile && $file->isValid()) {
                    $uploadedFile = $this->uploadService->uploadEvidenceFile($file);
                    $uploadedFiles[] = $uploadedFile['url'];
                }
            }
            $reportData['evidence_files'] = json_encode($uploadedFiles);
        }

        $reportId = $this->reportModel->insert($reportData, true);

        if (!$reportId) {
            throw new \RuntimeException('Erreur lors de la création du signalement');
        }

        return $reportId;
    }

    /**
     * Récupérer un signalement avec détails
     */
    public function getReport(int $reportId): ?array
    {
        $report = $this->reportModel->find($reportId);

        if (!$report) {
            return null;
        }

        $reportData = $report->toArray();

        // Ajouter les informations des utilisateurs et annonces
        $reportData['reporter'] = $this->userModel->find($report->reporter_id);

        if ($report->reported_user_id) {
            $reportData['reported_user'] = $this->userModel->find($report->reported_user_id);
        }

        if ($report->reported_ad_id) {
            $reportData['reported_ad'] = $this->adModel->find($report->reported_ad_id);
        }

        if ($report->handled_by) {
            $reportData['handler'] = $this->userModel->find($report->handled_by);
        }

        return $reportData;
    }

    /**
     * Récupérer les signalements en attente
     */
    public function getPendingReports(int $limit = 50, int $offset = 0): array
    {
        return $this->reportModel->getPending($limit, $offset);
    }

    /**
     * Récupérer les signalements d'un utilisateur
     */
    public function getUserReports(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->reportModel->getByReporter($userId, $limit, $offset);
    }

    /**
     * Résoudre un signalement
     */
    public function resolveReport(int $reportId, int $adminId, string $notes = null): bool
    {
        return $this->reportModel->resolve($reportId, $adminId, $notes);
    }

    /**
     * Rejeter un signalement
     */
    public function dismissReport(int $reportId, int $adminId, string $notes = null): bool
    {
        return $this->reportModel->dismiss($reportId, $adminId, $notes);
    }

    /**
     * Compter les signalements par statut
     */
    public function countReportsByStatus(): array
    {
        return $this->reportModel->countByStatus();
    }

    /**
     * Rechercher des signalements
     */
    public function searchReports(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $builder = $this->reportModel->builder();

        // Filtres par statut
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        // Filtres par type
        if (!empty($filters['report_type'])) {
            $builder->where('report_type', $filters['report_type']);
        }

        // Recherche textuelle
        if (!empty($filters['query'])) {
            $builder->groupStart()
                    ->like('report_reason', $filters['query'])
                    ->orLike('description', $filters['query'])
                    ->orLike('admin_notes', $filters['query'])
                    ->groupEnd();
        }

        // Filtres par date
        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'DESC';

        $allowedSortFields = ['created_at', 'updated_at', 'status', 'report_type'];
        if (in_array($sortBy, $allowedSortFields)) {
            $builder->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $total = $builder->countAllResults(false);
        $reports = $builder->limit($limit, $offset)->get()->getResult();

        return [
            'reports' => $reports,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Validation des données de signalement
     */
    private function validateReportData(array $data): void
    {
        $required = ['report_type', 'report_reason', 'description'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Le champ {$field} est obligatoire");
            }
        }

        // Vérifier qu'au moins un élément est signalé
        if (empty($data['reported_user_id']) && empty($data['reported_ad_id'])) {
            throw new \InvalidArgumentException('Vous devez signaler soit un utilisateur soit une annonce');
        }

        // Validation des utilisateurs et annonces
        if (!empty($data['reported_user_id']) && !$this->userModel->find($data['reported_user_id'])) {
            throw new \InvalidArgumentException('Utilisateur signalé non trouvé');
        }

        if (!empty($data['reported_ad_id']) && !$this->adModel->find($data['reported_ad_id'])) {
            throw new \InvalidArgumentException('Annonce signalée non trouvée');
        }

        // Validation du type de signalement
        $validTypes = ['user', 'ad', 'spam', 'fraud', 'harassment', 'other'];
        if (!in_array($data['report_type'], $validTypes)) {
            throw new \InvalidArgumentException('Type de signalement invalide');
        }
    }

    /**
     * Récupérer les erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->reportModel->errors();
    }

    /**
     * Notifier le propriétaire de l'annonce via WhatsApp
     */
    public function notifyAdOwnerWhatsApp(int $adId): ?string
    {
        try {
            $ad = $this->adModel->find($adId);
            if (!$ad) {
                log_message('warning', "Ad not found for notification: {$adId}");
                return null;
            }

            $adOwner = $this->userModel->find($ad->user_id);
            if (!$adOwner || empty($adOwner['phone'])) {
                log_message('warning', "Ad owner or phone not found for ad: {$adId}");
                return null;
            }

            return $this->generateWhatsAppLink($adOwner['phone'], $ad);

        } catch (\Exception $e) {
            log_message('error', 'WhatsApp notification error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Générer le lien WhatsApp avec message
     */
    private function generateWhatsAppLink(string $phone, array $ad): string
    {
        // Nettoyer le numéro (enlever espaces, tirets, parenthèses)
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);

        // Enlever le + s'il existe
        $cleanPhone = ltrim($cleanPhone, '+');

        // S'assurer que le numéro a un code pays
        if (!preg_match('/^\d{1,3}/', $cleanPhone)) {
            // Si pas de code pays, ajouter 237 (Cameroun)
            $cleanPhone = '237' . $cleanPhone;
        }

        // Préparer le titre de l'annonce
        $adTitle = $ad['title'] ?? $ad['ad_title'] ?? 'Votre annonce';

        // Créer le message
        $message = "Bonjour, votre annonce « {$adTitle} » a été reportée par un utilisateur. "
            . "Elle est actuellement en attente de modération par notre équipe. "
            . "Connectez-vous à Cambizzle pour plus de détails et pour contester si nécessaire.";

        // Générer le lien WhatsApp
        $whatsappLink = "https://wa.me/{$cleanPhone}?text=" . urlencode($message);

        return $whatsappLink;
    }
}
