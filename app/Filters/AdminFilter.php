<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Vérifier d'abord l'authentification
        $authFilter = new JWTAuthFilter();
        $authResult = $authFilter->before($request, $arguments);
        
        // Si l'authentification échoue, retourner l'erreur
        if ($authResult instanceof ResponseInterface) {
            return $authResult;
        }

        // Vérifier si l'utilisateur a le rôle admin
        if (!isset($request->user['role_id']) || $request->user['role_id'] != 1) {
            return Services::response()
                ->setJSON([
                    'status' => 'error', 
                    'message' => 'Accès refusé. Permissions administrateur requises.'
                ])
                ->setStatusCode(403);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
