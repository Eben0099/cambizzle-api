<?php

namespace App\Services;

use App\Models\AdFeedbackModel;

class FeedbackService
{
    public function __construct(private AdFeedbackModel $model)
    {
    }

    public function create(int $userId, int $adId, int $rating, string $content, ?array $photos = null): array
    {
        $existing = $this->model->where(['ad_id' => $adId, 'author_user_id' => $userId])->first();
        if ($existing) {
            throw new \RuntimeException('Vous avez déjà laissé un feedback pour cette annonce.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('La note doit être comprise entre 1 et 5.');
        }
        if (mb_strlen(trim($content)) < 5) {
            throw new \InvalidArgumentException('Le commentaire est trop court.');
        }

        $data = [
            'ad_id' => $adId,
            'author_user_id' => $userId,
            'rating' => $rating,
            'content' => $content,
            'photos' => $photos ? json_encode($photos) : null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $id = $this->model->insert($data, true);
        return $this->model->find($id);
    }

    public function getApprovedByAd(int $adId, int $limit = 10, int $offset = 0, ?string $sort = 'created_at', ?string $order = 'DESC'): array
    {
        return $this->model
            ->where(['ad_id' => $adId, 'status' => 'approved'])
            ->orderBy($sort ?? 'created_at', strtoupper($order ?? 'DESC'))
            ->findAll($limit, $offset);
    }

    public function getSummaryByAd(int $adId): array
    {
        $db = \Config\Database::connect();
        $row = $db->table('ad_feedbacks')
            ->select('COUNT(*) as total, AVG(rating) as avg')
            ->where(['ad_id' => $adId, 'status' => 'approved'])
            ->get()->getRowArray() ?? ['total' => 0, 'avg' => 0];

        $distribution = $db->table('ad_feedbacks')
            ->select('rating, COUNT(*) c')
            ->where(['ad_id' => $adId, 'status' => 'approved'])
            ->groupBy('rating')->get()->getResultArray();

        $dist = [1=>0,2=>0,3=>0,4=>0,5=>0];
        foreach ($distribution as $d) { $dist[(int)$d['rating']] = (int)$d['c']; }

        return [
            'averageRating' => round((float)($row['avg'] ?? 0), 2),
            'ratingsCount' => (int)($row['total'] ?? 0),
            'distribution' => $dist,
        ];
    }

    public function approve(int $feedbackId, int $adminId): array
    {
        $fb = $this->model->find($feedbackId);
        if (!$fb) { throw new \RuntimeException('Feedback introuvable'); }
        $this->model->update($feedbackId, [
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->recomputeAdStats((int)$fb['ad_id']);
        return $this->model->find($feedbackId);
    }

    public function reject(int $feedbackId, int $adminId, ?string $notes = null): array
    {
        $fb = $this->model->find($feedbackId);
        if (!$fb) { throw new \RuntimeException('Feedback introuvable'); }
        $this->model->update($feedbackId, [
            'status' => 'rejected',
            'admin_notes' => $notes,
            'reviewed_by' => $adminId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->model->find($feedbackId);
    }

    public function deletePendingByAuthor(int $feedbackId, int $authorId): bool
    {
        $fb = $this->model->find($feedbackId);
        if (!$fb || (int)$fb['author_user_id'] !== $authorId || $fb['status'] !== 'pending') {
            throw new \RuntimeException('Suppression non autorisée');
        }
        return (bool)$this->model->delete($feedbackId);
    }

    private function recomputeAdStats(int $adId): void
    {
        $db = \Config\Database::connect();
        $row = $db->table('ad_feedbacks')
            ->select('COUNT(*) as total, AVG(rating) as avg')
            ->where(['ad_id' => $adId, 'status' => 'approved'])
            ->get()->getRowArray() ?? ['total' => 0, 'avg' => 0];
        $db->table('ads')->where('id', $adId)->update([
            'average_rating' => round((float)($row['avg'] ?? 0), 2),
            'ratings_count' => (int)($row['total'] ?? 0),
        ]);
    }
}


