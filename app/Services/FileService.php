<?php

namespace App\Services;

use CodeIgniter\Files\File;
use RuntimeException;
use Exception;

class FileService
{
    protected $uploadPath = WRITEPATH . 'uploads/';
    protected $allowedTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    protected $watermarkText = 'POSTED ON CAMBIZZLE';

    /**
     * Upload et traite un fichier selon son type
     *
     * @param array $file Fichier depuis $_FILES
     * @param string $type Type de fichier ('profile', 'identity', 'ad')
     * @return array Information sur le fichier uploadé
     */
    public function upload($file, string $type): array
    {
        try {
            $this->validateFile($file);
            
            $path = $this->getStoragePath($type);
            $fileName = $this->generateUniqueFileName($file['name']);
            $fullPath = $path . $fileName;

            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new RuntimeException('Échec de l\'upload du fichier.');
            }

            // Ajoute un watermark si c'est une image d'annonce
            if ($type === 'ad' && $this->isImage($fullPath)) {
                $this->addWatermark($fullPath);
            }

            return [
                'fileName' => $fileName,
                'fullPath' => $fullPath,
                'type' => $file['type'],
                'size' => $file['size'],
                'url' => $this->getPublicUrl($type, $fileName)
            ];
        } catch (Exception $e) {
            throw new RuntimeException('Erreur lors de l\'upload : ' . $e->getMessage());
        }
    }

    /**
     * Valide le fichier uploadé
     */
    protected function validateFile($file): void
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Paramètres invalides.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->getUploadErrorMessage($file['error']));
        }

        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new RuntimeException('Type de fichier non autorisé.');
        }

        if ($file['size'] > 5242880) { // 5MB max
            throw new RuntimeException('Fichier trop volumineux.');
        }
    }

    /**
     * Génère le chemin de stockage selon le type de fichier
     */
    protected function getStoragePath(string $type): string
    {
        $basePath = $this->uploadPath . date('Y/m/');
        
        switch ($type) {
            case 'profile':
                return $basePath . 'profiles/';
            case 'identity':
                return $basePath . 'identity/';
            case 'ad':
                return $basePath . 'ads/';
            default:
                return $basePath . 'others/';
        }
    }

    /**
     * Ajoute un watermark au centre de l'image
     */
    protected function addWatermark(string $imagePath): void
    {
        // Charge l'image source
        $image = imagecreatefromstring(file_get_contents($imagePath));
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Configuration du texte - taille proportionnelle à l'image
        $minDimension = min($width, $height);
        // Font size: 2.5% of the minimum dimension (adjust this ratio as needed)
        $fontSize = max(12, round($minDimension * 0.025));
        
        $fontPath = ROOTPATH . 'public/fonts/arial.ttf'; // Assurez-vous d'avoir une police
        
        // Calcule la taille du texte
        $box = imagettfbbox($fontSize, 0, $fontPath, $this->watermarkText);
        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[5] - $box[1]);
        
        // Position au centre
        $x = ($width - $textWidth) / 2;
        $y = ($height + $textHeight) / 2;
        
        // Couleur semi-transparente
        $white = imagecolorallocatealpha($image, 255, 255, 255, 75);
        
        // Ajoute le texte
        imagettftext($image, $fontSize, 0, $x, $y, $white, $fontPath, $this->watermarkText);
        
        // Sauvegarde l'image
        imagejpeg($image, $imagePath, 90);
        imagedestroy($image);
    }

    /**
     * Génère un nom de fichier unique
     */
    protected function generateUniqueFileName(string $originalName): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        return sprintf('%s.%s', bin2hex(random_bytes(16)), $ext);
    }

    /**
     * Retourne l'URL publique du fichier
     */
    protected function getPublicUrl(string $type, string $fileName): string
    {
        return sprintf('/uploads/%s/%s/%s/%s', 
            date('Y'),
            date('m'),
            $type,
            $fileName
        );
    }

    /**
     * Vérifie si le fichier est une image
     */
    protected function isImage(string $path): bool
    {
        return in_array(mime_content_type($path), $this->allowedTypes);
    }

    /**
     * Retourne le message d'erreur selon le code
     */
    protected function getUploadErrorMessage($error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée.';
            case UPLOAD_ERR_PARTIAL:
                return 'Le fichier a été partiellement uploadé.';
            case UPLOAD_ERR_NO_FILE:
                return 'Aucun fichier n\'a été uploadé.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Dossier temporaire manquant.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Échec de l\'écriture du fichier.';
            case UPLOAD_ERR_EXTENSION:
                return 'Une extension PHP a arrêté l\'upload.';
            default:
                return 'Une erreur inconnue est survenue.';
        }
    }
}
