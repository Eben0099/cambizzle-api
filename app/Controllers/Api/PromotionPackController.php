<?php
namespace App\Controllers\Api;

use App\Models\PromotionPackModel;
use CodeIgniter\RESTful\ResourceController;

class PromotionPackController extends ResourceController
{
    protected $modelName = 'App\Models\PromotionPackModel';
    protected $format = 'json';

    /**
     * Liste tous les packs de promotion
     */
    public function index()
    {
        $packs = $this->model->where('is_active', 1)->findAll();
        return $this->respond($packs);
    }

    /**
     * Affiche un pack spécifique
     */
    public function show($id = null)
    {
        $pack = $this->model->find($id);
        if (!$pack) {
            return $this->failNotFound('Pack not found');
        }
        return $this->respond($pack);
    }

    /**
     * Crée un nouveau pack (admin uniquement)
     */
    public function create()
    {
        $data = $this->request->getJSON(true);

        $rules = [
            'name' => 'required|min_length[3]',
            'duration_days' => 'required|integer|greater_than[0]',
            'price' => 'required|decimal',
            'description' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        // Générer le slug automatiquement si non fourni
        if (empty($data['slug']) && !empty($data['name'])) {
            $slugService = \App\Services\SlugService::class;
            $data['slug'] = $slugService::generate($data['name']);
        }

        $packId = $this->model->insert($data);
        return $this->respondCreated(['id' => $packId, 'message' => 'Pack created successfully']);
    }

    /**
     * Met à jour un pack (admin uniquement)
     */
    public function update($id = null)
    {
        $pack = $this->model->find($id);
        if (!$pack) {
            return $this->failNotFound('Pack not found');
        }

        $data = $this->request->getJSON(true);
        $this->model->update($id, $data);
        return $this->respond(['message' => 'Pack updated successfully']);
    }

    /**
     * Supprime un pack (admin uniquement)
     */
    public function delete($id = null)
    {
        $pack = $this->model->find($id);
        if (!$pack) {
            return $this->failNotFound('Pack not found');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['message' => 'Pack deleted successfully']);
    }

    /**
     * OPTIONS handler for CORS preflight
     */
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setStatusCode(204);
    }
}
