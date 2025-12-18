<?php

namespace App\Validation;

class MessageRules
{
    /**
     * Vérifier que l'annonce existe et est active
     */
    public function validAd(string $str, string $fields, array $data): bool
    {
        $adModel = new \App\Models\AdModel();
        $ad = $adModel->find($str);

        return $ad && $ad->status === 'active' && $ad->moderation_status === 'approved';
    }

    /**
     * Vérifier que l'utilisateur n'est pas le propriétaire de l'annonce
     */
    public function notAdOwner(string $str, string $fields, array $data): bool
    {
        if (!isset($data['ad_id'])) {
            return false;
        }

        $adModel = new \App\Models\AdModel();
        $ad = $adModel->find($data['ad_id']);

        // Cette vérification nécessiterait l'ID de l'utilisateur connecté
        // Pour l'instant, on simule
        return $ad && $ad->user_id != 1; // Simulation
    }

    /**
     * Vérifier que l'utilisateur n'a pas déjà noté cette annonce
     */
    public function notAlreadyReviewed(string $str, string $fields, array $data): bool
    {
        if (!isset($data['ad_id']) || !isset($data['type']) || $data['type'] !== 'review') {
            return true; // Pas applicable
        }

        $messageModel = new \App\Models\MessageModel();

        // Cette vérification nécessiterait l'ID de l'utilisateur connecté
        // Pour l'instant, on simule
        $userId = 1; // Simulation

        return !$messageModel->where('user_id', $userId)
                            ->where('ad_id', $data['ad_id'])
                            ->where('type', 'review')
                            ->first();
    }

    /**
     * Vérifier que la note est valide pour les avis
     */
    public function validRating(string $str, string $fields, array $data): bool
    {
        if (!isset($data['type']) || $data['type'] !== 'review') {
            return true; // Pas applicable
        }

        $rating = (int) $str;
        return $rating >= 1 && $rating <= 5;
    }

    /**
     * Vérifier que le message parent existe et appartient à la même annonce
     */
    public function validParentMessage(string $str, string $fields, array $data): bool
    {
        if (empty($str)) {
            return true; // Pas obligatoire
        }

        $messageModel = new \App\Models\MessageModel();
        $parentMessage = $messageModel->find($str);

        if (!$parentMessage) {
            return false;
        }

        // Vérifier que le message parent appartient à la même annonce
        return isset($data['ad_id']) && $parentMessage->ad_id == $data['ad_id'];
    }

    /**
     * Vérifier que l'utilisateur peut accéder au message
     */
    public function canAccessMessage(string $str, string $fields, array $data): bool
    {
        $messageModel = new \App\Models\MessageModel();
        $message = $messageModel->find($str);

        if (!$message) {
            return false;
        }

        // Cette vérification nécessiterait l'ID de l'utilisateur connecté
        // Pour l'instant, on simule
        $userId = 1; // Simulation

        $adModel = new \App\Models\AdModel();
        $ad = $adModel->find($message->ad_id);

        return $ad && ($message->user_id == $userId || $ad->user_id == $userId);
    }
}
