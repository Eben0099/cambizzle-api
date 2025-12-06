<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\LocationModel;

class LocationController extends BaseApiController
{
    protected $locationModel;

    public function __construct()
    {
        $this->locationModel = new LocationModel();
    }

    public function index()
    {
        try {
            $locations = $this->locationModel->findAll();
            return $this->success($locations, 'Localisations récupérées avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la localisation requis']);
            }

            $location = $this->locationModel->find($id);
            if (!$location) {
                return $this->notFound('Localisation non trouvée');
            }

            return $this->success($location, 'Localisation récupérée avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if ($this->locationModel->insert($data) === false) {
                $errors = $this->locationModel->errors();
                return $this->validationError($errors);
            }

            $locationId = $this->locationModel->getInsertID();
            $location = $this->locationModel->find($locationId);

            return $this->created($location, 'Localisation créée avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la localisation requis']);
            }

            $data = $this->request->getJSON(true) ?? [];

            if ($this->locationModel->update($id, $data) === false) {
                $errors = $this->locationModel->errors();
                return $this->validationError($errors);
            }

            $location = $this->locationModel->find($id);

            return $this->success($location, 'Localisation mise à jour avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la localisation requis']);
            }

            if ($this->locationModel->delete($id) === false) {
                return $this->serverError('Échec de la suppression');
            }

            return $this->success(null, 'Localisation supprimée avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}


