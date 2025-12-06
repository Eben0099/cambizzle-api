<?php
namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class BaseApiController extends ResourceController
{
    use ResponseTrait;

    protected $format = 'json';

    /**
     * OPTIONS - Gestion des requêtes preflight CORS
     * Cette méthode est héritée par tous les contrôleurs API
     */
    public function options()
    {
        // Ajouter explicitement les en-têtes CORS
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Max-Age', '3600')
            ->setStatusCode(204);
    }

    /**
     * Réponse de succès générique
     */
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        // Ajouter les en-têtes CORS à chaque réponse
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        return $this->respond([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Réponse de création réussie
     */
    protected function created($data = null, string $message = 'Created successfully', int $code = 201)
    {
        // Ajouter les en-têtes CORS à chaque réponse
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        return $this->respond([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Réponse d'erreur de validation
     */
    protected function validationError($errors, string $message = 'Validation failed', int $code = 422)
    {
        // Ajouter les en-têtes CORS à chaque réponse
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        return $this->respond([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    /**
     * Réponse d'erreur non trouvé
     */
    protected function notFound(string $message = 'Resource not found', int $code = 404)
    {
        // Ajouter les en-têtes CORS à chaque réponse
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        return $this->respond([
            'status' => 'error',
            'message' => $message
        ], $code);
    }

    /**
     * Réponse d'erreur non autorisé
     */
    protected function unauthorized(string $message = 'Unauthorized', int $code = 401)
    {
        // Ajouter les en-têtes CORS à chaque réponse
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        return $this->respond([
            'status' => 'error',
            'message' => $message
        ], $code);
    }

    /**
     * Réponse d'erreur interdite
     */
    protected function forbidden(string $message = 'Forbidden', int $code = 403)
    {
        // Ajouter les en-têtes CORS à chaque réponse
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        return $this->respond([
            'status' => 'error',
            'message' => $message
        ], $code);
    }

    /**
     * Réponse d'erreur serveur
     */
    protected function serverError(string $message = 'Internal server error', int $code = 500)
    {
        // Ajouter les en-têtes CORS à chaque réponse
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        
        return $this->respond([
            'status' => 'error',
            'message' => $message
        ], $code);
    }
}
