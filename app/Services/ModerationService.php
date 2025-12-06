<?php

namespace App\Services;

use App\Models\AdModel;
use App\Models\UserModel;

class ModerationService
{
    protected $adModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->adModel = new AdModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Approuver une annonce avec traçabilité
     */
    public function approveAd(int $adId, int $moderatorId, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            // Récupérer l'annonce actuelle
            $ad = $this->adModel->find($adId);
            if (!$ad) {
                throw new \RuntimeException('Annonce non trouvée');
            }

            $oldStatus = $ad['moderation_status'];

            // Mettre à jour l'annonce
            $updateData = [
                'moderation_status' => 'approved',
                'moderator_id' => $moderatorId,
                'moderation_notes' => $notes,
                'moderated_at' => date('Y-m-d H:i:s')
            ];

            $success = $this->adModel->update($adId, $updateData);

            if (!$success) {
                throw new \RuntimeException('Échec de la mise à jour de l\'annonce');
            }

            // Créer le log de modération
            $this->logModerationAction(
                $moderatorId,
                'ad_approve',
                'ad',
                $adId,
                $oldStatus,
                'approved',
                null,
                $notes
            );

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Annonce approuvée avec succès',
                'ad_id' => $adId
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Rejeter une annonce avec traçabilité
     */
    public function rejectAd(int $adId, int $moderatorId, string $reason, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            // Récupérer l'annonce actuelle
            $ad = $this->adModel->find($adId);
            if (!$ad) {
                throw new \RuntimeException('Annonce non trouvée');
            }

            $oldStatus = $ad['moderation_status'];

            // Mettre à jour l'annonce
            $updateData = [
                'moderation_status' => 'rejected',
                'moderator_id' => $moderatorId,
                'moderation_notes' => $notes,
                'moderated_at' => date('Y-m-d H:i:s')
            ];

            $success = $this->adModel->update($adId, $updateData);

            if (!$success) {
                throw new \RuntimeException('Échec de la mise à jour de l\'annonce');
            }

            // Créer le log de modération
            $this->logModerationAction(
                $moderatorId,
                'ad_reject',
                'ad',
                $adId,
                $oldStatus,
                'rejected',
                $reason,
                $notes
            );

            // Générer le lien WhatsApp pour notifier le propriétaire
            $whatsappLink = $this->generateWhatsAppLinkForRejection($ad, $reason);

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Annonce rejetée avec succès',
                'ad_id' => $adId,
                'whatsapp_notification_link' => $whatsappLink
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Suspendre un utilisateur
     */
    public function suspendUser(int $userId, int $moderatorId, string $reason, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            // Vérifier que l'utilisateur existe
            $user = $this->userModel->find($userId);
            if (!$user) {
                throw new \RuntimeException('Utilisateur non trouvé');
            }

            // Vérifier que ce n'est pas un admin qui suspend un autre admin
            if ($user['role_id'] == 1 && $moderatorId != $userId) {
                throw new \RuntimeException('Impossible de suspendre un administrateur');
            }

            // Vérifier si l'utilisateur est déjà suspendu
            $isAlreadySuspended = ($user['is_suspended'] ?? 0) == 1;
            if ($isAlreadySuspended) {
                throw new \RuntimeException('Cet utilisateur est déjà suspendu');
            }

            $oldStatus = 'active';

            // Mettre à jour l'utilisateur
            $updateData = [
                'is_suspended' => 1,
                'suspended_at' => date('Y-m-d H:i:s'),
                'suspended_by' => $moderatorId,
                'suspension_reason' => $reason,
            ];

            $success = $this->userModel->update($userId, $updateData);

            if (!$success) {
                throw new \RuntimeException('Échec de la suspension de l\'utilisateur');
            }

            // Créer le log de modération
            $this->logModerationAction(
                $moderatorId,
                'user_suspend',
                'user',
                $userId,
                $oldStatus,
                'suspended',
                $reason,
                $notes
            );

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Utilisateur suspendu avec succès',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Réactiver un utilisateur suspendu
     */
    public function unsuspendUser(int $userId, int $moderatorId, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            // Vérifier que l'utilisateur existe
            $user = $this->userModel->find($userId);
            if (!$user) {
                throw new \RuntimeException('Utilisateur non trouvé');
            }

            $oldStatus = ($user['is_suspended'] ?? 0) ? 'suspended' : 'active';

            // Mettre à jour l'utilisateur
            $updateData = [
                'is_suspended' => 0,
                'unsuspended_at' => date('Y-m-d H:i:s'),
                'unsuspended_by' => $moderatorId,
                'suspension_reason' => null, // Effacer la raison de suspension
            ];

            $success = $this->userModel->update($userId, $updateData);

            if (!$success) {
                throw new \RuntimeException('Échec de la réactivation de l\'utilisateur');
            }

            // Créer le log de modération
            $this->logModerationAction(
                $moderatorId,
                'user_unsuspend',
                'user',
                $userId,
                $oldStatus,
                'active',
                null,
                $notes
            );

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Utilisateur réactivé avec succès',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Supprimer un utilisateur (soft delete avec traçabilité)
     */
    public function deleteUser(int $userId, int $moderatorId, string $reason, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            // Vérifier que l'utilisateur existe
            $user = $this->userModel->find($userId);
            if (!$user) {
                throw new \RuntimeException('Utilisateur non trouvé');
            }

            // Vérifier que ce n'est pas un admin qui supprime un autre admin
            if ($user['role_id'] == 1 && $moderatorId != $userId) {
                throw new \RuntimeException('Impossible de supprimer un administrateur');
            }

            // Soft delete
            $updateData = [
                'deleted' => date('Y-m-d H:i:s'),
                'is_suspended' => 1, // Marquer aussi comme suspendu
                'suspended_at' => date('Y-m-d H:i:s'),
                'suspended_by' => $moderatorId,
                'suspension_reason' => 'Compte supprimé: ' . $reason,
            ];

            $success = $this->userModel->update($userId, $updateData);

            if (!$success) {
                throw new \RuntimeException('Échec de la suppression de l\'utilisateur');
            }

            // Créer le log de modération
            $this->logModerationAction(
                $moderatorId,
                'user_delete',
                'user',
                $userId,
                'active',
                'deleted',
                $reason,
                $notes
            );

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Vérifier l'identité d'un utilisateur
     */
    public function verifyUserIdentity(int $userId, int $moderatorId, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            // Vérifier que l'utilisateur existe
            $user = $this->userModel->find($userId);
            if (!$user) {
                throw new \RuntimeException('Utilisateur non trouvé');
            }

            $oldStatus = (int)($user['is_verified'] ?? (($user['is_identity_verified'] ?? 0) ? 1 : 0));

            // Mettre à jour l'utilisateur
            $updateData = [
                'is_verified' => 1,
                'identity_verified_at' => date('Y-m-d H:i:s'),
            ];

            $success = $this->userModel->update($userId, $updateData);

            if (!$success) {
                throw new \RuntimeException('Échec de la vérification d\'identité');
            }

            // Créer le log de modération
            $this->logModerationAction(
                $moderatorId,
                'identity_verify',
                'user',
                $userId,
                (string)$oldStatus,
                '1',
                null,
                $notes
            );

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Identité vérifiée avec succès',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Rejeter la vérification d'identité
     */
    public function rejectUserIdentity(int $userId, int $moderatorId, string $reason, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            // Vérifier que l'utilisateur existe
            $user = $this->userModel->find($userId);
            if (!$user) {
                throw new \RuntimeException('Utilisateur non trouvé');
            }

            $oldStatus = (int)($user['is_verified'] ?? (($user['is_identity_verified'] ?? 0) ? 1 : 0));

            // Mettre à jour l'utilisateur
            $updateData = [
                'is_verified' => 4,
                'identity_review_reason' => $reason,
                'identity_reviewed_at' => date('Y-m-d H:i:s'),
            ];

            $success = $this->userModel->update($userId, $updateData);

            if (!$success) {
                throw new \RuntimeException('Échec du rejet de la vérification d\'identité');
            }

            // Créer le log de modération
            $this->logModerationAction(
                $moderatorId,
                'identity_reject',
                'user',
                $userId,
                (string)$oldStatus,
                '4',
                $reason,
                $notes
            );

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Vérification d\'identité rejetée',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Demander des corrections sur la vérification d'identité
     */
    public function requestChangesUserIdentity(int $userId, int $moderatorId, string $reason, ?string $notes = null): array
    {
        $this->db->transStart();

        try {
            $user = $this->userModel->find($userId);
            if (!$user) {
                throw new \RuntimeException('Utilisateur non trouvé');
            }

            $oldStatus = (int)($user['is_verified'] ?? (($user['is_identity_verified'] ?? 0) ? 1 : 0));

            $updateData = [
                'is_verified' => 3, // changes requested
                'identity_review_reason' => $reason,
                'identity_reviewed_at' => date('Y-m-d H:i:s'),
            ];

            $success = $this->userModel->update($userId, $updateData);
            if (!$success) {
                throw new \RuntimeException('Échec de la demande de corrections');
            }

            $this->logModerationAction(
                $moderatorId,
                'identity_request_changes',
                'user',
                $userId,
                (string)$oldStatus,
                '3',
                $reason,
                $notes
            );

            $this->db->transComplete();

            return [
                'success' => true,
                'message' => 'Corrections demandées avec succès',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Enregistrer une action de modération dans les logs
     */
    private function logModerationAction(
        int $moderatorId,
        string $actionType,
        string $targetType,
        int $targetId,
        ?string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        ?string $notes = null
    ): void {
        // Récupérer les informations de la requête
        $request = \Config\Services::request();
        $ipAddress = $request->getIPAddress();
        $userAgent = $request->getUserAgent()->getAgentString();

        $data = [
            'moderator_id' => $moderatorId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'notes' => $notes,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Insérer dans la table moderation_logs (si elle existe)
        try {
            $this->db->table('moderation_logs')->insert($data);
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, on log dans le fichier de log
            log_message('error', '[MODERATION] Impossible d\'insérer dans moderation_logs: ' . $e->getMessage());
            log_message('info', '[MODERATION] Action: ' . json_encode($data));
        }
    }

    /**
     * Récupérer les logs de modération
     */
    public function getModerationLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $builder = $this->db->table('moderation_logs');

        // Filtres
        if (!empty($filters['moderator_id'])) {
            $builder->where('moderator_id', $filters['moderator_id']);
        }

        if (!empty($filters['action_type'])) {
            $builder->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['target_type'])) {
            $builder->where('target_type', $filters['target_type']);
        }

        if (!empty($filters['target_id'])) {
            $builder->where('target_id', $filters['target_id']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }

        // Tri
        $builder->orderBy('created_at', 'DESC');

        // Pagination
        $total = $builder->countAllResults(false);
        $logs = $builder->limit($limit, $offset)->get()->getResultArray();

        return [
            'logs' => $logs,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Générer le lien WhatsApp pour notifier le propriétaire du rejet
     */
    private function generateWhatsAppLinkForRejection(array $ad, string $reason): ?string
    {
        try {
            // Récupérer le propriétaire de l'annonce
            $adOwner = $this->userModel->find($ad['user_id']);
            if (!$adOwner || empty($adOwner['phone'])) {
                log_message('warning', "Ad owner or phone not found for ad: {$ad['id']}");
                return null;
            }

            // Nettoyer le numéro (enlever espaces, tirets, parenthèses)
            $cleanPhone = preg_replace('/[^0-9+]/', '', $adOwner['phone']);
            $cleanPhone = ltrim($cleanPhone, '+');

            // S'assurer que le numéro a un code pays
            if (!preg_match('/^\d{1,3}/', $cleanPhone)) {
                $cleanPhone = '237' . $cleanPhone;
            }

            // Préparer le titre de l'annonce
            $adTitle = $ad['title'] ?? 'Votre annonce';

            // Créer le message
            $message = "Bonjour, votre annonce « {$adTitle} » a été rejetée par notre équipe de modération. "
                . "Raison: {$reason}. "
                . "Pour plus d'informations ou pour modifier votre annonce, connectez-vous à Cambizzle.";

            // Générer le lien WhatsApp
            $whatsappLink = "https://wa.me/{$cleanPhone}?text=" . urlencode($message);

            return $whatsappLink;

        } catch (\Exception $e) {
            log_message('error', 'WhatsApp link generation error for rejection: ' . $e->getMessage());
            return null;
        }
    }
}
