<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

class FileController extends BaseApiController
{
    /**
     * Servir les fichiers uploadés depuis le dossier uploads/
     * Cette route permet d'accéder aux fichiers via /api/uploads/ads/nomfichier.jpg
     */
    public function serveFile(string $subpath = '', string $filename = '')
    {
        // Permettre l'accès depuis n'importe quelle origine
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        // Si c'est une requête OPTIONS (CORS preflight), on renvoie juste les headers
        if ($this->request->getMethod() === 'options') {
            return $this->response;
        }

        try {
            // Construire le chemin complet du fichier (dans public/)
            $filePath = FCPATH . 'uploads/' . $subpath . '/' . $filename;
            
            // Nettoyer le chemin pour éviter les directory traversal attacks
            $realPath = realpath($filePath);
            $uploadsPath = realpath(FCPATH . 'uploads/');
            
            // Vérifier que le fichier est bien dans le dossier uploads/
            if (!$realPath || !$uploadsPath || strpos($realPath, $uploadsPath) !== 0) {
                return $this->failNotFound('Fichier non trouvé');
            }
            
            // Vérifier que le fichier existe
            if (!is_file($realPath)) {
                return $this->failNotFound('Fichier non trouvé');
            }
            
            // Obtenir les informations du fichier
            $mimeType = mime_content_type($realPath);
            $fileSize = filesize($realPath);
            
            // Définir les headers appropriés
            $response = $this->response;
            $response->setHeader('Content-Type', $mimeType);
            $response->setHeader('Content-Length', (string)$fileSize);
            $response->setHeader('Cache-Control', 'public, max-age=86400'); // Cache 1 jour
            
            // Lire et envoyer le fichier
            $response->setBody(file_get_contents($realPath));
            
            return $response;
            
        } catch (\Exception $e) {
            return $this->failServerError('Erreur lors de la récupération du fichier');
        }
    }
}