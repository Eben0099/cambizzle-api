<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Services\FeedbackService;

class FeedbackController extends BaseApiController
{
    protected FeedbackService $service;

    public function __construct()
    {
        $this->service = service('feedbackService');
    }

    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setStatusCode(204);
    }

    public function listApprovedByAd(int $adId)
    {
        $limit = (int)($this->request->getGet('limit') ?? 10);
        $page = (int)($this->request->getGet('page') ?? 1);
        $sort = $this->request->getGet('sort_by') ?? 'created_at';
        $order = strtoupper($this->request->getGet('sort_order') ?? 'DESC');
        $offset = max(0, ($page - 1) * $limit);
        $items = $this->service->getApprovedByAd($adId, $limit, $offset, $sort, $order);
        return $this->success(['items' => $items, 'page' => $page, 'limit' => $limit]);
    }

    public function summaryByAd(int $adId)
    {
        return $this->success($this->service->getSummaryByAd($adId));
    }

    public function createForAd(int $adId)
    {
        $user = $this->request->user ?? null;
        if (!$user) { return $this->unauthorized(); }

        $photos = null;
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (is_string($contentType) && stripos($contentType, 'multipart/form-data') !== false) {
            // FormData: champs texte + fichiers
            $rating = (int)($this->request->getPost('rating') ?? 0);
            $content = trim((string)($this->request->getPost('content') ?? ''));

            // Upload via UploadService (photos[])
            $uploadService = service('uploadService');
            $result = $uploadService->uploadMultiple('photos', 'public/uploads/feedbacks', true, false);
            if (!empty($result['files'])) {
                $photos = array_map(fn($f) => $f['public_path'], $result['files']);
            }
        } else {
            // JSON
            $data = $this->request->getJSON(true);
            $rating = (int)($data['rating'] ?? 0);
            $content = trim((string)($data['content'] ?? ''));
            $photos = isset($data['photos']) && is_array($data['photos']) ? $data['photos'] : null;
        }

        $ad = model('AdModel')->find($adId);
        if (!$ad) { return $this->notFound('Annonce introuvable'); }
        if ((int)$ad['user_id'] === (int)$user['user_id']) {
            return $this->forbidden('Vous ne pouvez pas évaluer votre propre annonce');
        }

        try {
            $created = $this->service->create((int)$user['user_id'], $adId, $rating, $content, $photos);
            return $this->created($created, 'Feedback créé (en attente de validation)');
        } catch (\InvalidArgumentException $e) {
            return $this->validationError(['fields' => $e->getMessage()]);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'déjà laissé')) {
                return $this->validationError(['unique' => $e->getMessage()]);
            }
            return $this->serverError($e->getMessage());
        }
    }

    public function myFeedbacks()
    {
        $user = $this->request->user ?? null;
        if (!$user) { return $this->unauthorized(); }
        $limit = (int)($this->request->getGet('limit') ?? 10);
        $page = (int)($this->request->getGet('page') ?? 1);
        $offset = max(0, ($page - 1) * $limit);
        $items = (new \App\Models\AdFeedbackModel())
            ->where('author_user_id', (int)$user['user_id'])
            ->orderBy('created_at', 'DESC')
            ->findAll($limit, $offset);
        return $this->success(['items' => $items, 'page' => $page, 'limit' => $limit]);
    }

    public function deleteMine(int $id)
    {
        $user = $this->request->user ?? null;
        if (!$user) { return $this->unauthorized(); }
        try {
            $ok = $this->service->deletePendingByAuthor($id, (int)$user['user_id']);
            return $ok ? $this->success(null, 'Feedback supprimé') : $this->serverError('Suppression échouée');
        } catch (\Throwable $e) {
            return $this->forbidden($e->getMessage());
        }
    }
}


