<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use Config\Services;

class AdminController extends BaseApiController
{
    protected $userService;
    protected $adService;
    protected $reportService;
    protected $authService;
    protected $moderationService;

    public function __construct()
    {
        $this->userService = Services::userService();
        $this->adService = Services::adService();
        $this->reportService = Services::reportService();
        $this->authService = Services::authService();
        $this->moderationService = Services::moderationService();
    }


    public function users()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 20;
            $page = $this->request->getGet('page') ?? 1;
            $search = $this->request->getGet('search');
            $filters = $this->request->getGet('filters') ?? [];
            $result = $this->userService->getUsersPaginated($perPage, $page, $search, $filters);
            return $this->success($result, 'Utilisateurs récupérés');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function verifyUser($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID utilisateur requis']);
            }
            $success = $this->userService->update($id, [
                'is_identity_verified' => true,
                'identity_verified_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$success) {
                return $this->serverError('Échec de la vérification');
            }
            return $this->success(null, 'Utilisateur vérifié');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function pendingAds()
    {
        try {
            $builder = model('App\\Models\\AdModel')->where('moderation_status', 'pending');
            $ads = $builder->orderBy('created_at', 'DESC')->findAll();
            return $this->success($ads, 'Annonces en attente');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function approveAd($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID annonce requis']);
            }

            $data = $this->request->getJSON(true);
            $notes = $data['notes'] ?? null;

            // Récupérer l'admin depuis le token
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->approveAd((int)$id, (int)$payload->user_id, $notes);

            return $this->success($result, 'Annonce approuvée avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function rejectAd($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID annonce requis']);
            }

            $data = $this->request->getJSON(true);
            $reason = $data['reason'] ?? '';
            $notes = $data['notes'] ?? null;

            if (empty($reason)) {
                return $this->validationError(['reason' => 'Raison du rejet requise']);
            }

            // Récupérer l'admin depuis le token
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->rejectAd((int)$id, (int)$payload->user_id, $reason, $notes);

            return $this->success($result, 'Annonce rejetée avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function reports()
    {
        try {
            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;
            $reports = $this->reportService->getPendingReports($limit, $offset);
            return $this->success(['reports' => $reports], 'Rapports');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function resolveReport($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID signalement requis']);
            }
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));
            $notes = $this->request->getJSON(true)['notes'] ?? null;
            $success = $this->reportService->resolveReport((int)$id, (int)$payload->user_id, $notes);
            if (!$success) {
                return $this->serverError('Échec de la résolution');
            }
            return $this->success(null, 'Signalement résolu');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Suspendre un utilisateur
     */
    public function suspendUser($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID utilisateur requis']);
            }

            $data = $this->request->getJSON(true);
            $reason = $data['reason'] ?? '';
            $notes = $data['notes'] ?? null;

            if (empty($reason)) {
                return $this->validationError(['reason' => 'Raison de suspension requise']);
            }

            // Récupérer l'admin depuis le token
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->suspendUser((int)$id, (int)$payload->user_id, $reason, $notes);

            return $this->success($result, 'Utilisateur suspendu avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Réactiver un utilisateur suspendu
     */
    public function unsuspendUser($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID utilisateur requis']);
            }

            $data = $this->request->getJSON(true);
            $notes = $data['notes'] ?? null;

            // Récupérer l'admin depuis le token
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->unsuspendUser((int)$id, (int)$payload->user_id, $notes);

            return $this->success($result, 'Utilisateur réactivé avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID utilisateur requis']);
            }

            $data = $this->request->getJSON(true);
            $reason = $data['reason'] ?? '';
            $notes = $data['notes'] ?? null;

            if (empty($reason)) {
                return $this->validationError(['reason' => 'Raison de suppression requise']);
            }

            // Récupérer l'admin depuis le token
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->deleteUser((int)$id, (int)$payload->user_id, $reason, $notes);

            return $this->success($result, 'Utilisateur supprimé avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Vérifier l'identité d'un utilisateur
     */
    public function verifyUserIdentity($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID utilisateur requis']);
            }

            // Vérifier que le service de modération est disponible
            if (!$this->moderationService) {
                log_message('error', 'ModerationService is null in AdminController::verifyUserIdentity');
                return $this->serverError('Service de modération non disponible');
            }

            $data = $this->request->getJSON(true);
            $notes = $data['notes'] ?? null;

            // Récupérer l'admin depuis le token
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->verifyUserIdentity((int)$id, (int)$payload->user_id, $notes);

            return $this->success($result, 'Identité vérifiée avec succès');
        } catch (\Exception $e) {
            log_message('error', 'AdminController::verifyUserIdentity error: ' . $e->getMessage());
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Rejeter la vérification d'identité
     */
    public function rejectUserIdentity($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID utilisateur requis']);
            }

            // Vérifier que le service de modération est disponible
            if (!$this->moderationService) {
                log_message('error', 'ModerationService is null in AdminController::rejectUserIdentity');
                return $this->serverError('Service de modération non disponible');
            }

            $data = $this->request->getJSON(true);
            $reason = $data['reason'] ?? '';
            $notes = $data['notes'] ?? null;

            if (empty($reason)) {
                return $this->validationError(['reason' => 'Raison du rejet requise']);
            }

            // Récupérer l'admin depuis le token
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->rejectUserIdentity((int)$id, (int)$payload->user_id, $reason, $notes);

            return $this->success($result, 'Vérification d\'identité rejetée');
        } catch (\Exception $e) {
            log_message('error', 'AdminController::rejectUserIdentity error: ' . $e->getMessage());
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Demander des corrections sur la vérification d'identité
     */
    public function requestChangesUserIdentity($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID utilisateur requis']);
            }

            if (!$this->moderationService) {
                log_message('error', 'ModerationService is null in AdminController::requestChangesUserIdentity');
                return $this->serverError('Service de modération non disponible');
            }

            $data = $this->request->getJSON(true);
            $reason = $data['reason'] ?? '';
            $notes = $data['notes'] ?? null;
            if (empty($reason)) {
                return $this->validationError(['reason' => 'Raison des corrections requise']);
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $this->request->getHeaderLine('Authorization')));

            $result = $this->moderationService->requestChangesUserIdentity((int)$id, (int)$payload->user_id, $reason, $notes);

            return $this->success($result, 'Corrections demandées avec succès');
        } catch (\Exception $e) {
            log_message('error', 'AdminController::requestChangesUserIdentity error: ' . $e->getMessage());
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Logs de modération
     */
    public function moderationLogs()
    {
        try {
            $filters = $this->request->getGet();
            $limit = $filters['limit'] ?? 50;
            $offset = $filters['offset'] ?? 0;

            // Nettoyer les filtres
            unset($filters['limit'], $filters['offset']);

            $result = $this->moderationService->getModerationLogs($filters, $limit, $offset);

            return $this->success($result, 'Logs de modération récupérés');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Statistiques détaillées pour le dashboard
     */
    public function dashboard()
    {
        try {
            $db = \Config\Database::connect();

            // Statistiques utilisateurs
            $usersStats = $db->table('users')
                ->select('COUNT(*) as total_users, SUM(CASE WHEN is_suspended = 1 THEN 1 ELSE 0 END) as suspended_users, SUM(CASE WHEN is_identity_verified = 1 THEN 1 ELSE 0 END) as verified_users, SUM(CASE WHEN deleted IS NOT NULL THEN 1 ELSE 0 END) as deleted_users')
                ->get()
                ->getRowArray();

            // Statistiques annonces
            $adsStats = $db->table('ads')
                ->select('COUNT(*) as total_ads, SUM(CASE WHEN moderation_status = "pending" THEN 1 ELSE 0 END) as pending_ads, SUM(CASE WHEN moderation_status = "approved" THEN 1 ELSE 0 END) as approved_ads, SUM(CASE WHEN moderation_status = "rejected" THEN 1 ELSE 0 END) as rejected_ads, SUM(view_count) as total_views')
                ->get()
                ->getRowArray();

            // Statistiques signalements
            $reportsStats = $db->table('reports')
                ->select('COUNT(*) as total_reports, SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_reports, SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved_reports')
                ->get()
                ->getRowArray();

            // Messages des 7 derniers jours
            $messages7Days = $db->table('messages')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->countAllResults();

            // Annonces créées cette semaine
            $adsThisWeek = $db->table('ads')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->countAllResults();

            // Utilisateurs inscrits cette semaine
            $usersThisWeek = $db->table('users')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->where('deleted IS NULL')
                ->countAllResults();

            // Top catégories
            $topCategories = $db->table('ads a')
                ->select('c.name, COUNT(a.id) as ad_count')
                ->join('subcategories s', 'a.subcategory_id = s.id')
                ->join('categories c', 's.category_id = c.id')
                ->where('a.moderation_status', 'approved')
                ->where('a.status', 'active')
                ->groupBy('c.id')
                ->orderBy('ad_count', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();

            // Répartition par statut d'annonce
            $adsByStatus = $db->table('ads')
                ->select('moderation_status, COUNT(*) as count')
                ->groupBy('moderation_status')
                ->get()
                ->getResultArray();

            $data = [
                'users' => [
                    'total' => (int)($usersStats['total_users'] ?? 0),
                    'suspended' => (int)($usersStats['suspended_users'] ?? 0),
                    'verified' => (int)($usersStats['verified_users'] ?? 0),
                    'deleted' => (int)($usersStats['deleted_users'] ?? 0),
                    'active' => (int)($usersStats['total_users'] ?? 0) - (int)($usersStats['suspended_users'] ?? 0) - (int)($usersStats['deleted_users'] ?? 0),
                    'new_this_week' => $usersThisWeek
                ],
                'ads' => [
                    'total' => (int)($adsStats['total_ads'] ?? 0),
                    'pending' => (int)($adsStats['pending_ads'] ?? 0),
                    'approved' => (int)($adsStats['approved_ads'] ?? 0),
                    'rejected' => (int)($adsStats['rejected_ads'] ?? 0),
                    'total_views' => (int)($adsStats['total_views'] ?? 0),
                    'new_this_week' => $adsThisWeek,
                    'by_status' => $adsByStatus
                ],
                'reports' => [
                    'total' => (int)($reportsStats['total_reports'] ?? 0),
                    'pending' => (int)($reportsStats['pending_reports'] ?? 0),
                    'resolved' => (int)($reportsStats['resolved_reports'] ?? 0)
                ],
                'activity' => [
                    'messages_7_days' => $messages7Days,
                    'ads_7_days' => $adsThisWeek,
                    'users_7_days' => $usersThisWeek
                ],
                'top_categories' => $topCategories
            ];

            return $this->success($data, 'Statistiques du dashboard');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}


