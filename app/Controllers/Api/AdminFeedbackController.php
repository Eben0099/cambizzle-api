<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;

class AdminFeedbackController extends BaseApiController
{
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, PUT, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setStatusCode(204);
    }

    public function index()
    {
        $status = $this->request->getGet('status');
        $adId = $this->request->getGet('ad_id');
        $userId = $this->request->getGet('user_id');
        $minR = (int)($this->request->getGet('min_rating') ?? 0);
        $maxR = (int)($this->request->getGet('max_rating') ?? 5);
        $limit = (int)($this->request->getGet('limit') ?? 20);
        $page = (int)($this->request->getGet('page') ?? 1);
        $offset = max(0, ($page - 1) * $limit);

        $qb = (new \App\Models\AdFeedbackModel())->builder();
        if ($status) { $qb->where('status', $status); }
        if ($adId) { $qb->where('ad_id', (int)$adId); }
        if ($userId) { $qb->where('author_user_id', (int)$userId); }
        $qb->where('rating >=', $minR)->where('rating <=', $maxR)
           ->orderBy('created_at', 'DESC');
        $rows = $qb->get($limit, $offset)->getResultArray();
        return $this->success(['items' => $rows, 'page' => $page, 'limit' => $limit]);
    }

    public function pending()
    {
        $limit = (int)($this->request->getGet('limit') ?? 20);
        $page = (int)($this->request->getGet('page') ?? 1);
        $offset = max(0, ($page - 1) * $limit);
        $rows = (new \App\Models\AdFeedbackModel())
            ->where('status', 'pending')
            ->orderBy('created_at', 'ASC')
            ->findAll($limit, $offset);
        return $this->success(['items' => $rows, 'page' => $page, 'limit' => $limit]);
    }

    public function approve(int $id)
    {
        $admin = $this->request->user ?? null;
        if (!$admin) { return $this->unauthorized(); }
        try {
            $updated = service('feedbackService')->approve($id, (int)$admin['user_id']);
            return $this->success($updated, 'Feedback approuvé');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function reject(int $id)
    {
        $admin = $this->request->user ?? null;
        if (!$admin) { return $this->unauthorized(); }
        $data = $this->request->getJSON(true);
        $notes = $data['admin_notes'] ?? null;
        try {
            $updated = service('feedbackService')->reject($id, (int)$admin['user_id'], $notes);
            return $this->success($updated, 'Feedback rejeté');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}


