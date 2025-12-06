<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class JWTAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            return Services::response()
                ->setJSON(['status' => 'error', 'message' => 'Token d\'authentification manquant'])
                ->setStatusCode(401);
        }

        // Extraire le token du header Authorization (format: "Bearer token")
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return Services::response()
                ->setJSON(['status' => 'error', 'message' => 'Format de token invalide'])
                ->setStatusCode(401);
        }

        $token = $matches[1];

        try {
            $jwtService = Services::jwtService();
            $payload = $jwtService->decode($token);

            // Ajouter les informations utilisateur à la requête
            $request->user = $payload;

            // Vérifier si l'utilisateur existe toujours
            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($payload['user_id']);
            
            if (!$user) {
                return Services::response()
                    ->setJSON(['status' => 'error', 'message' => 'Utilisateur non trouvé'])
                    ->setStatusCode(401);
            }

            // Vérifier si l'utilisateur est actif
            if (isset($user['deleted_at']) && $user['deleted_at'] !== null) {
                return Services::response()
                    ->setJSON(['status' => 'error', 'message' => 'Compte désactivé'])
                    ->setStatusCode(401);
            }

        } catch (\Exception $e) {
            return Services::response()
                ->setJSON(['status' => 'error', 'message' => 'Token invalide: ' . $e->getMessage()])
                ->setStatusCode(401);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Rien à faire après la requête
        return $response;
    }
}
