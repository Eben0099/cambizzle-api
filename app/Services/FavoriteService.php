<?php

namespace App\Services;

use App\Models\AdFavoriteModel;

class FavoriteService
{
    public function __construct(private AdFavoriteModel $model)
    {
    }

    public function toggleFavorite(int $userId, int $adId): array
    {
        $existing = $this->model->where([
            'user_id' => $userId,
            'ad_id' => $adId
        ])->first();

        if ($existing) {
            return $this->removeFavorite($userId, $adId);
        }

        $this->model->insert([
            'user_id' => $userId,
            'ad_id' => $adId
        ]);
        return ['status' => 'added'];
    }

    public function removeFavorite(int $userId, int $adId): array
    {
        $existing = $this->model->where([
            'user_id' => $userId,
            'ad_id' => $adId
        ])->first();

        if (!$existing) {
            return ['status' => 'not_found'];
        }

        $this->model->where('id', $existing['id'])->delete();
        return ['status' => 'removed'];
    }

    public function getFavorites(int $userId, int $limit = 10, int $offset = 0, string $sort = 'created_at', string $order = 'DESC'): array
    {
        return $this->model->getWithAdDetails($userId, [
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $sort,
            'order' => $order
        ]);
    }

    public function getCount(int $userId): int
    {
        return $this->model->getCount($userId);
    }

    public function isFavorite(int $userId, int $adId): bool
    {
        return $this->model->where([
            'user_id' => $userId,
            'ad_id' => $adId
        ])->countAllResults() > 0;
    }
}