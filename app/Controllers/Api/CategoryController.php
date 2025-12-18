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
            $categories = $this->normalizeIconPaths($categories);
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

            $category = $this->normalizeIconPath($category);
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
            $subs = $this->normalizeIconPaths($subs);
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

                // Compter le nombre total d'annonces pour cette catégorie (seulement les annonces vérifiées et publiées)
                $totalAdsForCategory = $this->adModel->select('COUNT(*) as total')
                                                    ->join('subcategories', 'subcategories.id = ads.subcategory_id')
                                                    ->where('subcategories.category_id', $category['id'])
                                                    ->where('ads.status !=', 'deleted')
                                                    ->where('ads.status', 'active')
                                                    ->where('ads.moderation_status', 'approved')
                                                    ->where('ads.publication_status', 'published')
                                                    ->first();

                $categoryData = [
                    'id' => $category['id'],
                    'slug' => $category['slug'],
                    'name' => $category['name'],
                    'icon_path' => $this->normalizeIconPathString($category['icon_path']),
                    'is_active' => $category['is_active'],
                    'display_order' => $category['display_order'],
                    'total_ads' => (int) $totalAdsForCategory['total'],
                    'subcategories' => []
                ];

                // Pour chaque sous-catégorie, compter les annonces (seulement les annonces vérifiées et publiées)
                foreach ($subcategories as $subcategory) {
                    $adsCount = $this->adModel->select('COUNT(*) as total')
                                            ->where('subcategory_id', $subcategory['id'])
                                            ->where('status !=', 'deleted')
                                            ->where('status', 'active')
                                            ->where('moderation_status', 'approved')
                                            ->where('publication_status', 'published')
                                            ->first();

                    $subcategoryData = [
                        'id' => $subcategory['id'],
                        'category_id' => $subcategory['category_id'],
                        'slug' => $subcategory['slug'],
                        'name' => $subcategory['name'],
                        'icon_path' => $this->normalizeIconPathString($subcategory['icon_path']),
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

    /**
     * Normalize icon paths from absolute URLs to relative paths
     * Converts: http://localhost:8080/api/uploads/categories/xxx to /uploads/categories/xxx
     */
    private function normalizeIconPathString($iconPath)
    {
        if (empty($iconPath)) {
            return $iconPath;
        }
        // If path contains /uploads/ anywhere, keep only from that segment
        if (strpos($iconPath, '/uploads/') !== false) {
            $pos = strpos($iconPath, '/uploads/');
            return substr($iconPath, $pos);
        }
        // If it starts with http, extract the path after /api
        if (strpos($iconPath, 'http') === 0) {
            if (preg_match('#/api(/uploads/[^/]*(?:/.*?)?)$#', $iconPath, $matches)) {
                return $matches[1];
            }
        }
        // If it already starts with /, return as is
        if (strpos($iconPath, '/') === 0) {
            return $iconPath;
        }
        // Otherwise add /uploads/ prefix if needed
        return '/' . $iconPath;
    }

    /**
     * Normalize icon_path in a single category/subcategory array
     */
    private function normalizeIconPath(&$item)
    {
        if (isset($item['icon_path'])) {
            $item['icon_path'] = $this->normalizeIconPathString($item['icon_path']);
        }
        return $item;
    }

    /**
     * Normalize icon_path in an array of items
     */
    private function normalizeIconPaths(&$items)
    {
        foreach ($items as &$item) {
            $this->normalizeIconPath($item);
        }
        return $items;
    }
}


