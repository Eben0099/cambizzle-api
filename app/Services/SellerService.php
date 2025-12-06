<?php
namespace App\Services;

use App\Models\SellerProfileModel;
use App\Services\UploadService;

class SellerService
{
    protected $model;
    protected $uploadService;
    protected $errors = [];

    public function __construct(SellerProfileModel $model, UploadService $uploadService)
    {
        $this->model = $model;
        $this->uploadService = $uploadService;
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
     * Créer un profil vendeur
     */
    public function createSellerProfile(int $userId, array $data): int
    {
        $data['user_id'] = $userId;
        $data['is_verified'] = 0; // En attente de vérification (0 = false)
        $data['verification_status'] = 'pending';
        $data['is_active'] = $data['is_active'] ?? 1; // 1 pour actif, 0 pour inactif

        // Convertir les valeurs booléennes en entiers pour la validation
        if (isset($data['is_verified']) && is_bool($data['is_verified'])) {
            $data['is_verified'] = $data['is_verified'] ? 1 : 0;
        }
        if (isset($data['is_active']) && is_bool($data['is_active'])) {
            $data['is_active'] = $data['is_active'] ? 1 : 0;
        }

        // Gérer l'upload de logo si présent
        if (isset($data['logo']) && $data['logo'] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $uploadPath = FCPATH . 'uploads/seller_logos/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if ($data['logo']->isValid() && !$data['logo']->hasMoved()) {
                $newName = $data['logo']->getRandomName();
                $data['logo']->move($uploadPath, $newName);
                $data['logo_url'] = 'uploads/seller_logos/' . $newName;
            }
            unset($data['logo']);
        }

        $insertResult = $this->model->insert($data);
        if ($insertResult === false) {
            $errors = $this->model->errors();
            throw new \RuntimeException('Erreur lors de la création du profil vendeur: ' . json_encode($errors));
        }

        // Récupérer l'ID du profil créé
        $profileId = $this->model->getInsertID();
        if (!$profileId) {
            throw new \RuntimeException('Impossible de récupérer l\'ID du profil vendeur créé');
        }

        return $profileId;
    }

    /**
     * Mettre à jour un profil vendeur
     */
    public function updateSellerProfile(int $profileId, array $data): bool
    {
        // Gérer l'upload de logo si présent
        if (isset($data['logo']) && $data['logo'] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $uploadPath = FCPATH . 'uploads/seller_logos/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if ($data['logo']->isValid() && !$data['logo']->hasMoved()) {
                $newName = $data['logo']->getRandomName();
                $data['logo']->move($uploadPath, $newName);
                $data['logo_url'] = 'uploads/seller_logos/' . $newName;
            }
            unset($data['logo']);
        }

        return $this->update($profileId, $data);
    }

    /**
     * Obtenir le profil vendeur par user_id
     */
    public function getSellerProfileByUserId(int $userId)
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * Vérifier un profil vendeur
     */
    public function verifySellerProfile(int $profileId): bool
    {
        return $this->update($profileId, [
            'is_verified' => true,
            'verification_status' => 'verified',
            'verified_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Rejeter un profil vendeur
     */
    public function rejectSellerProfile(int $profileId, string $reason = ''): bool
    {
        return $this->update($profileId, [
            'is_verified' => false,
            'verification_status' => 'rejected',
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Obtenir les profils vendeurs paginés
     */
    public function getSellerProfilesPaginated($perPage = 10, $page = 1, $search = null, $filters = [])
    {
        $profiles = $this->model->getSellerProfiles($perPage, $page, $search, $filters);
        $total = $this->model->countSellerProfiles($search, $filters);

        return [
            'profiles' => $profiles,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
}
