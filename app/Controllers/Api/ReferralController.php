<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\ReferralCodeModel;
use App\Models\ReferralUseModel;
use App\Services\AuthService;

class ReferralController extends BaseApiController
{
    protected $referralCodeModel;
    protected $referralUseModel;
    protected $authService;

    public function __construct()
    {
        $this->referralCodeModel = new ReferralCodeModel();
        $this->referralUseModel = new ReferralUseModel();
        $this->authService = service('authService');
    }

    /**
     * GET /api/referrals - Liste des codes de parrainage avec infos propriétaire et filleuls
     */
    public function index()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Authentication token required');
            }
            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $db = \Config\Database::connect();
            $codes = $db->table('referral_codes')
                ->select('referral_codes.id, referral_codes.code, referral_codes.current_uses, referral_codes.is_active, users.first_name, users.last_name, users.email, users.phone')
                ->join('users', 'users.id_user = referral_codes.user_id')
                ->where('referral_codes.user_id', $userId)
                ->get()->getResultArray();

            foreach ($codes as &$code) {
                $filleuls = $db->table('referral_uses')
                    ->select('users.id_user, users.first_name, users.last_name, users.email, users.phone, referral_uses.used_at')
                    ->join('users', 'users.id_user = referral_uses.referred_user_id')
                    ->where('referral_uses.referral_code_id', $code['id'])
                    ->get()->getResultArray();
                $code['filleuls'] = $filleuls;
            }

            // On retourne tous les codes, pas seulement le premier
            return $this->success(array_values($codes), 'Referral codes and referrals retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/referrals - Créer un code de parrainage
     */
    public function create()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Authentication token required');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $data = $this->request->getJSON(true) ?? [];

            // Validation des données
            $rules = [
                'description' => 'permit_empty|string|max_length[255]',
                'max_uses' => 'permit_empty|integer|greater_than_equal_to[0]',
                'bonus_amount' => 'required|numeric|greater_than_equal_to[0]',
                'expires_at' => 'permit_empty|valid_date[Y-m-d H:i:s]'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Préparer les données avec valeurs par défaut
            $insertData = [
                'user_id' => $userId,
                'description' => $data['description'] ?? null,
                'max_uses' => $data['max_uses'] ?? 0,
                'current_uses' => 0,
                'bonus_amount' => $data['bonus_amount'],
                'is_active' => $data['is_active'] ?? 1,
                'expires_at' => $data['expires_at'] ?? null
            ];

            // Le code sera généré automatiquement par le modèle si vide
            if (!empty($data['code'])) {
                $insertData['code'] = $data['code'];
            }

            $codeId = $this->referralCodeModel->insert($insertData);

            if (!$codeId) {
                $errors = $this->referralCodeModel->errors();
                return $this->validationError($errors ?: ['general' => 'Failed to create referral code. Please try again.']);
            }

            $code = $this->referralCodeModel->find($codeId);

            return $this->success($code, 'Referral code created successfully', 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/referrals/use - Utiliser un code de parrainage
     */
    public function useCode()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Authentication token required');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $data = $this->request->getJSON(true);

            if (empty($data['code'])) {
                return $this->validationError(['code' => 'Referral code is required']);
            }

            // Trouver le code de parrainage
            $referralCode = $this->referralCodeModel
                ->where('code', $data['code'])
                ->where('is_active', true)
                ->first();

            if (!$referralCode) {
                return $this->validationError(['code' => 'Invalid referral code']);
            }

            // Vérifier que l'utilisateur n'a pas déjà été parrainé
            if ($this->referralUseModel->isUserAlreadyReferred($userId)) {
                return $this->validationError(['code' => 'You have already used a referral code']);
            }

            // Vérifier que l'utilisateur n'est pas le propriétaire du code
            if ($referralCode['user_id'] === $userId) {
                return $this->validationError(['code' => 'You cannot use your own referral code']);
            }

            // Vérifier la limite d'utilisation
            $usesCount = $this->referralUseModel->where('referral_code_id', $referralCode['id'])->countAllResults();
            if ($referralCode['max_uses'] > 0 && $usesCount >= $referralCode['max_uses']) {
                return $this->validationError(['code' => 'Referral code has reached its maximum uses']);
            }

            // Créer l'utilisation
            $useData = [
                'referral_code_id' => $referralCode['id'],
                'referrer_id' => $referralCode['user_id'],
                'referred_user_id' => $userId,
                'bonus_earned' => $referralCode['bonus_amount'],
                'used_at' => date('Y-m-d H:i:s')
            ];

            if (isset($data['ad_id'])) {
                $useData['ad_id'] = $data['ad_id'];
            }

            $useId = $this->referralUseModel->insert($useData);

            if (!$useId) {
                $errors = $this->referralUseModel->errors();
                return $this->validationError($errors ?: ['general' => 'Failed to use referral code. Please try again.']);
            }

            // Incrémenter le nombre d'utilisations du code
            $this->referralCodeModel->incrementUses($referralCode['id']);

            return $this->success([
                'bonus_earned' => $referralCode['bonus_amount']
            ], 'Referral code used successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/referrals/stats - Statistiques de parrainage
     */
    public function stats()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Authentication token required');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            // Statistiques pour l'utilisateur
            $totalBonus = $this->referralUseModel->getTotalBonusEarned($userId);
            $successfulReferrals = $this->referralUseModel->countSuccessfulReferrals($userId);
            $activeCodes = $this->referralCodeModel
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->countAllResults();

            return $this->success([
                'total_bonus_earned' => $totalBonus,
                'successful_referrals' => $successfulReferrals,
                'active_codes' => $activeCodes
            ], 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Générer un code de parrainage unique
     */
    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while ($this->referralCodeModel->where('code', $code)->countAllResults() > 0);

        return $code;
    }
}
