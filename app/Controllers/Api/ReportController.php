<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Services\ReportService;
use App\Services\AuthService;

class ReportController extends BaseApiController
{
    protected $reportService;
    protected $authService;

    public function __construct()
    {
        $this->reportService = service('reportService');
        $this->authService = service('authService');
    }

    /**
     * POST /api/reports - Créer un signalement
     */
    public function create()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $data = $this->request->getJSON(true);

            // Gérer les fichiers uploadés pour les preuves
            $files = $this->request->getFiles();
            if (!empty($files['evidence_files'])) {
                $data['evidence_files'] = $files['evidence_files'];
            }

            $reportId = $this->reportService->createReport($userId, $data);

            // 🔔 Envoyer notification WhatsApp au propriétaire si c'est une annonce
            $whatsappLink = null;
            if (isset($data['reported_ad_id'])) {
                $whatsappLink = $this->reportService->notifyAdOwnerWhatsApp($data['reported_ad_id']);
            }

            return $this->created([
                'id' => $reportId,
                'whatsapp_notification_link' => $whatsappLink
            ], 'Signalement créé avec succès. Propriétaire notifié.');

        } catch (\InvalidArgumentException $e) {
            return $this->validationError(['error' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }

    /**
     * GET /api/reports/{id} - Détails d'un signalement
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du signalement requis']);
            }

            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $report = $this->reportService->getReport((int)$id);

            if (!$report) {
                return $this->notFound('Signalement non trouvé');
            }

            // Vérifier que l'utilisateur peut voir ce signalement
            if ($report['reporter_id'] !== $userId && !$payload->is_admin) {
                return $this->forbidden('Accès non autorisé');
            }

            return $this->success($report, 'Signalement récupéré avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/reports - Liste des signalements de l'utilisateur
     */
    public function index()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;

            $reports = $this->reportService->getUserReports($userId, $limit, $offset);

            return $this->success([
                'reports' => $reports,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ], 'Signalements récupérés avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/reports/{id}/resolve - Résoudre un signalement (Admin)
     */
    public function resolve($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du signalement requis']);
            }

            // Vérifier l'authentification admin
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));

            // Vérifier les permissions admin
            if (!isset($payload->is_admin) || !$payload->is_admin) {
                return $this->forbidden('Permissions administrateur requises');
            }

            $data = $this->request->getJSON(true);
            $notes = $data['notes'] ?? null;

            $success = $this->reportService->resolveReport((int)$id, $payload->user_id, $notes);

            if (!$success) {
                return $this->serverError('Échec de la résolution');
            }

            return $this->success(null, 'Signalement résolu avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/reports/{id}/dismiss - Rejeter un signalement (Admin)
     */
    public function dismiss($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du signalement requis']);
            }

            // Vérifier l'authentification admin
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));

            // Vérifier les permissions admin
            if (!isset($payload->is_admin) || !$payload->is_admin) {
                return $this->forbidden('Permissions administrateur requises');
            }

            $data = $this->request->getJSON(true);
            $notes = $data['notes'] ?? null;

            $success = $this->reportService->dismissReport((int)$id, $payload->user_id, $notes);

            if (!$success) {
                return $this->serverError('Échec du rejet');
            }

            return $this->success(null, 'Signalement rejeté avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/reports/stats - Statistiques des signalements (Admin)
     */
    public function stats()
    {
        try {
            // Vérifier l'authentification admin
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));

            // Vérifier les permissions admin
            if (!isset($payload->is_admin) || !$payload->is_admin) {
                return $this->forbidden('Permissions administrateur requises');
            }

            $stats = $this->reportService->countReportsByStatus();

            return $this->success($stats, 'Statistiques récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/reports/admin/pending - Signalements en attente (Admin)
     */
    public function pending()
    {
        try {
            // Vérifier l'authentification admin
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));

            // Vérifier les permissions admin
            if (!isset($payload->is_admin) || !$payload->is_admin) {
                return $this->forbidden('Permissions administrateur requises');
            }

            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;

            $reports = $this->reportService->getPendingReports($limit, $offset);

            return $this->success([
                'reports' => $reports,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ], 'Signalements en attente récupérés avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
