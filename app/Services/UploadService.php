<?php
namespace App\Services;

use CodeIgniter\Files\File;

class UploadService
{
    /**
     * Upload a file and optionally add watermark
     * @param string $fieldName
     * @param string $uploadPath
     * @param bool $addWatermark
     * @param bool $isCniOrProfile
     * @return array
     */
    public function upload(string $fieldName, string $uploadPath, bool $addWatermark = true, bool $isCniOrProfile = false): array
    {
        $file = request()->getFile($fieldName);
        if (!$file->isValid()) {
            return [
                'success' => false,
                'error' => $file->getErrorString()
            ];
        }

        // Construire le chemin absolu vers le dossier uploads/ dans le dossier public/
        // FCPATH pointe vers le dossier public/
        $absoluteUploadPath = FCPATH . $uploadPath;
        
        // Création automatique du dossier cible si nécessaire
        if (!is_dir($absoluteUploadPath)) {
            mkdir($absoluteUploadPath, 0777, true);
        }
        $newName = $file->getRandomName();
        $file->move($absoluteUploadPath, $newName);
        $filePath = $absoluteUploadPath . DIRECTORY_SEPARATOR . $newName;
        if ($addWatermark && !$isCniOrProfile) {
            $this->addWatermark($filePath);
        }
        return [
            'success' => true,
            'file' => $newName,
            'path' => $filePath,
            'public_path' => ltrim(str_replace(['public'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $uploadPath) . '/' . $newName, '/')
        ];
    }

    /**
     * Upload multiple files for a given field (e.g., photos[])
     * @param string $fieldName
     * @param string $uploadPath Relative to project root (e.g., 'public/uploads/feedbacks')
     * @param bool $addWatermark
     * @param bool $isCniOrProfile
     * @return array{success:bool, files?:array<int, array>, errors?:array<int, string>}
     */
    public function uploadMultiple(string $fieldName, string $uploadPath, bool $addWatermark = true, bool $isCniOrProfile = false): array
    {
        $files = request()->getFileMultiple($fieldName);
        if (!$files || count($files) === 0) {
            return ['success' => false, 'errors' => ['Aucun fichier reçu']];
        }

        $absoluteUploadPath = FCPATH . $uploadPath;
        if (!is_dir($absoluteUploadPath)) {
            mkdir($absoluteUploadPath, 0777, true);
        }

        $uploaded = [];
        $errors = [];
        foreach ($files as $index => $file) {
            if (!$file->isValid()) {
                $errors[$index] = $file->getErrorString();
                continue;
            }
            $newName = $file->getRandomName();
            $file->move($absoluteUploadPath, $newName);
            $filePath = $absoluteUploadPath . DIRECTORY_SEPARATOR . $newName;
            if ($addWatermark && !$isCniOrProfile) {
                $this->addWatermark($filePath);
            }
            $uploaded[] = [
                'file' => $newName,
                'path' => $filePath,
                'public_path' => ltrim(str_replace(['public'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $uploadPath) . '/' . $newName, '/')
            ];
        }

        return [
            'success' => count($uploaded) > 0,
            'files' => $uploaded,
            'errors' => $errors
        ];
    }

    /**
     * Add watermark to image
     * @param string $filePath
     */
    protected function addWatermark(string $filePath)
    {
        // Utiliser getimagesize() au lieu de exif_imagetype() car cette fonction est disponible par défaut
        $imageInfo = getimagesize($filePath);
        if ($imageInfo === false) {
            return; // Fichier non valide ou non supporté
        }

        $imageType = $imageInfo[2]; // Le type d'image est dans l'index 2
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filePath);
                break;
            default:
                return;
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $text = 'POSTED ON CAMBIZZLE';
        
        // Calculate font size proportionally to image dimensions
        // Use the smaller dimension to calculate the proportion
        $minDimension = min($width, $height);
        // Font size: 2.5% of the minimum dimension (adjust this ratio as needed)
        $fontSize = max(12, round($minDimension * 0.025));
        
        $fontFile = __DIR__ . '/../../public/fonts/GeistMono-VariableFont_wght.ttf'; // à adapter selon le chemin réel
        $textColor = imagecolorallocate($image, 255, 0, 0);
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $x = ($width - $textWidth) / 2;
        $y = ($height + $textHeight) / 2;
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $text);
        if ($imageType == IMAGETYPE_JPEG) {
            imagejpeg($image, $filePath);
        } else {
            imagepng($image, $filePath);
        }
        imagedestroy($image);
    }
}
