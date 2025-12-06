<?php

namespace App\Services;

use App\Models\UserModel;
use App\Services\UploadService;

class UserService
{
    protected $model;
    protected $uploadService;
    protected $errors = [];

    public function __construct(UserModel $model, UploadService $uploadService)
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

    public function createUser(array $data)
    {
        // Validation des données
        if (empty($data['email']) || empty($data['password_hash'])) {
            throw new \InvalidArgumentException('Email et mot de passe requis');
        }

        // Vérifier si l'utilisateur existe déjà
        if ($this->model->where('email', $data['email'])->first()) {
            throw new \RuntimeException('Un utilisateur avec cet email existe déjà');
        }

        // Hasher le mot de passe
        $data['password_hash'] = password_hash($data['password_hash'], PASSWORD_DEFAULT);

        // Définir les valeurs par défaut
        $data['is_verified'] = false;
        $data['role_id'] = $data['role_id'] ?? 2; // Rôle par défaut: vendeur

        return $this->create($data);
    }

    public function updateUser($id, array $data)
    {
        // Log des données reçues pour debug
        log_message('info', 'UserService::updateUser - Received data: ' . json_encode($data));
        
        // Ne pas permettre la modification de l'email via cette méthode (pour l'instant)
        if (isset($data['email'])) {
            log_message('info', 'UserService::updateUser - Email field removed from update data');
            unset($data['email']);
        }
        
        // Ne pas permettre la modification du slug directement (il est généré automatiquement)
        if (isset($data['slug'])) {
            log_message('info', 'UserService::updateUser - Slug field removed from update data');
            unset($data['slug']);
        }

        // Gérer l'upload de photo/avatar si présent
        $photoFields = ['photo', 'avatar'];
        $photoProcessed = false;
        
        foreach ($photoFields as $field) {
            log_message('info', "UserService::updateUser - Checking field '$field' in data");
            
            if (isset($data[$field])) {
                log_message('info', "UserService::updateUser - Field '$field' exists in data");
                log_message('info', "UserService::updateUser - Field '$field' type: " . gettype($data[$field]));
                
                if ($data[$field] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                    log_message('info', "UserService::updateUser - Field '$field' is UploadedFile instance");
                    
                    try {
                        log_message('info', "UserService::updateUser - Starting upload for field '$field'");
                        $result = $this->handleImageUpload($data[$field], 'avatars');
                        
                        if ($result['success']) {
                            $data['photo_url'] = $result['url'];
                            log_message('info', "UserService::updateUser - ✅ {$field} uploaded successfully: " . $result['url']);
                            $photoProcessed = true;
                        } else {
                            $this->errors[$field] = $result['error'];
                            log_message('error', "UserService::updateUser - ❌ {$field} upload failed: " . $result['error']);
                            // Ne pas arrêter le processus, continuer sans le fichier
                        }
                    } catch (\Exception $e) {
                        $this->errors[$field] = 'Erreur lors de l\'upload: ' . $e->getMessage();
                        log_message('error', "UserService::updateUser - ❌ {$field} upload exception: " . $e->getMessage());
                    }
                    
                    unset($data[$field]);
                    if ($photoProcessed) {
                        break; // Traiter seulement le premier fichier trouvé avec succès
                    }
                } else {
                    log_message('warning', "UserService::updateUser - Field '$field' is not an UploadedFile instance: " . gettype($data[$field]));
                }
            } else {
                log_message('info', "UserService::updateUser - Field '$field' not found in data");
            }
        }
        
        if (!$photoProcessed) {
            log_message('info', 'UserService::updateUser - No photo/avatar file was processed');
        }

        // Gérer l'upload de document d'identité si présent
        if (isset($data['identity_document']) && $data['identity_document'] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            try {
                $result = $this->handleDocumentUpload($data['identity_document'], 'identity');
                if ($result['success']) {
                    $data['identity_document_url'] = $result['url'];
                    log_message('info', 'UserService::updateUser - Identity document uploaded successfully: ' . $result['url']);
                } else {
                    $this->errors['identity_document'] = $result['error'];
                    log_message('error', 'UserService::updateUser - Identity document upload failed: ' . $result['error']);
                    // Ne pas arrêter le processus, continuer sans le fichier
                }
            } catch (\Exception $e) {
                $this->errors['identity_document'] = 'Erreur lors de l\'upload: ' . $e->getMessage();
                log_message('error', 'UserService::updateUser - Identity document upload exception: ' . $e->getMessage());
            }
            unset($data['identity_document']);
        }

        // Si aucune donnée exploitable après traitements
        if (empty($data)) {
            $this->errors = ['data' => 'Aucune donnée valide à mettre à jour'];
            log_message('error', 'UserService::updateUser - No data after processing');
            return false;
        }

        // Log des données avant mise à jour pour debug
        log_message('info', 'UserService::updateUser - Data to update: ' . json_encode($data));

        try {
            $result = $this->update($id, $data);
            if (!$result) {
                $this->errors = $this->model->errors();
                log_message('error', 'UserService::updateUser - Model errors: ' . json_encode($this->errors));
            }
            return $result;
        } catch (\Exception $e) {
            $this->errors = ['exception' => $e->getMessage()];
            log_message('error', 'UserService::updateUser - Exception: ' . $e->getMessage());
            return false;
        }
    }

