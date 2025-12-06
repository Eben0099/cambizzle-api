<?php
namespace App\Services;

class SlugService
{
    /**
     * Génère un slug à partir d'une chaîne de caractères
     * - Remplace les accents
     * - Supprime les caractères spéciaux
     * - Remplace les espaces par des tirets
     * - Met en minuscules
     */
    public static function generate($string)
    {
        // Remplacer les accents
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        // Supprimer tout ce qui n'est pas lettre, chiffre ou espace
        $string = preg_replace('/[^A-Za-z0-9 ]/', '', $string);
        // Remplacer les espaces par des tirets
        $string = preg_replace('/\s+/', '-', $string);
        // Mettre en minuscules
        $string = strtolower($string);
        // Supprimer les tirets en début/fin
        $string = trim($string, '-');
        return $string;
    }
}
