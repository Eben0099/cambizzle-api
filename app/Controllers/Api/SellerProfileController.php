<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use Config\Services;

class SellerProfileController extends BaseApiController
{
    protected $sellerService;
    protected $authService;

    public function __construct()
    {
        $this->sellerService = Services::sellerService();
        $this->authService = Services::authService();
    }

    public function index()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 10;
            $page = $this->request->getGet('page') ?? 1;
            $search = $this->request->getGet('search');
            $filters = $this->request->getGet('filters') ?? [];

            $result = $this->sellerService->getSellerProfilesPaginated($perPage, $page, $search, $filters);
            return $this->success($result, 'Profils vendeurs récupérés');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du profil vendeur requis']);
            }
            $profile = $this->sellerService->find((int)$id);
            if (!$profile) {
                return $this->notFound('Profil vendeur non trouvé');
            }
            return $this->success($profile, 'Profil vendeur récupéré');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function create()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));

            // Détection du type de contenu et parsing approprié
            $contentType = $this->request->getHeaderLine('Content-Type');
            $data = [];

            if (strpos($contentType, 'application/json') !== false) {
                $data = $this->request->getJSON(true) ?? [];
            } elseif (strpos($contentType, 'multipart/form-data') !== false || strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                $data = $this->request->getPost();
            }

            // Gérer les fichiers
            $logo = $this->request->getFile('logo');
            if ($logo && $logo->isValid()) {
                $data['logo'] = $logo;
            }

            // Traiter les champs JSON si nécessaires
            if (!empty($data)) {
                // Pour opening_hours : décoder si c'est du JSON stringifié, garder l'array si déjà décodé
                if (isset($data['opening_hours']) && is_string($data['opening_hours'])) {
                    // Essayer de décoder le JSON stringifié depuis form-data
                    $decoded = json_decode($data['opening_hours'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['opening_hours'] = $decoded; // Maintenant c'est un array pour le cast JSON
                    }
                    // Si ce n'est pas du JSON valide, le cast JSON du modèle gérera l'erreur
                }

                // Même chose pour delivery_options
                if (isset($data['delivery_options']) && is_string($data['delivery_options'])) {
                    $decoded = json_decode($data['delivery_options'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['delivery_options'] = $decoded; // Maintenant c'est un array pour le cast JSON
                    }
                    // Si ce n'est pas du JSON valide, le cast JSON du modèle gérera l'erreur
                }
            }

            $id = $this->sellerService->createSellerProfile((int)$payload->user_id, $data);

            if (!$id) {
                return $this->serverError('Échec de la création du profil vendeur');
            }

            $profile = $this->sellerService->find($id);

            if (!$profile) {
                return $this->serverError('Profil vendeur créé mais non trouvé');
            }

            return $this->created($profile, 'Profil vendeur créé');
        } catch (\InvalidArgumentException $e) {
            return $this->validationError(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du profil vendeur requis']);
            }

            // Détection du type de contenu et parsing approprié
            $contentType = $this->request->getHeaderLine('Content-Type');
            $data = [];

            if (strpos($contentType, 'application/json') !== false) {
                $data = $this->request->getJSON(true) ?? [];
            } elseif (strpos($contentType, 'multipart/form-data') !== false || strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                $data = $this->request->getPost();
            }

            // Gérer les fichiers
            $logo = $this->request->getFile('logo');
            if ($logo && $logo->isValid()) {
                $data['logo'] = $logo;
            }

            // Traiter les champs JSON si nécessaires
            if (!empty($data)) {
                // Pour opening_hours : décoder si c'est du JSON stringifié, garder l'array si déjà décodé
                if (isset($data['opening_hours']) && is_string($data['opening_hours'])) {
                    // Essayer de décoder le JSON stringifié depuis form-data
                    $decoded = json_decode($data['opening_hours'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['opening_hours'] = $decoded; // Maintenant c'est un array pour le cast JSON
                    }
                    // Si ce n'est pas du JSON valide, le cast JSON du modèle gérera l'erreur
                }

                // Même chose pour delivery_options
                if (isset($data['delivery_options']) && is_string($data['delivery_options'])) {
                    $decoded = json_decode($data['delivery_options'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['delivery_options'] = $decoded; // Maintenant c'est un array pour le cast JSON
                    }
                    // Si ce n'est pas du JSON valide, le cast JSON du modèle gérera l'erreur
                }
            }

            $success = $this->sellerService->updateSellerProfile((int)$id, $data);
            if (!$success) {
                return $this->validationError($this->sellerService->getErrors());
            }
            $profile = $this->sellerService->find($id);
            return $this->success($profile, 'Profil vendeur mis à jour');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du profil vendeur requis']);
            }
            $success = $this->sellerService->delete((int)$id);
            if (!$success) {
                return $this->serverError('Échec de la suppression');
            }
            return $this->success(null, 'Profil vendeur supprimé');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller-profiles/me - Récupérer le profil vendeur de l'utilisateur connecté
     */
    public function me()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$payload) {
                return $this->unauthorized('Token invalide');
            }

            $userId = $payload->user_id;
            $profile = $this->sellerService->getSellerProfileByUserId((int)$userId);
            
            if (!$profile) {
                return $this->notFound('Aucun profil vendeur trouvé pour cet utilisateur');
            }

            return $this->success($profile, 'Profil vendeur récupéré');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/seller-profiles/me - Mettre à jour le profil vendeur de l'utilisateur connecté
     */
    public function updateMe()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            if (!$payload) {
                return $this->unauthorized('Token invalide');
            }

            $userId = $payload->user_id;
            
            // Vérifier que l'utilisateur a bien un profil vendeur
            $existingProfile = $this->sellerService->getSellerProfileByUserId((int)$userId);
            if (!$existingProfile) {
                return $this->notFound('Aucun profil vendeur trouvé pour cet utilisateur');
            }

            // Vérifier que l'ID du profil est valide
            $profileId = null;
            if (is_object($existingProfile)) {
                $profileId = $existingProfile->id ?? null;
            } elseif (is_array($existingProfile)) {
                $profileId = $existingProfile['id'] ?? null;
            }

            if (!$profileId) {
                return $this->serverError('ID du profil vendeur invalide');
            }

            // Détection du type de contenu et parsing approprié
            $contentType = $this->request->getHeaderLine('Content-Type');
            $data = [];

            if (strpos($contentType, 'application/json') !== false) {
                $data = $this->request->getJSON(true) ?? [];
            } elseif (strpos($contentType, 'multipart/form-data') !== false || strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                $data = $this->request->getPost();
            }

            // Gérer les fichiers
            $logo = $this->request->getFile('logo');
            if ($logo && $logo->isValid()) {
                $data['logo'] = $logo;
            }

            // Traiter les champs JSON si nécessaires
            if (!empty($data)) {
                // Pour opening_hours : décoder si c'est du JSON stringifié, garder l'array si déjà décodé
                if (isset($data['opening_hours']) && is_string($data['opening_hours'])) {
                    // Essayer de décoder le JSON stringifié depuis form-data
                    $decoded = json_decode($data['opening_hours'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['opening_hours'] = $decoded; // Maintenant c'est un array pour le cast JSON
                    }
                    // Si ce n'est pas du JSON valide, le cast JSON du modèle gérera l'erreur
                }

                // Même chose pour delivery_options
                if (isset($data['delivery_options']) && is_string($data['delivery_options'])) {
                    $decoded = json_decode($data['delivery_options'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['delivery_options'] = $decoded; // Maintenant c'est un array pour le cast JSON
                    }
                    // Si ce n'est pas du JSON valide, le cast JSON du modèle gérera l'erreur
                }
            }

            // Mettre à jour le profil en utilisant l'ID du profil existant
            $success = $this->sellerService->updateSellerProfile($profileId, $data);
            if (!$success) {
                return $this->validationError($this->sellerService->getErrors());
            }

            // Récupérer le profil mis à jour
            $updatedProfile = $this->sellerService->find($profileId);
            return $this->success($updatedProfile, 'Profil vendeur mis à jour');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/seller-profiles/{userId} - Récupérer le profil vendeur d'un utilisateur par son userId
     */
    public function getByUserId($userId = null)
    {
        try {
            if (!$userId) {
                return $this->validationError(['userId' => 'ID utilisateur requis']);
            }

            // Valider que userId est un nombre
            if (!is_numeric($userId)) {
                return $this->validationError(['userId' => 'ID utilisateur doit être un nombre']);
            }

            $profile = $this->sellerService->getSellerProfileByUserId((int)$userId);
            
            if (!$profile) {
                return $this->notFound('Aucun profil vendeur trouvé pour cet utilisateur');
            }

            return $this->success($profile, 'Profil vendeur récupéré');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}


