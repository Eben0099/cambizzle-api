<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Services\AuthService;
use App\Services\UserService;
use App\Traits\CaseConverterTrait;
use Config\Services;

class AuthController extends BaseApiController
{
    use CaseConverterTrait;

    protected $authService;
    protected $validation;
    protected $userService;

    public function __construct()
    {
        // Initialiser seulement si les services existent
        try {
            $this->authService = Services::authService();
            $this->validation = Services::validation();
            $this->userService = Services::userService();
        } catch (\Exception $e) {
            // Log l'erreur mais continuer
            log_message('error', 'AuthController init error: ' . $e->getMessage());
        }
    }


    /**
     * POST /api/auth/register - Inscription standard
     */
    public function register()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validation basique des données requises avec messages explicites
            $errors = [];
            
            // Prénom obligatoire
            if (empty($data['firstName']) && empty($data['first_name'])) {
                $errors['firstName'] = 'Le prénom est obligatoire pour créer votre compte';
            }
            
            // Nom obligatoire  
            if (empty($data['lastName']) && empty($data['last_name'])) {
                $errors['lastName'] = 'Le nom de famille est obligatoire pour créer votre compte';
            }
            
            // Téléphone obligatoire (principal moyen de connexion)
            if (empty($data['phone'])) {
                $errors['phone'] = 'Le numéro de téléphone est obligatoire (il servira pour vous connecter)';
            } else {
                // Validation format téléphone plus explicite
                $phone = preg_replace('/[^0-9+]/', '', $data['phone']);
                if (strlen($phone) < 8) {
                    $errors['phone'] = 'Le numéro de téléphone semble incorrect (trop court)';
                }
            }
            
            // Mot de passe obligatoire avec critères
            if (empty($data['password'])) {
                $errors['password'] = 'Un mot de passe est obligatoire pour sécuriser votre compte';
            } else if (strlen($data['password']) < 6) {
                $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères';
            }
            
