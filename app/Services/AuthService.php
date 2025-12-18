<?php

namespace App\Services;

use App\Models\UserModel;
use App\Entities\UserEntity;

class AuthService
{
    protected $model;
    protected $jwtService;
    protected $uploadService;
    protected $userService;

    public function __construct(UserModel $model, JWTService $jwtService, UploadService $uploadService, UserService $userService)
    {
        $this->model = $model;
        $this->jwtService = $jwtService;
        $this->uploadService = $uploadService;
        $this->userService = $userService;
    }

    /**
     * Inscription avec email/mot de passe
     */
    public function register(array $userData): array
    {
        // Data validation with explicit messages
        if (empty($userData['phone'])) {
            throw new \InvalidArgumentException('Phone number is required');
        }
        if (empty($userData['password'])) {
            throw new \InvalidArgumentException('Password is required');
        }

        // Check if user already exists with more explicit messages
        if (!empty($userData['email'])) {
            $existingEmail = $this->model->where('email', $userData['email'])->first();
            if ($existingEmail) {
                throw new \RuntimeException('An account with this email address already exists');
            }
        }
        
        $existingPhone = $this->model->where('phone', $userData['phone'])->first();
        if ($existingPhone) {
            throw new \RuntimeException('An account with this phone number already exists');
        }

        // Nettoyer et préparer les données
        // Convertir les chaînes vides en null pour l'email
        if (isset($userData['email']) && trim($userData['email']) === '') {
            $userData['email'] = null;
        }

        // Hasher le mot de passe
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);

