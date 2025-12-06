<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\FilterModel;
use App\Models\SubcategoryModel;
use CodeIgniter\API\ResponseTrait;

class FilterController extends BaseApiController
{
    use ResponseTrait;

    /**
     * Récupérer les filtres disponibles pour une sous-catégorie spécifique.
     * Inclut également les options de prix et de localisation.
     * GET /api/filters/by-subcategory/{subcategorySlug}
     */
    public function getBySubcategory($subcategorySlug)
    {
        $subcategoryModel = new SubcategoryModel();
        $filterModel = new FilterModel();
        $locationModel = new \App\Models\LocationModel();

        try {
            // 1. Vérifier que la sous-catégorie existe et est active (par slug)
            $subcategory = $subcategoryModel->where('slug', $subcategorySlug)->first();
            if (!$subcategory || !$subcategory['is_active']) {
                return $this->failNotFound('Sous-catégorie non trouvée ou inactive.');
            }

            $subcategoryId = $subcategory['id'];

            // 2. Récupérer les filtres associés à cette sous-catégorie
            $filters = $filterModel
                ->select('filters.id, filters.name, filters.type, filters.is_required, filters.display_order')
                ->where('filters.subcategory_id', $subcategoryId)
                ->where('filters.is_active', 1)
                ->orderBy('filters.display_order', 'ASC')
                ->findAll();

            // 3. Pour chaque filtre de type 'select', 'radio', 'checkbox', récupérer les options
            $filterOptionModel = new \App\Models\FilterOptionModel();
            foreach ($filters as &$filter) {
                if (in_array($filter['type'], ['select', 'radio', 'checkbox'])) {
                    $filter['options'] = $filterOptionModel
                        ->where('filter_id', $filter['id'])
                        ->where('is_active', 1)
                        ->orderBy('display_order', 'ASC')
                        ->findAll();
                } else {
                    $filter['options'] = [];
                }
            }

            // 4. Récupérer toutes les localisations actives pour le filtre de lieu
            $locations = $locationModel
                ->where('is_active', 1)
                ->orderBy('region', 'ASC')
                ->orderBy('city', 'ASC')
                ->findAll();

            // Transformer les locations en format simple pour le frontend
            $locationOptions = array_map(function($location) {
                // LocationEntity retourne des objets, pas des arrays
                return [
                    'id' => $location->id,
                    'name' => $location->city . ', ' . $location->region,
                    'city' => $location->city,
                    'region' => $location->region
                ];
            }, $locations);

            // 5. Construire la réponse avec filtres + métadonnées (prix et lieux)
            $response = [
                'filters' => $filters,
                'metadata' => [
                    'locations' => $locationOptions,
                    'priceRange' => [
                        'min' => 0,
                        'max' => 10000000, // Valeur max par défaut, peut être dynamique
                        'currency' => 'XAF'
                    ]
                ]
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            log_message('error', '[API] FilterController getBySubcategory: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération des filtres.');
        }
    }
}