            // Email facultatif mais si fourni, doit être valide
            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'adresse email semble incorrecte';
            }

            if (!empty($errors)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Veuillez corriger les erreurs ci-dessous',
                    'errors' => $errors,
                    'code' => 'VALIDATION_ERROR'
                ]);
            }

            // Préparer les données pour le service (en snake_case)
            // Priorité aux valeurs camelCase si elles existent
            $serviceData = [
                'first_name' => $data['firstName'] ?? $data['first_name'] ?? '',
                'last_name' => $data['lastName'] ?? $data['last_name'] ?? '',
                'email' => (!empty($data['email']) && trim($data['email']) !== '') ? trim($data['email']) : null, // Email facultatif, nettoyer les chaînes vides
                'password' => $data['password'],
                'phone' => $data['phone'],
                'referral_code' => $data['referralCode'] ?? $data['referral_code'] ?? null,
            ];

            // Copier les autres champs si présents
            $otherFields = ['photo_url', 'accept_terms', 'wants_to_be_seller'];
            foreach ($otherFields as $field) {
                $camelField = $this->snakeToCamel($field);
                if (isset($data[$field]) || isset($data[$camelField])) {
                    $serviceData[$field] = $data[$field] ?? $data[$camelField] ?? null;
                }
            }

            // Utiliser le service d'authentification si disponible
            if ($this->authService) {
                $result = $this->authService->register($serviceData);
                unset($result['user']['password_hash']);
                // Enrichir avec bloc identity dérivé de is_verified
                $iv = (int)($result['user']['is_verified'] ?? (($result['user']['is_identity_verified'] ?? 0) ? 1 : 0));
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
                $result['user']['identity'] = $identity;
                $camelCaseResult = $this->snakeToCamel($result);
                
                return $this->response->setStatusCode(201)->setJSON([
                    'success' => true,
                    'message' => 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.',
                    'data' => $camelCaseResult,
                    'code' => 'REGISTRATION_SUCCESS'
                ]);
            } else {
                // Fallback si le service n'est pas disponible
                return $this->response->setStatusCode(503)->setJSON([
                    'success' => false,
                    'message' => 'Service d\'inscription temporairement indisponible. Veuillez réessayer plus tard.',
                    'code' => 'SERVICE_UNAVAILABLE'
                ]);
            }

        } catch (\RuntimeException $e) {
            log_message('error', 'Register RuntimeException: ' . $e->getMessage());
            
            // Gestion des erreurs métier avec messages explicites
            $message = $e->getMessage();
            $code = 'REGISTRATION_ERROR';
            
            if (strpos($message, 'email existe déjà') !== false) {
                $code = 'EMAIL_ALREADY_EXISTS';
                $message = 'Cette adresse email est déjà utilisée. Essayez de vous connecter ou utilisez une autre adresse.';
            } elseif (strpos($message, 'numéro existe déjà') !== false) {
                $code = 'PHONE_ALREADY_EXISTS';
                $message = 'Ce numéro de téléphone est déjà utilisé. Essayez de vous connecter ou utilisez un autre numéro.';
            } elseif (strpos($message, 'Code de parrainage') !== false) {
                $code = 'INVALID_REFERRAL_CODE';
                $message = 'Le code de parrainage saisi n\'est pas valide ou a expiré.';
            }
            
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => $message,
                'code' => $code
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Register error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
                'code' => 'INTERNAL_ERROR'
            ]);
        }
    }

    /**
     * POST /api/auth/login - Connexion
     */
    public function login()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validation basique des données requises avec messages explicites
            $errors = [];
            
            // Email ou téléphone requis
            if (empty($data['phone']) && empty($data['email'])) {
                $errors['login'] = 'Veuillez saisir votre numéro de téléphone ou votre adresse email';
            }
            
            // Mot de passe obligatoire
            if (empty($data['password'])) {
                $errors['password'] = 'Veuillez saisir votre mot de passe';
            }

            if (!empty($errors)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Veuillez remplir tous les champs obligatoires',
                    'errors' => $errors,
                    'code' => 'VALIDATION_ERROR'
                ]);
            }

            // Utiliser le service d'authentification si disponible
            if ($this->authService) {
                // Authentification : priorité au téléphone
                $loginValue = null;
                $loginType = null;
                if (!empty($data['phone'])) {
                    $loginValue = $data['phone'];
                    $loginType = 'phone';
                } elseif (!empty($data['email'])) {
                    $loginValue = $data['email'];
                    $loginType = 'email';
                }
                
                $result = $this->authService->login($loginValue, $data['password'], $loginType);
                // Ajout du code de parrainage comme dans /auth/me
                $userId = $result['user']['id_user'] ?? null;
                if ($userId) {
                    $referralCodeModel = new \App\Models\ReferralCodeModel();
                    $referralCode = $referralCodeModel->where('user_id', $userId)->where('is_active', 1)->orderBy('created_at', 'DESC')->first();
                    $result['user']['referral_code'] = $referralCode ? $referralCode['code'] : null;
                } else {
                    $result['user']['referral_code'] = null;
                }
                unset($result['user']['password_hash']);
                $camelCaseResult = $this->snakeToCamel($result);
                return $this->response->setStatusCode(200)->setJSON([
                    'success' => true,
                    'message' => 'Connexion réussie ! Bienvenue.',
                    'data' => $camelCaseResult,
                    'code' => 'LOGIN_SUCCESS'
                ]);
            } else {
                // Fallback si le service n'est pas disponible
                return $this->response->setStatusCode(503)->setJSON([
                    'success' => false,
                    'message' => 'Service de connexion temporairement indisponible. Veuillez réessayer plus tard.',
                    'code' => 'SERVICE_UNAVAILABLE'
                ]);
            }

        } catch (\RuntimeException $e) {
            log_message('error', 'Login RuntimeException: ' . $e->getMessage());
            
            // Gestion des erreurs d'authentification avec messages explicites
            $message = $e->getMessage();
            $code = 'LOGIN_ERROR';
            
            if (strpos($message, 'Utilisateur non trouvé') !== false || 
                strpos($message, 'not found') !== false) {
                $code = 'USER_NOT_FOUND';
                $message = 'Aucun compte trouvé avec ces identifiants. Vérifiez votre numéro de téléphone ou email.';
            } elseif (strpos($message, 'Mot de passe incorrect') !== false || 
                      strpos($message, 'Invalid password') !== false) {
                $code = 'INVALID_PASSWORD';
                $message = 'Mot de passe incorrect. Veuillez réessayer.';
            } elseif (strpos($message, 'suspendu') !== false || 
                      strpos($message, 'suspended') !== false) {
                $code = 'ACCOUNT_SUSPENDED';
                $message = 'Votre compte a été suspendu. Contactez le support pour plus d\'informations.';
            }
            
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => $message,
                'code' => $code
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer plus tard.',
                'code' => 'INTERNAL_ERROR'
            ]);
        }
    }

    /**
     * POST /api/auth/google - Inscription/Connexion Google (simplifiée)
     */
    public function google()
    {
        return $this->success(['message' => 'Google auth endpoint available'], 'Endpoint ready');
    }

    /**
     * POST /api/auth/facebook - Inscription/Connexion Facebook (simplifiée)
     */
    public function facebook()
    {
        return $this->success(['message' => 'Facebook auth endpoint available'], 'Endpoint ready');
    }

    /**
     * POST /api/auth/verify-identity - Vérification d'identité (simplifiée)
     */
    public function verifyIdentity()
    {
        return $this->success(['message' => 'Identity verification endpoint available'], 'Endpoint ready');
    }

    /**
     * GET /api/auth/me - Profil utilisateur (simplifiée)
     */
    public function me()
    {
        try {
            // Essayer de récupérer l'utilisateur via le filtre JWT (request->user)
            $userPayload = $this->request->user ?? null;

            // Fallback: décoder le token si non présent
            if (!$userPayload) {
                $authHeader = $this->request->getHeaderLine('Authorization');
                if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                    return $this->unauthorized('Token d\'authentification manquant');
                }
                $token = $matches[1];
                $userPayload = (array) $this->authService->validateToken($token);
            }

            $userId = $userPayload['user_id'] ?? null;
            if (!$userId) {
                return $this->unauthorized('Utilisateur non authentifié');
            }

            $user = $this->userService ? $this->userService->find($userId) : null;
            if (!$user) {
                return $this->notFound('Utilisateur non trouvé');
            }

                // Ajouter le code de parrainage
                $referralCodeModel = new \App\Models\ReferralCodeModel();
                $referralCode = $referralCodeModel->where('user_id', $userId)->where('is_active', 1)->orderBy('created_at', 'DESC')->first();
                $user['referral_code'] = $referralCode ? $referralCode['code'] : null;

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
            log_message('error', 'Auth me error: ' . $e->getMessage());
            return $this->serverError('Erreur lors de la récupération du profil');
        }
    }

    /**
     * POST /api/auth/forgot-password - DÉPRÉCIÉ
     * Cette endpoint n'est plus utilisée (flux simplifié en une étape)
     */
    public function forgotPassword()
    {
        return $this->response->setStatusCode(410)->setJSON([
            'success' => false,
            'message' => 'Cette endpoint a été déplacée. Utilisez POST /api/auth/reset-password directement.',
            'code' => 'DEPRECATED'
        ]);
    }

    /**
     * POST /api/auth/reset-password - Réinitialiser le mot de passe
     * Accepte un numéro de téléphone et un nouveau mot de passe
     */
    public function resetPassword()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validation
            $errors = [];
            if (empty($data['phone'])) {
                $errors['phone'] = 'Le numéro de téléphone est requis';
            }
            if (empty($data['password']) && empty($data['new_password'])) {
                $errors['password'] = 'Nouveau mot de passe requis';
            }

            if (!empty($errors)) {
                return $this->validationError($errors);
            }

            // Utiliser le service d'authentification
            if (!$this->authService) {
                return $this->serverError('Service d\'authentification indisponible');
            }

            $newPassword = $data['password'] ?? $data['new_password'];
            $result = $this->authService->resetPasswordByPhone($data['phone'], $newPassword);

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'user' => $result['user']
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return $this->validationError(['password' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'RESET_FAILED'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Reset password error: ' . $e->getMessage());
            return $this->serverError('Erreur lors de la réinitialisation du mot de passe');
        }
    }
}