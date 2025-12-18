<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\BrandModel;

class BrandController extends BaseApiController
{
    protected $brandModel;

    public function __construct()
    {
        $this->brandModel = new BrandModel();
    }

    /**
     * GET /api/brands - Liste des marques
     */
    public function index()
    {
        try {
            $subcategoryId = $this->request->getGet('subcategory_id');
            $search = $this->request->getGet('search');

            if ($subcategoryId) {
                $brands = $this->brandModel->getBySubcategory($subcategoryId);
            } elseif ($search) {
                $brands = $this->brandModel->search($search);
            } else {
                $brands = $this->brandModel->where('is_active', true)->findAll();
            }

            return $this->success($brands, 'Marques récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/brands/{id} - Détails d'une marque
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la marque requis']);
            }

            $brand = $this->brandModel->find($id);

            if (!$brand) {
                return $this->notFound('Marque non trouvée');
            }

            return $this->success($brand, 'Marque récupérée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/brands - Créer une marque (Admin)
     */
    public function create()
    {
        try {
            // TODO: Vérifier les permissions admin
            $data = $this->request->getJSON(true);

            if ($this->brandModel->insert($data) === false) {
                $errors = $this->brandModel->errors();
                return $this->validationError($errors);
            }

            $brandId = $this->brandModel->getInsertID();
            $brand = $this->brandModel->find($brandId);

            return $this->created($brand, 'Marque créée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/brands/{id} - Mettre à jour une marque (Admin)
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la marque requis']);
            }

            // TODO: Vérifier les permissions admin
            $data = $this->request->getJSON(true);

            if ($this->brandModel->update($id, $data) === false) {
                $errors = $this->brandModel->errors();
                return $this->validationError($errors);
            }

            $brand = $this->brandModel->find($id);

            return $this->success($brand, 'Marque mise à jour avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/brands/{id} - Supprimer une marque (Admin)
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la marque requis']);
            }

            // TODO: Vérifier les permissions admin
            if ($this->brandModel->delete($id) === false) {
                return $this->serverError('Échec de la suppression');
            }

            return $this->success(null, 'Marque supprimée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