        // Définir les valeurs par défaut
        $userData['is_verified'] = false; // Non vérifié par défaut
        $userData['slug'] = $this->generateSlug(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''));
        $userData['role_id'] = $userData['role_id'] ?? 2; // Rôle par défaut: vendeur

        // Gérer l'upload de photo si présent
        if (isset($userData['photo']) && $userData['photo'] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
            $uploadPath = FCPATH . 'uploads/profiles/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            if ($userData['photo']->isValid() && !$userData['photo']->hasMoved()) {
                $newName = $userData['photo']->getRandomName();
                $userData['photo']->move($uploadPath, $newName);
                $userData['photo_url'] = 'uploads/profiles/' . $newName;
            }
            unset($userData['photo']);
        }

        // Si un code de parrainage est fourni, on vérifie et prépare les infos du parrain
        $parentReferralId = null;
        $parentUserId = null;
        if (!empty($userData['referral_code'])) {
            $referralCodeService = service('referralCodeService');
            $validReferral = $referralCodeService->validateReferralCode($userData['referral_code']);
            if (!$validReferral) {
                throw new \RuntimeException('Invalid or expired referral code');
            }
            // On incrémente current_uses
            $referralCodeService->incrementUses($validReferral['id']);
            $parentReferralId = $validReferral['id'];
            $parentUserId = $validReferral['user_id'];
        }
        // Inscription du filleul
        $userId = $this->model->insert($userData, true);
        if (!$userId) {
            log_message('error', 'Insert failed: ' . json_encode($this->model->errors()));
            throw new \RuntimeException('User insert failed: ' . json_encode($this->model->errors()));
        }
        // Génération du code de parrainage pour le filleul
        $referralCode = service('referralCodeService')->createReferralCode($userId);
        // Si parrainage, on insère dans referral_uses
        if ($parentReferralId && $parentUserId) {
            $db = \Config\Database::connect();
            $db->table('referral_uses')->insert([
                'referral_code_id' => $parentReferralId,
                'referrer_id' => $parentUserId,
                'referred_user_id' => $userId,
                'used_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $user = $this->model->find($userId);
        $token = $this->generateToken($user);

        return [
            'user' => $user,
            'referral_code' => $referralCode['code'],
            'token' => $token
        ];
    }

    /**
     * Valider un token et retourner le payload
     */
    public function validateToken(string $token): object
    {
        $payload = $this->jwtService->decode($token);
        return (object) $payload;
    }

    /**
     * Inscription avec Google
     */
    public function registerWithGoogle(array $googleData): array
    {
        // Vérifier si l'utilisateur existe déjà avec Google ID
        $existingUser = $this->model->where('google_id', $googleData['id'])->first();

        if ($existingUser) {
            $token = $this->generateToken($existingUser);
            return [
                'user' => $existingUser,
                'token' => $token
            ];
        }

        // Vérifier si l'email existe déjà
        $userByEmail = $this->model->where('email', $googleData['email'])->first();
        if ($userByEmail) {
            // Lier le compte Google à l'utilisateur existant
            $this->model->update($userByEmail['id_user'], [
                'google_id' => $googleData['id'],
                'is_verified' => true // Email vérifié par Google
            ]);

            $updatedUser = $this->model->find($userByEmail['id_user']);
            $token = $this->generateToken($updatedUser);

            return [
                'user' => $updatedUser,
                'token' => $token
            ];
        }

        // Créer un nouvel utilisateur
        $userData = [
            'first_name' => $googleData['given_name'] ?? '',
            'last_name' => $googleData['family_name'] ?? '',
            'email' => $googleData['email'],
            'google_id' => $googleData['id'],
            'is_verified' => true, // Email vérifié par Google
            'role_id' => 2, // Vendeur par défaut
            'photo_url' => $googleData['picture'] ?? null
        ];

        $userData['slug'] = $this->generateSlug($userData['first_name'] . ' ' . $userData['last_name']);
        $userData['password_hash'] = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $userId = $this->model->insert($userData);
        $user = $this->model->find($userId);
        $token = $this->generateToken($user);

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Inscription avec Facebook
     */
    public function registerWithFacebook(array $facebookData): array
    {
        // Implémentation similaire à Google
        $existingUser = $this->model->where('facebook_id', $facebookData['id'])->first();

        if ($existingUser) {
            $token = $this->generateToken($existingUser);
            return [
                'user' => $existingUser,
                'token' => $token
            ];
        }

        $userByEmail = $this->model->where('email', $facebookData['email'])->first();
        if ($userByEmail) {
            $this->model->update($userByEmail['id_user'], [
                'facebook_id' => $facebookData['id'],
                'is_verified' => false
            ]);

            $updatedUser = $this->model->find($userByEmail['id_user']);
            $token = $this->generateToken($updatedUser);

            return [
                'user' => $updatedUser,
                'token' => $token
            ];
        }

        $userData = [
            'first_name' => $facebookData['first_name'] ?? '',
            'last_name' => $facebookData['last_name'] ?? '',
            'email' => $facebookData['email'],
            'facebook_id' => $facebookData['id'],
            'is_verified' => false,
            'role_id' => 2,
            'photo_url' => $facebookData['picture']['data']['url'] ?? null
        ];

        $userData['slug'] = $this->generateSlug($userData['first_name'] . ' ' . $userData['last_name']);
        $userData['password_hash'] = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $userId = $this->model->insert($userData);
        $user = $this->model->find($userId);
        $token = $this->generateToken($user);

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Connexion standard par téléphone ou email (priorité téléphone)
     */
    public function login(string $loginValue, string $password, string $loginType = null): array
    {
        if ($loginType === 'phone') {
            $user = $this->model->where('phone', $loginValue)->first();
            if (!$user) {
                throw new \RuntimeException('ACCOUNT_NOT_FOUND:No account exists with this phone number. Please create an account.');
            }
            if (!password_verify($password, $user['password_hash'])) {
                throw new \RuntimeException('INVALID_PASSWORD:Incorrect password. Please try again.');
            }
        } else {
            $user = $this->model->where('email', $loginValue)->first();
            if (!$user) {
                throw new \RuntimeException('ACCOUNT_NOT_FOUND:No account exists with this email address. Please create an account.');
            }
            if (!password_verify($password, $user['password_hash'])) {
                throw new \RuntimeException('INVALID_PASSWORD:Incorrect password. Please try again.');
            }
        }
        $token = $this->generateToken($user);
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Vérification d'identité (CNI/Passeport)
     */
    public function verifyIdentity(int $userId, string $documentType, string $documentNumber, $documentFile): bool
    {
        $uploadPath = FCPATH . 'uploads/identity/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Upload du fichier
        if ($documentFile->isValid() && !$documentFile->hasMoved()) {
            $newName = $documentFile->getRandomName();
            $documentFile->move($uploadPath, $newName);
            
            $data = [
                'identity_document_type' => $documentType,
                'identity_document_number' => $documentNumber,
                'identity_document_url' => 'uploads/identity/' . $newName,
                // Nouveau flux: utiliser is_verified (0,2,3,4,1) → 2 = soumis en attente
                'is_verified' => 2,
                'identity_submitted_at' => date('Y-m-d H:i:s')
            ];

            return $this->model->update($userId, $data);
        }

        throw new \RuntimeException('Erreur lors de l\'upload du document');
    }

    /**
     * Génération du token JWT
     */
    protected function generateToken(array $user): string
    {
        return $this->jwtService->encode([
            'user_id' => $user['id_user'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'is_identity_verified' => $user['is_identity_verified']
        ]);
    }

    /**
     * Génération de slug unique
     */
    protected function generateSlug(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'user-' . uniqid();
        }

        // Vérifier l'unicité
        $slug = $text;
        $counter = 1;
        while ($this->model->where('slug', $slug)->first()) {
            $slug = $text . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Demande de réinitialisation de mot de passe par numéro de téléphone
     * Réinitialisation du mot de passe avec numéro de téléphone
     * Flux simplifié: téléphone + nouveau mot de passe
     */
    public function resetPasswordByPhone(string $phone, string $newPassword): array
    {
        // Basic validation
        if (empty($phone) || empty($newPassword)) {
            throw new \InvalidArgumentException('Phone number and password required');
        }

        if (strlen($newPassword) < 6) {
            throw new \InvalidArgumentException('Password must contain at least 6 characters');
        }

        // Find user with this phone number
        $user = $this->model->where('phone', $phone)->first();
        
        if (!$user) {
            throw new \RuntimeException('No account found with this phone number');
        }

        // Mettre à jour le mot de passe
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->model->update($user['id_user'], [
            'password_hash' => $newHash
        ]);

        $updatedUser = $this->model->find($user['id_user']);
        log_message('info', "Password reset successful for user {$user['id_user']} via phone reset");

        return [
            'message' => 'Password reset successfully',
            'user' => $updatedUser
        ];
    }
}