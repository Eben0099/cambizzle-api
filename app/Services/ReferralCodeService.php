<?php

namespace App\Services;

use App\Models\ReferralCodeModel;
use CodeIgniter\Database\BaseConnection;

class ReferralCodeService
{
    protected $model;
    protected $db;

    public function __construct(ReferralCodeModel $model, BaseConnection $db)
    {
        $this->model = $model;
        $this->db = $db;
    }

    /**
     * Génère un code de parrainage unique de 8 caractères commençant par CB
     */
    public function generateUniqueCode(): string
    {
        $length = 6;
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        do {
            $random = '';
            for ($i = 0; $i < $length; $i++) {
                $random .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $code = 'CB' . $random;
        } while ($this->model->where('code', $code)->first());
        return $code;
    }

    /**
     * Crée un code de parrainage pour un utilisateur
     */
    public function createReferralCode(int $userId, ?string $description = null, int $maxUses = 0, float $bonusAmount = 0.0): array
    {
        $code = $this->generateUniqueCode();
        $data = [
            'code' => $code,
            'user_id' => $userId
        ];
        $id = $this->model->insert($data, true);
        // Correction: $id peut être un tableau ou un entier selon CodeIgniter
        if (is_array($id) && isset($id['id'])) {
            $id = $id['id'];
        }
        // Correction supplémentaire : si $id est un tableau avec une clé 'insertID'
        if (is_array($id) && isset($id['insertID'])) {
            $id = $id['insertID'];
        }
        // Si $id est null, essayer de récupérer le dernier insert id
        if (empty($id)) {
            $id = $this->model->getInsertID();
        }
        $result = $this->model->find($id);
        if (!$result || !is_array($result)) {
            // Si l'insertion a échoué ou rien n'est trouvé, retourne un tableau vide pour éviter l'erreur TypeError
            return [];
        }
        return $result;
    }

    /**
     * Vérifie la validité d'un code de parrainage
     */
    public function validateReferralCode(string $code): ?array
    {
        $referral = $this->model->where('code', $code)->where('is_active', 1)->first();
        if (!$referral) return null;
        if ($referral['max_uses'] > 0 && $referral['current_uses'] >= $referral['max_uses']) return null;
        if ($referral['expires_at'] && strtotime($referral['expires_at']) < time()) return null;
        return $referral;
    }

    /**
     * Met à jour le user_id d'un code de parrainage
     */
    public function updateReferralUser(int $referralId, int $userId): bool
    {
        // Utilise la méthode publique du modèle
        return $this->model->update($referralId, ['user_id' => $userId]);
    }

    /**
     * Utilise un code de parrainage
     */
    public function useReferralCode(int $referralCodeId, int $referrerId, int $referredUserId): bool
    {
        // Incrémente le nombre d'utilisations du code
        $this->model->incrementUses($referralCodeId);
        // Ajoute une entrée dans referral_uses
        $db = \Config\Database::connect();
        return $db->table('referral_uses')->insert([
            'referral_code_id' => $referralCodeId,
            'referrer_id' => $referrerId,
            'referred_user_id' => $referredUserId,
            'used_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Incrémente le compteur d'utilisation d'un code de parrainage
     */
    public function incrementUses(int $id): bool
    {
        // Vérifie que l'id est valide et que le code existe
        if (!$id) {
            log_message('error', 'incrementUses: id manquant');
            return false;
        }
        $code = $this->model->find($id);
        if (!$code) {
            log_message('error', 'incrementUses: code de parrainage introuvable pour id ' . $id);
            return false;
        }
        // Utilise la méthode du modèle pour incrémenter le compteur
        return $this->model->incrementUses($id);
    }
}
