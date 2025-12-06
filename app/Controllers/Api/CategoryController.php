<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\CategoryModel;
use App\Models\SubcategoryModel;
use App\Models\AdModel;

class CategoryController extends BaseApiController
{
    protected $categoryModel;
    protected $subcategoryModel;
    protected $adModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
        $this->subcategoryModel = new SubcategoryModel();
        $this->adModel = new AdModel();
    }

    public function index()
    {
        try {
            $categories = $this->categoryModel->where('is_active', true)->orderBy('display_order', 'ASC')->findAll();
            return $this->success($categories, 'Catégories récupérées avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la catégorie requis']);
            }

            $category = $this->categoryModel->find($id);
            if (!$category) {
                return $this->notFound('Catégorie non trouvée');
            }

            return $this->success($category, 'Catégorie récupérée avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function getSubcategories($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la catégorie requis']);
            }

            $subs = $this->subcategoryModel->where('category_id', $id)->where('is_active', true)->orderBy('display_order', 'ASC')->findAll();
            return $this->success($subs, 'Sous-catégories récupérées avec succès');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/categories/stats - Récupérer les catégories et sous-catégories avec le nombre d'annonces
     */
    public function getCategoriesWithStats()
    {
        try {
            // Récupération des catégories actives
            $categories = $this->categoryModel->where('is_active', true)
                                            ->orderBy('display_order', 'ASC')
                                            ->findAll();

            $result = [];

            foreach ($categories as $category) {
                // Récupération des sous-catégories pour cette catégorie
                $subcategories = $this->subcategoryModel->where('category_id', $category['id'])
                                                       ->where('is_active', true)
                                                       ->orderBy('display_order', 'ASC')
                                                       ->findAll();

                // Compter le nombre total d'annonces pour cette catégorie
                $totalAdsForCategory = $this->adModel->select('COUNT(*) as total')
                                                    ->join('subcategories', 'subcategories.id = ads.subcategory_id')
                                                    ->where('subcategories.category_id', $category['id'])
                                                    ->where('ads.status !=', 'deleted')
                                                    ->where('ads.status', 'active')
                                                    ->first();

                $categoryData = [
                    'id' => $category['id'],
                    'slug' => $category['slug'],
                    'name' => $category['name'],
                    'icon_path' => $category['icon_path'],
                    'is_active' => $category['is_active'],
                    'display_order' => $category['display_order'],
                    'total_ads' => (int) $totalAdsForCategory['total'],
                    'subcategories' => []
                ];

                // Pour chaque sous-catégorie, compter les annonces
                foreach ($subcategories as $subcategory) {
                    $adsCount = $this->adModel->select('COUNT(*) as total')
                                            ->where('subcategory_id', $subcategory['id'])
                                            ->where('status !=', 'deleted')
                                            ->where('status', 'active')
                                            ->first();

                    $subcategoryData = [
                        'id' => $subcategory['id'],
                        'category_id' => $subcategory['category_id'],
                        'slug' => $subcategory['slug'],
                        'name' => $subcategory['name'],
                        'icon_path' => $subcategory['icon_path'],
                        'is_active' => $subcategory['is_active'],
                        'display_order' => $subcategory['display_order'],
                        'total_ads' => (int) $adsCount['total']
                    ];

                    $categoryData['subcategories'][] = $subcategoryData;
                }

                $result[] = $categoryData;
            }

            return $this->success($result, 'Catégories avec statistiques récupérées avec succès');

        } catch (\Exception $e) {
            log_message('error', '[API] CategoryController getCategoriesWithStats: ' . $e->getMessage());
            return $this->serverError('Une erreur est survenue lors de la récupération des catégories avec statistiques.');
        }
    }
}


