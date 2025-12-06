<?php

namespace App\Models;

use CodeIgniter\Model;

class AdFavoriteModel extends Model
{
    protected $table = 'ad_favorites';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'ad_id'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    // Pour joindre les infos des annonces
    public function getWithAdDetails(int $userId, array $options = [])
    {
        $limit = $options['limit'] ?? 10;
        $offset = $options['offset'] ?? 0;
        $sort = $options['sort'] ?? 'created_at';
        $order = $options['order'] ?? 'DESC';

        return $this->select('ad_favorites.*, ads.*')
            ->join('ads', 'ads.id = ad_favorites.ad_id')
            ->where('ad_favorites.user_id', $userId)
            ->orderBy("ad_favorites.$sort", $order)
            ->findAll($limit, $offset);
    }

    public function getCount(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }
}