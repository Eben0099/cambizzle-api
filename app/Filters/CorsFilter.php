<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Cors;

class CorsFilter implements FilterInterface
{
    protected $corsConfig;

    public function __construct()
    {
        $this->corsConfig = new Cors();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        // Récupérer l'origine de la requête
        $origin = $request->getHeaderLine('Origin');

        // Gérer la requête OPTIONS (preflight)
        if ($request->getMethod(true) === 'OPTIONS') {
            $response = service('response');

            // Autoriser toutes les origines
            $response->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Methods', implode(', ', $this->corsConfig->default['allowedMethods']))
                ->setHeader('Access-Control-Allow-Headers', '*')
                ->setHeader('Access-Control-Max-Age', $this->corsConfig->default['maxAge']);

            // IMPORTANT : Terminer la requête ici pour OPTIONS
            $response->setStatusCode(204); // No Content
            return $response;
        }

        // Pour toutes les autres requêtes, ajouter les en-têtes CORS de base
        $response = service('response');
        $response->setHeader('Access-Control-Allow-Origin', '*');

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Autoriser toutes les origines
        $response->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', implode(', ', $this->corsConfig->default['allowedMethods']))
            ->setHeader('Access-Control-Allow-Headers', '*');

        // Ajouter les headers exposés si configurés
        if (!empty($this->corsConfig->default['exposedHeaders'])) {
            $response->setHeader('Access-Control-Expose-Headers', '*');
        }

        return $response;
    }
}