<?php
namespace App\Services;

use App\Models\VerificationModel;

class VerificationService
{
    protected $model;
    protected $errors = [];

    public function __construct(VerificationModel $model)
    {
        $this->model = $model;
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function create(array $data)
    {
        return $this->model->insert($data);
    }

    public function update($id, array $data)
    {
        return $this->model->update($id, $data);
    }

    public function delete($id)
    {
        return $this->model->delete($id);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Créer une demande de vérification
     */
    public function createVerificationRequest(int $userId, string $type, array $data): int
    {
        $verificationData = [
            'user_id' => $userId,
            'verification_type' => $type,
            'status' => 'pending',
            'data' => json_encode($data),
            'requested_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($verificationData);
    }

    /**
     * Approuver une vérification
     */
    public function approveVerification(int $verificationId, int $adminId, string $notes = ''): bool
    {
        return $this->update($verificationId, [
            'status' => 'approved',
            'verified_by' => $adminId,
            'verified_at' => date('Y-m-d H:i:s'),
            'admin_notes' => $notes
        ]);
    }

    /**
     * Rejeter une vérification
     */
    public function rejectVerification(int $verificationId, int $adminId, string $reason): bool
    {
        return $this->update($verificationId, [
            'status' => 'rejected',
            'verified_by' => $adminId,
            'verified_at' => date('Y-m-d H:i:s'),
            'admin_notes' => $reason
        ]);
    }

    /**
     * Obtenir les vérifications en attente
     */
    public function getPendingVerifications($perPage = 10, $page = 1): array
    {
        $verifications = $this->model->getPendingVerifications($perPage, $page);
        $total = $this->model->countPendingVerifications();

        return [
            'verifications' => $verifications,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Obtenir les vérifications par utilisateur
     */
    public function getVerificationsByUser(int $userId): array
    {
        return $this->model->where('user_id', $userId)
                          ->orderBy('created_at', 'DESC')
                          ->findAll();
    }

    /**
     * Vérifier si un utilisateur a une vérification en cours
     */
    public function hasPendingVerification(int $userId, string $type): bool
    {
        $verification = $this->model->where('user_id', $userId)
                                   ->where('verification_type', $type)
                                   ->where('status', 'pending')
                                   ->first();

        return $verification !== null;
    }
}
