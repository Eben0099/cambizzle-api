<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Services\FavoriteService;
use CodeIgniter\API\ResponseTrait;

class FavoriteController extends BaseApiController
{
    use ResponseTrait;
    
    protected FavoriteService $service;

    public function options(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
            ->setStatusCode(204);
    }

    public function __construct()
    {
        $this->service = service('favoriteService');
    }

    public function toggleFavorite(int $adId)
    {
        $user = $this->request->user ?? null;
        if (!$user) { return $this->unauthorized(); }

        try {
            // Vérifie si l'annonce existe
            $ad = model('AdModel')->find($adId);
            if (!$ad) {
                return $this->notFound('Annonce introuvable');
            }
            
            $result = $this->service->toggleFavorite((int)$user['user_id'], $adId);
            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function removeFavorite(int $adId)
    {
        $user = $this->request->user ?? null;
        if (!$user) { return $this->unauthorized(); }

        try {
            // Vérifie si l'annonce existe
            $ad = model('AdModel')->find($adId);
            if (!$ad) {
                return $this->notFound('Annonce introuvable');
            }
            
            $result = $this->service->removeFavorite((int)$user['user_id'], $adId);
            return $this->success(['status' => 'removed']);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function myFavorites()
    {
        $user = $this->request->user ?? null;
        if (!$user) { return $this->unauthorized(); }

        $limit = (int)($this->request->getGet('limit') ?? 10);
        $page = (int)($this->request->getGet('page') ?? 1);
        $sort = $this->request->getGet('sort_by') ?? 'created_at';
        $order = strtoupper($this->request->getGet('sort_order') ?? 'DESC');
        $offset = max(0, ($page - 1) * $limit);

        try {
            $items = $this->service->getFavorites(
                (int)$user['user_id'],
                $limit,
                $offset,
                $sort,
                $order
            );
            $total = $this->service->getCount((int)$user['user_id']);

            return $this->success([
                'items' => $items,
                'page' => $page,
                'limit' => $limit,
                'total' => $total
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function checkFavorite(int $adId)
    {
        $user = $this->request->user ?? null;
        if (!$user) { return $this->unauthorized(); }

        try {
            $isFavorite = $this->service->isFavorite((int)$user['user_id'], $adId);
            return $this->success(['isFavorite' => $isFavorite]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}