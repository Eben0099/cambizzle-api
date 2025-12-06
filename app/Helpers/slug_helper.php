<?php

if (!function_exists('safe_url_title')) {
    /**
     * Génère un slug URL sécurisé en supprimant les caractères accentués et non-ASCII
     *
     * @param string $title Le titre à convertir en slug
     * @param string $separator Le séparateur à utiliser (par défaut '-')
     * @param bool $lowercase Convertir en minuscules (par défaut true)
     * @return string Le slug sécurisé
     */
    function safe_url_title(string $title, string $separator = '-', bool $lowercase = true): string
    {
        // Nettoyer le titre de base
        $title = trim($title);

        // Convertir les caractères accentués en caractères ASCII
        $title = remove_accents($title);

        // Convertir en minuscules si demandé
        if ($lowercase) {
            $title = strtolower($title);
        }

        // Remplacer les espaces et caractères spéciaux par le séparateur
        $title = preg_replace('/[^\w]+/', $separator, $title);

        // Supprimer tous les caractères non autorisés (lettres, chiffres, tirets)
        $slug = preg_replace('/[^a-zA-Z0-9\-]/', '', $title);

        // Supprimer les tirets multiples et les tirets au début/à la fin
        $slug = preg_replace('/-+/', $separator, $slug);
        $slug = trim($slug, $separator);

        return $slug;
    }
}

if (!function_exists('remove_accents')) {
    /**
     * Supprime les accents et caractères spéciaux d'une chaîne
     *
     * @param string $string La chaîne à nettoyer
     * @return string La chaîne sans accents
     */
    function remove_accents(string $string): string
    {
        // Tableau de correspondance des caractères accentués
        $accents = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 's',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'Þ' => 'th',
        ];

        return strtr($string, $accents);
    }
}

if (!function_exists('generate_ad_slug')) {
    /**
     * Génère un slug unique pour une annonce
     *
     * @param string $title Le titre de l'annonce
     * @return string Le slug unique avec timestamp
     */
    function generate_ad_slug(string $title): string
    {
        // Log pour debug
        error_log('[SLUG_HELPER] generate_ad_slug appelée avec: "' . $title . '"');

        $safeSlug = safe_url_title($title);

        // Vérifier si le slug contient des accents
        $hasAccents = preg_match('/[àâäéèêëïîôöùûüÿñç]/i', $safeSlug);
        if ($hasAccents) {
            error_log('[SLUG_HELPER] ⚠️ ATTENTION: Le slug contient encore des accents: "' . $safeSlug . '"');
        } else {
            error_log('[SLUG_HELPER] ✅ Slug sans accents généré: "' . $safeSlug . '"');
        }

        $finalSlug = $safeSlug . '-' . time();
        error_log('[SLUG_HELPER] Slug final: "' . $finalSlug . '"');

        return $finalSlug;
    }
}