    public function getUsersPaginated($perPage = 10, $page = 1, $search = null, $filters = [])
    {
        $users = $this->model->getUsers($perPage, $page, $search, $filters);
        $total = $this->model->countUsers($search, $filters);

        return [
            'users' => $users,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    public function verifyIdentity($userId, $documentType, $documentNumber, $documentFile)
    {
        $user = $this->model->find($userId);
        if (!$user) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        // Upload du document
        $uploadPath = FCPATH . 'uploads/identity/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if ($documentFile->isValid() && !$documentFile->hasMoved()) {
            $newName = $documentFile->getRandomName();
            $documentFile->move($uploadPath, $newName);

            $data = [
                'identity_document_type' => $documentType,
                'identity_document_number' => $documentNumber,
                'identity_document_url' => 'uploads/identity/' . $newName,
                'is_identity_verified' => false, // En attente de vérification admin
            ];

            return $this->update($userId, $data);
        }

        throw new \RuntimeException('Erreur lors de l\'upload du document');
    }

    public function findByEmail(string $email): ?array
    {
        return $this->model->where('email', $email)->first();
    }

    public function changePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->model->find($userId);
        if (!$user) {
            throw new \RuntimeException('Utilisateur non trouvé');
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            throw new \RuntimeException('Mot de passe actuel incorrect');
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Utiliser un update direct pour éviter l'erreur "There is no data to update."
        return $this->model
            ->where('id_user', $userId)
            ->set('password_hash', $newHash)
            ->update();
    }

    /**
     * Gérer l'upload d'images (avatars, photos de profil)
     */
    public function handleImageUpload(\CodeIgniter\HTTP\Files\UploadedFile $file, string $folder): array
    {
        try {
            log_message('info', "UserService::handleImageUpload - Processing file: " . $file->getName() . " (" . $file->getSize() . " bytes)");
            
            // Validation du fichier
            if (!$file->isValid()) {
                $error = 'Fichier invalide: ' . $file->getErrorString();
                log_message('error', "UserService::handleImageUpload - $error");
                return ['success' => false, 'error' => $error];
            }

            // Vérifier le type de fichier
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $file->getMimeType();
            log_message('info', "UserService::handleImageUpload - File type: $fileType");
            
            if (!in_array($fileType, $allowedTypes)) {
                $error = 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, GIF, WebP. Reçu: ' . $fileType;
                log_message('error', "UserService::handleImageUpload - $error");
                return ['success' => false, 'error' => $error];
            }

            // Vérifier la taille (5MB max)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file->getSize() > $maxSize) {
                $error = 'Fichier trop volumineux. Taille maximum: 5MB, reçu: ' . round($file->getSize() / 1024 / 1024, 2) . 'MB';
                log_message('error', "UserService::handleImageUpload - $error");
                return ['success' => false, 'error' => $error];
            }

            // Créer le chemin d'upload
            $uploadPath = FCPATH . 'uploads/' . $folder . '/';
            log_message('info', "UserService::handleImageUpload - Upload path: $uploadPath");
            
            if (!is_dir($uploadPath)) {
                if (!mkdir($uploadPath, 0755, true)) {
                    $error = 'Impossible de créer le dossier d\'upload: ' . $uploadPath;
                    log_message('error', "UserService::handleImageUpload - $error");
                    return ['success' => false, 'error' => $error];
                }
                log_message('info', "UserService::handleImageUpload - Created upload directory: $uploadPath");
            }

            // Vérifier les permissions du dossier
            if (!is_writable($uploadPath)) {
                $error = 'Dossier d\'upload non accessible en écriture: ' . $uploadPath;
                log_message('error', "UserService::handleImageUpload - $error");
                return ['success' => false, 'error' => $error];
            }

            // Générer un nom unique
            $newName = $file->getRandomName();
            $fullPath = $uploadPath . $newName;
            log_message('info', "UserService::handleImageUpload - New filename: $newName");
            
            // Déplacer le fichier
            if ($file->move($uploadPath, $newName)) {
                $url = 'uploads/' . $folder . '/' . $newName;
                log_message('info', "UserService::handleImageUpload - File moved successfully to: $fullPath");
                
                // Vérifier que le fichier existe bien
                if (file_exists($fullPath)) {
                    log_message('info', "UserService::handleImageUpload - File confirmed at: $fullPath");
                    return ['success' => true, 'url' => $url, 'filename' => $newName];
                } else {
                    $error = 'Fichier déplacé mais non trouvé: ' . $fullPath;
                    log_message('error', "UserService::handleImageUpload - $error");
                    return ['success' => false, 'error' => $error];
                }
            } else {
                $error = 'Erreur lors du déplacement du fichier vers: ' . $fullPath;
                log_message('error', "UserService::handleImageUpload - $error");
                return ['success' => false, 'error' => $error];
            }
        } catch (\Exception $e) {
            $error = 'Exception lors de l\'upload: ' . $e->getMessage();
            log_message('error', "UserService::handleImageUpload - $error");
            return ['success' => false, 'error' => $error];
        }
    }

