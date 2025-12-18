<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Services\UserService;

class UserController extends BaseApiController
{
    protected $userService;

    public function __construct()
    {
        $this->userService = service('userService');
    }

    /**
     * GET /api/v1/users - Liste des utilisateurs (paginated)
     */
    public function index()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 10;
            $page = $this->request->getGet('page') ?? 1;
            $search = $this->request->getGet('search');
            $filters = $this->request->getGet('filters') ?? [];

            $result = $this->userService->getUsersPaginated($perPage, $page, $search, $filters);

            return $this->success([
                'users' => $result['users'],
                'pagination' => $result['pagination']
            ], 'Liste des utilisateurs récupérée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/users/{id} - Détails d'un utilisateur
     */
    public function show($id = null)
    {
        try {
            $user = $this->userService->find($id);

            if (!$user) {
                return $this->notFound('Utilisateur non trouvé');
            }

            // Ne pas retourner le mot de passe
            unset($user['password_hash']);

            return $this->success($user, 'Utilisateur récupéré avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/users - Créer un utilisateur
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validation basique
            if (empty($data['email']) || empty($data['password'])) {
                return $this->validationError([
                    'email' => 'Email requis',
                    'password' => 'Mot de passe requis'
                ]);
            }

            $userId = $this->userService->createUser($data);

            $user = $this->userService->find($userId);
            unset($user['password_hash']);

            return $this->created($user, 'Utilisateur créé avec succès');

        } catch (\InvalidArgumentException $e) {
            return $this->validationError(['error' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }

    /**
     * PUT /api/v1/users/{id} - Mettre à jour un utilisateur
     */
    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON(true);

            // Récupérer les fichiers uploadés
            $photo = $this->request->getFile('photo');
            if ($photo && $photo->isValid()) {
                $data['photo'] = $photo;
            }

            $identityDocument = $this->request->getFile('identity_document');
            if ($identityDocument && $identityDocument->isValid()) {
                $data['identity_document'] = $identityDocument;
            }

            $success = $this->userService->updateUser($id, $data);

            if (!$success) {
                $errors = $this->userService->getErrors();
                return $this->validationError($errors ?: ['unknown' => 'Mise à jour impossible']);
            }

            $user = $this->userService->find($id);
            unset($user['password_hash']);

            return $this->success($user, 'Utilisateur mis à jour avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/users/{id} - Supprimer un utilisateur (soft delete)
     */
    public function delete($id = null)
    {
        try {
            $success = $this->userService->delete($id);

            if (!$success) {
                return $this->serverError('Échec de la suppression');
            }

            return $this->success(null, 'Utilisateur supprimé avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/v1/users/{id}/verify-identity - Vérification d'identité
     */
    public function verifyIdentity($id = null)
    {
        try {
            // Accepte form-data, x-www-form-urlencoded et JSON
            $contentType = $this->request->getHeaderLine('Content-Type');
            $data = [];

            if ($contentType && stripos($contentType, 'application/json') !== false) {
                $data = $this->request->getJSON(true) ?? [];
            } else {
                $data = $this->request->getPost();
                if (empty($data)) {
                    $data = $this->request->getRawInput();
                }
            }

            $document = $this->request->getFile('document');

            if (!$document || !$document->isValid()) {
                return $this->validationError(['document' => 'Document requis']);
            }

            $documentType = $data['document_type'] ?? null;
            $documentNumber = $data['document_number'] ?? null;

            if (empty($documentType) || empty($documentNumber)) {
                return $this->validationError([
                    'document_type' => 'Type de document requis',
                    'document_number' => 'Numéro de document requis'
                ]);
            }

            $success = $this->userService->verifyIdentity(
                (int)$id,
                (string)$documentType,
                (string)$documentNumber,
                $document
            );

            if (!$success) {
                $errors = $this->userService->getErrors();
                return $this->validationError($errors);
            }

            return $this->success(null, 'Document d\'identité uploadé avec succès. En attente de vérification.');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/v1/users/{id}/change-password - Changer le mot de passe
     */
    public function changePassword($id = null)
    {
        try {
            // Supporte JSON, x-www-form-urlencoded et form-data
            $data = $this->request->getJSON(true);
            if (empty($data)) {
                $data = $this->request->getRawInput();
            }
            if (empty($data)) {
                $data = $this->request->getPost();
            }

            if (empty($data['current_password']) || empty($data['new_password'])) {
                return $this->validationError([
                    'current_password' => 'Mot de passe actuel requis',
                    'new_password' => 'Nouveau mot de passe requis'
                ]);
            }

            $success = $this->userService->changePassword(
                $id,
                $data['current_password'],
                $data['new_password']
            );

            if (!$success) {
                $errors = $this->userService->getErrors();
                return $this->validationError($errors);
            }

            return $this->success(null, 'Mot de passe changé avec succès');

        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }

    /**
     * GET /api/users/me - Récupérer le profil de l'utilisateur connecté
     */
    public function me()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            // Utiliser AuthService pour valider le token
            $authService = service('authService');
            $payload = $authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$payload) {
                return $this->unauthorized('Token invalide');
            }

            $userId = $payload->user_id;
            $user = $this->userService->find($userId);

            if (!$user) {
                return $this->notFound('Utilisateur non trouvé');
            }

            // Récupérer le code de parrainage
            $referralCodeModel = new \App\Models\ReferralCodeModel();
            $referralCode = $referralCodeModel->where('user_id', $userId)->where('is_active', 1)->orderBy('created_at', 'DESC')->first();
            $user['referral_code'] = $referralCode ? $referralCode['code'] : null;

            // Ne pas retourner le mot de passe
            unset($user['password_hash']);

            $iv = (int)($user['is_verified'] ?? (($user['is_identity_verified'] ?? 0) ? 1 : 0));
            $identity = [
                'status_code' => $iv,
                'status_label' => match ($iv) {
                    1 => 'verified',
                    2 => 'submitted_pending',
                    3 => 'changes_requested',
                    4 => 'rejected',
                    default => 'not_submitted'
                },
                'can_submit' => in_array($iv, [0,4], true),
                'can_edit' => in_array($iv, [0,3,4], true),
                'can_resubmit' => in_array($iv, [0,3,4], true),
            ];
            $user['identity'] = $identity;

            return $this->success(['user' => $user], 'Profil récupéré avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/users/me - Mettre à jour le profil de l'utilisateur connecté
     */
    public function updateMe()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            // Utiliser AuthService pour valider le token
            $authService = service('authService');
            $payload = $authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$payload) {
                return $this->unauthorized('Token invalide');
            }

            $userId = $payload->user_id;

            // Vérifier que l'utilisateur existe
            $existingUser = $this->userService->find($userId);
            if (!$existingUser) {
                return $this->notFound('Utilisateur non trouvé');
            }

            // Gestion spéciale pour PUT avec FormData (problème connu de CodeIgniter)
            $data = [];
            $contentType = $this->request->getHeaderLine('Content-Type');
            $method = $this->request->getMethod();

            log_message('info', "UserController::updateMe - Method: $method, Content-Type: $contentType");

            // Gestion universelle des fichiers pour toutes les méthodes
            log_message('info', 'UserController::updateMe - Checking for files...');

            // Essayer de récupérer les fichiers quelque soit la méthode
            $fileFields = ['avatar', 'photo', 'identity_document'];
            foreach ($fileFields as $fieldName) {
                $file = $this->request->getFile($fieldName);
                log_message('info', "UserController::updateMe - Checking field '$fieldName'");

                if ($file) {
                    log_message('info', "UserController::updateMe - File object found for '$fieldName': " . get_class($file));
                    log_message('info', "UserController::updateMe - File name: " . $file->getName());
                    log_message('info', "UserController::updateMe - File size: " . $file->getSize());
                    log_message('info', "UserController::updateMe - File valid: " . ($file->isValid() ? 'YES' : 'NO'));
                    log_message('info', "UserController::updateMe - File moved: " . ($file->hasMoved() ? 'YES' : 'NO'));

                    if ($file->isValid() && !$file->hasMoved()) {
                        $data[$fieldName] = $file;
                        log_message('info', "UserController::updateMe - ✅ $fieldName file added to data: " . $file->getName() . ' (' . $file->getSize() . ' bytes)');
                    } else {
                        if (!$file->isValid()) {
                            log_message('error', "UserController::updateMe - File '$fieldName' is not valid: " . $file->getErrorString());
                        }
                        if ($file->hasMoved()) {
                            log_message('error', "UserController::updateMe - File '$fieldName' has already been moved");
                        }
                    }
                } else {
                    log_message('info', "UserController::updateMe - No file object for '$fieldName'");
                }
            }

            // Pour PUT avec multipart/form-data, CodeIgniter ne parse pas automatiquement les données
            if ($method === 'PUT' && strpos($contentType, 'multipart/form-data') !== false) {
                // Parsing manuel des données multipart pour PUT
                $rawInput = file_get_contents('php://input');
                log_message('info', 'UserController::updateMe - Raw input length: ' . strlen($rawInput));

                if (!empty($rawInput)) {
                    $parsedData = $this->parseMultipartData($rawInput, $contentType);
                    $data = array_merge($data, $parsedData);
                }

                // Essayer aussi les méthodes traditionnelles au cas où
                $postData = $this->request->getPost();
                if ($postData) {
                    $data = array_merge($data, $postData);
                }
            } elseif (strpos($contentType, 'application/json') !== false) {
                try {
                    $jsonData = $this->request->getJSON(true);
                    if ($jsonData) {
                        $data = array_merge($data, $jsonData);
                    }
                } catch (\Exception $e) {
                    log_message('error', 'UserController::updateMe - JSON parsing error: ' . $e->getMessage());
                    // Essayer de parser manuellement
                    $rawInput = file_get_contents('php://input');
                    if (!empty($rawInput)) {
                        $jsonData = json_decode($rawInput, true);
                        if (json_last_error() === JSON_ERROR_NONE && $jsonData) {
                            $data = array_merge($data, $jsonData);
                        }
                    }
                }
            } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                $postData = $this->request->getPost();
                if ($postData) {
                    $data = array_merge($data, $postData);
                } else {
                    // Pour PUT, essayer le raw input
                    $rawInput = file_get_contents('php://input');
                    if (!empty($rawInput)) {
                        parse_str($rawInput, $parsedData);
                        if ($parsedData) {
                            $data = array_merge($data, $parsedData);
                        }
                    }
                }
            } else {
                // Fallback: essayer toutes les méthodes
                $postData = $this->request->getPost();
                if ($postData) {
                    $data = array_merge($data, $postData);
                }

                try {
                    $jsonData = $this->request->getJSON(true);
                    if ($jsonData) {
                        $data = array_merge($data, $jsonData);
                    }
                } catch (\Exception $e) {
                    log_message('warning', 'UserController::updateMe - JSON fallback failed: ' . $e->getMessage());
                }

                // Essayer de récupérer les fichiers même dans le fallback
                $avatar = $this->request->getFile('avatar');
                if ($avatar && $avatar->isValid() && !$avatar->hasMoved()) {
                    $data['avatar'] = $avatar;
                    log_message('info', 'UserController::updateMe - Fallback avatar detected: ' . $avatar->getName());
                }

                $photo = $this->request->getFile('photo');
                if ($photo && $photo->isValid() && !$photo->hasMoved()) {
                    $data['photo'] = $photo;
                    log_message('info', 'UserController::updateMe - Fallback photo detected: ' . $photo->getName());
                }
            }

            // Filtrer et nettoyer les données avec une approche plus permissive
            $filteredData = [];
            // Champs qui existent réellement dans la table users
            $allowedFields = [
                'first_name', 'last_name', 'phone', 'email', 'photo_url',
                'password', 'new_password', 'current_password',
                'identity_document_type', 'identity_document_number',
                'photo', 'avatar', 'identity_document'
            ];
            
            foreach ($data as $key => $value) {
                // Ignorer les champs non autorisés
                if (!in_array($key, $allowedFields)) {
                    log_message('info', "UserController::updateMe - Ignoring field '$key' (not in users table)");
                    continue;
                }
                
                // Garder les fichiers uploadés
                if (in_array($key, ['photo', 'avatar', 'identity_document']) &&
                    $value instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                    $filteredData[$key] = $value;
                    continue;
                }

                // Approche plus permissive pour les valeurs - accepter même les chaînes vides
                if ($value !== null && $value !== 'null' && $value !== 'undefined') {
                    // Convertir les chaînes booléennes
                    if ($value === 'true') {
                        $filteredData[$key] = true;
                    } elseif ($value === 'false') {
                        $filteredData[$key] = false;
                    } else {
                        $filteredData[$key] = $value; // Accepter même les chaînes vides
                    }
                } elseif ($value === false || $value === 0 || $value === '0') {
                    // Garder les valeurs falsy mais valides
                    $filteredData[$key] = $value;
                }
            }

            // Si toujours pas de données, essayer une approche encore plus permissive
            if (empty($filteredData)) {
                log_message('warning', 'UserController::updateMe - No data after filtering, trying permissive approach');
                $allowedFields = [
                    'first_name', 'last_name', 'phone', 'email', 'photo_url',
                    'password', 'new_password', 'current_password',
                    'identity_document_type', 'identity_document_number'
                ];
                foreach ($data as $key => $value) {
                    // Garder tout sauf null, même les chaînes vides si c'est un champ autorisé
                    if (in_array($key, $allowedFields) && $value !== null && $value !== 'null' && $value !== 'undefined') {
                        $filteredData[$key] = $value;
                    }
                }
            }

            // Log pour debug détaillé
            log_message('info', 'UserController::updateMe - Content-Type: ' . $contentType);
            log_message('info', 'UserController::updateMe - Raw data count: ' . count($data));
            log_message('info', 'UserController::updateMe - Raw data: ' . json_encode($data));
            log_message('info', 'UserController::updateMe - Filtered data count: ' . count($filteredData));
            log_message('info', 'UserController::updateMe - Filtered data: ' . json_encode($filteredData));

            // Vérifier qu'on a des données à mettre à jour
            if (empty($filteredData)) {
                log_message('error', 'UserController::updateMe - No data after all filtering attempts');
                log_message('error', 'UserController::updateMe - Original POST data: ' . json_encode($this->request->getPost()));
                log_message('error', 'UserController::updateMe - Original JSON data: ' . json_encode($this->request->getJSON(true)));

                // Dernier recours : utiliser les données brutes si disponibles
                $rawPost = $this->request->getPost();
                $rawJson = $this->request->getJSON(true);

                if (!empty($rawPost) || !empty($rawJson)) {
                    $filteredData = array_merge($rawPost ?: [], $rawJson ?: []);
                    log_message('info', 'UserController::updateMe - Using raw data as fallback: ' . json_encode($filteredData));
                } else {
                    return $this->validationError(['data' => 'Aucune donnée valide fournie pour la mise à jour']);
                }
            }

            // Mettre à jour l'utilisateur
            try {
                $success = $this->userService->updateUser($userId, $filteredData);
                if (!$success) {
                    $errors = $this->userService->getErrors();
                    log_message('error', 'User update failed: ' . json_encode($errors));

                    // Si c'est juste un problème d'upload de fichier, continuer quand même
                    $uploadErrors = array_intersect_key($errors, array_flip(['photo', 'avatar', 'identity_document']));
                    $otherErrors = array_diff_key($errors, array_flip(['photo', 'avatar', 'identity_document']));

                    if (!empty($uploadErrors) && empty($otherErrors)) {
                        // Seulement des erreurs d'upload, continuer avec un avertissement
                        log_message('warning', 'User update completed with upload errors: ' . json_encode($uploadErrors));
                        $updatedUser = $this->userService->find($userId);
                        unset($updatedUser['password_hash']);
                        return $this->success([
                            'user' => $updatedUser,
                            'upload_warnings' => $uploadErrors
                        ], 'Profil utilisateur mis à jour avec des avertissements sur les fichiers');
                    } else {
                        return $this->validationError($errors ?: ['unknown' => 'Mise à jour impossible']);
                    }
                }
            } catch (\Exception $e) {
                log_message('error', 'UserService::updateUser threw exception: ' . $e->getMessage());
                return $this->serverError('Erreur lors de la mise à jour: ' . $e->getMessage());
            }

            // Récupérer l'utilisateur mis à jour
            $updatedUser = $this->userService->find($userId);
            unset($updatedUser['password_hash']);

            return $this->success($updatedUser, 'Profil utilisateur mis à jour avec succès');
        } catch (\Exception $e) {
            log_message('error', 'UserController::updateMe - Exception: ' . $e->getMessage());
            return $this->serverError('Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * DEBUG - Endpoint temporaire pour diagnostiquer les données reçues
     */
    public function debugMe()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            // Utiliser AuthService pour valider le token
            $authService = service('authService');
            $payload = $authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$payload) {
                return $this->unauthorized('Token invalide');
            }

            $contentType = $this->request->getHeaderLine('Content-Type');
            $postData = $this->request->getPost();
            $jsonData = $this->request->getJSON(true);
            $rawInput = $this->request->getRawInput();

            $debugInfo = [
                'content_type' => $contentType,
                'post_data' => $postData,
                'json_data' => $jsonData,
                'raw_input' => $rawInput,
                'files' => [
                    'avatar' => $this->request->getFile('avatar') ? 'present' : 'absent',
                    'photo' => $this->request->getFile('photo') ? 'present' : 'absent'
                ],
                'headers' => $this->request->headers(),
                'method' => $this->request->getMethod()
            ];

            return $this->success($debugInfo, 'Debug info');
        } catch (\Exception $e) {
            return $this->serverError('Debug error: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/users/test-upload - Test spécifique pour l'upload de fichiers
     */
    public function testUpload()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $authService = service('authService');
            $payload = $authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$payload) {
                return $this->unauthorized('Token invalide');
            }

            $userId = $payload->user_id;
            $contentType = $this->request->getHeaderLine('Content-Type');
            $method = $this->request->getMethod();

            log_message('info', "UserController::testUpload - Method: $method, Content-Type: $contentType");

            // Test de détection des fichiers
            $fileInfo = [];
            $fileFields = ['avatar', 'photo', 'identity_document'];
            
            foreach ($fileFields as $fieldName) {
                $file = $this->request->getFile($fieldName);
                
                if ($file) {
                    $fileInfo[$fieldName] = [
                        'detected' => true,
                        'name' => $file->getName(),
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                        'valid' => $file->isValid(),
                        'moved' => $file->hasMoved(),
                        'error' => $file->getErrorString()
                    ];
                    
                    // Test d'upload direct si le fichier est valide
                    if ($file->isValid() && !$file->hasMoved()) {
                        try {
                            $result = $this->userService->handleImageUpload($file, 'avatars');
                            $fileInfo[$fieldName]['upload_test'] = $result;
                        } catch (\Exception $e) {
                            $fileInfo[$fieldName]['upload_test'] = [
                                'success' => false,
                                'error' => 'Exception: ' . $e->getMessage()
                            ];
                        }
                    }
                } else {
                    $fileInfo[$fieldName] = [
                        'detected' => false
                    ];
                }
            }

            // Informations sur les dossiers
            $uploadDirs = [
                'avatars' => FCPATH . 'uploads/avatars/',
                'profiles' => FCPATH . 'uploads/profiles/',
                'identity' => FCPATH . 'uploads/identity/'
            ];

            $dirInfo = [];
            foreach ($uploadDirs as $name => $path) {
                $dirInfo[$name] = [
                    'path' => $path,
                    'exists' => is_dir($path),
                    'writable' => is_writable($path),
                    'permissions' => is_dir($path) ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A'
                ];
            }

            return $this->success([
                'user_id' => $userId,
                'method' => $method,
                'content_type' => $contentType,
                'files' => $fileInfo,
                'directories' => $dirInfo,
                'php_settings' => [
                    'file_uploads' => ini_get('file_uploads'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'max_file_uploads' => ini_get('max_file_uploads')
                ]
            ], 'Test d\'upload terminé');

        } catch (\Exception $e) {
            log_message('error', 'UserController::testUpload - Exception: ' . $e->getMessage());
            return $this->serverError('Erreur test upload: ' . $e->getMessage());
        }
    }

    /**
     * Parser manuel pour les données multipart/form-data avec PUT
     * CodeIgniter ne parse pas automatiquement les données multipart pour PUT
     */
    private function parseMultipartData(string $rawInput, string $contentType): array
    {
        $data = [];
        
        try {
            // Extraire la boundary du Content-Type
            if (!preg_match('/boundary=(.+)$/', $contentType, $matches)) {
                log_message('error', 'UserController::parseMultipartData - No boundary found in Content-Type');
                return $data;
            }
            
            $boundary = '--' . trim($matches[1]);
            log_message('info', "UserController::parseMultipartData - Boundary: $boundary");
            
            // Diviser les données par boundary
            $parts = explode($boundary, $rawInput);
            
            foreach ($parts as $part) {
                $part = trim($part);
                if (empty($part) || $part === '--') {
                    continue;
                }
                
                // Séparer les headers du contenu
                $headerEndPos = strpos($part, "\r\n\r\n");
                if ($headerEndPos === false) {
                    continue;
                }
                
                $headers = substr($part, 0, $headerEndPos);
                $content = substr($part, $headerEndPos + 4);
                
                // Extraire le nom du champ
                if (preg_match('/name="([^"]+)"/', $headers, $nameMatches)) {
                    $fieldName = $nameMatches[1];
                    
                    // Vérifier si c'est un fichier
                    if (strpos($headers, 'filename=') !== false) {
                        // C'est un fichier - on ne peut pas le traiter ici facilement
                        // Les fichiers seront gérés par $this->request->getFile()
                        log_message('info', "UserController::parseMultipartData - File field detected: $fieldName");
                        continue;
                    } else {
                        // C'est un champ texte
                        $data[$fieldName] = trim($content);
                        log_message('info', "UserController::parseMultipartData - Field: $fieldName = " . trim($content));
                    }
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'UserController::parseMultipartData - Exception: ' . $e->getMessage());
        }
        
        return $data;
    }

    /**
     * OPTIONS handler for CORS preflight
     */
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setStatusCode(204);
    }
}
