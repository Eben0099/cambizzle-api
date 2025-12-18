<?php

namespace App\Validation;

class ReportRules
{
    /**
     * Vérifier qu'au moins un élément est signalé
     */
    public function hasReportedItem(string $str, string $fields, array $data): bool
    {
        return !empty($data['reported_user_id']) || !empty($data['reported_ad_id']);
    }

    /**
     * Vérifier que l'utilisateur signalé existe
     */
    public function validReportedUser(string $str, string $fields, array $data): bool
    {
        if (empty($str)) {
            return true; // Optionnel
        }

        $userModel = new \App\Models\UserModel();
        return $userModel->find($str) !== null;
    }

    /**
     * Vérifier que l'annonce signalée existe
     */
    public function validReportedAd(string $str, string $fields, array $data): bool
    {
        if (empty($str)) {
            return true; // Optionnel
        }

        $adModel = new \App\Models\AdModel();
        return $adModel->find($str) !== null;
    }

    /**
     * Vérifier que l'utilisateur ne signale pas sa propre annonce
     */
    public function notSelfReportedAd(string $str, string $fields, array $data): bool
    {
        if (empty($str)) {
            return true; // Optionnel
        }

        $adModel = new \App\Models\AdModel();
        $ad = $adModel->find($str);

        // Cette vérification nécessiterait l'ID de l'utilisateur connecté
        // Pour l'instant, on simule
        $userId = 1; // Simulation

        return $ad && $ad->user_id != $userId;
    }

    /**
     * Vérifier que l'utilisateur ne se signale pas lui-même
     */
    public function notSelfReportedUser(string $str, string $fields, array $data): bool
    {
        if (empty($str)) {
            return true; // Optionnel
        }

        // Cette vérification nécessiterait l'ID de l'utilisateur connecté
        // Pour l'instant, on simule
        $userId = 1; // Simulation

        return $str != $userId;
    }

    /**
     * Vérifier que l'utilisateur n'a pas déjà fait ce signalement
     */
    public function notAlreadyReported(string $str, string $fields, array $data): bool
    {
        $reportModel = new \App\Models\ReportModel();

        // Cette vérification nécessiterait l'ID de l'utilisateur connecté
        // Pour l'instant, on simule
        $userId = 1; // Simulation

        return !$reportModel->hasAlreadyReported(
            $userId,
            $data['reported_user_id'] ?? null,
            $data['reported_ad_id'] ?? null
        );
    }

    /**
     * Vérifier que le type de signalement est valide
     */
    public function validReportType(string $str, string $fields, array $data): bool
    {
        $validTypes = ['user', 'ad', 'spam', 'fraud', 'harassment', 'other'];
        return in_array($str, $validTypes);
    }

    /**
     * Vérifier que l'administrateur existe
     */
    public function validAdmin(string $str, string $fields, array $data): bool
    {
        if (empty($str)) {
            return true; // Optionnel
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($str);

        // Cette vérification nécessiterait de vérifier les permissions admin
        // Pour l'instant, on simule
        return $user !== null;
    }
}