    /**
     * Gérer l'upload de documents (PDF, images de documents d'identité)
     */
    private function handleDocumentUpload(\CodeIgniter\HTTP\Files\UploadedFile $file, string $folder): array
    {
        try {
            // Validation du fichier
            if (!$file->isValid()) {
                return ['success' => false, 'error' => 'Fichier invalide: ' . $file->getErrorString()];
            }

            // Vérifier le type de fichier
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            $fileType = $file->getMimeType();
            
            if (!in_array($fileType, $allowedTypes)) {
                return ['success' => false, 'error' => 'Type de fichier non autorisé. Formats acceptés: JPG, PNG, PDF'];
            }

            // Vérifier la taille (5MB max)
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file->getSize() > $maxSize) {
                return ['success' => false, 'error' => 'Fichier trop volumineux. Taille maximum: 5MB'];
            }

            // Créer le chemin d'upload
            $uploadPath = FCPATH . 'uploads/' . $folder . '/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Générer un nom unique
            $newName = $file->getRandomName();
            
            // Déplacer le fichier
            if ($file->move($uploadPath, $newName)) {
                $url = 'uploads/' . $folder . '/' . $newName;
                return ['success' => true, 'url' => $url, 'filename' => $newName];
            } else {
                return ['success' => false, 'error' => 'Erreur lors du déplacement du fichier'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors de l\'upload: ' . $e->getMessage()];
        }
    }
}