<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\CategoryModel;
use App\Models\SubcategoryModel;
use App\Models\FilterModel;
use App\Models\FilterOptionModel;
use App\Models\BrandModel;
use App\Services\UploadService;

class AdminReferentialController extends BaseApiController
{
    protected $categoryModel;
    protected $subcategoryModel;
    protected $filterModel;
    protected $filterOptionModel;
    protected $brandModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
        $this->subcategoryModel = new SubcategoryModel();
        $this->filterModel = new FilterModel();
        $this->filterOptionModel = new FilterOptionModel();
        $this->brandModel = new BrandModel();
    }

    // ============ GESTION DES CATÉGORIES ============

    /**
     * Liste des catégories (avec pagination et recherche)
     */
    public function categories()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 20;
            $page = $this->request->getGet('page') ?? 1;
            $search = $this->request->getGet('search');
            $isActive = $this->request->getGet('is_active');

            $builder = $this->categoryModel;

            if ($search) {
                $builder->groupStart()
                        ->like('name', $search)
                        ->orLike('slug', $search)
                        ->groupEnd();
            }

            if ($isActive !== null) {
                $builder->where('is_active', (bool)$isActive);
            }

            $total = $builder->countAllResults(false);
            $categories = $builder->orderBy('display_order', 'ASC')
                                 ->orderBy('name', 'ASC')
                                 ->paginate($perPage, 'default', $page);

            return $this->success([
                'categories' => $categories,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ], 'Catégories récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Créer une catégorie
     */
    public function createCategory()
    {
        try {
            $contentType = $this->request->getHeaderLine('Content-Type');
            $isJson = stripos($contentType, 'application/json') !== false;
            $data = $isJson ? ($this->request->getJSON(true) ?? []) : $this->request->getPost();

            // Upload d'icône (multipart/form-data)
            $iconFile = $this->request->getFile('icon');
            if ($iconFile && $iconFile->isValid()) {
                $uploadService = new UploadService();
                $uploadResult = $uploadService->upload('icon', 'uploads/categories', false);
                if (!empty($uploadResult['path'])) {
                    $filename = basename($uploadResult['path']);
                    $data['icon_path'] = '/uploads/categories/' . $filename;
                }
            }

            // Validation des données
            $rules = [
                'name' => 'required|min_length[2]|max_length[100]',
                'slug' => 'permit_empty|min_length[2]|max_length[120]|is_unique[categories.slug]',
                'icon_path' => 'permit_empty|max_length[255]',
                'is_active' => 'permit_empty|in_list[0,1]',
                'display_order' => 'permit_empty|integer'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Les valeurs par défaut (is_active, display_order) 
            // sont maintenant gérées automatiquement par le modèle
            // Note: Cette table n'a pas de champs created_at/updated_at

            $id = $this->categoryModel->insert($data);

            if (!$id) {
                return $this->serverError('Erreur lors de la création de la catégorie');
            }

            $category = $this->categoryModel->find($id);
            return $this->success($category, 'Catégorie créée avec succès', 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategory($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la catégorie requis']);
            }

            $category = $this->categoryModel->find($id);
            if (!$category) {
                return $this->notFound('Catégorie non trouvée');
            }

            $contentType = $this->request->getHeaderLine('Content-Type');
            $isJson = stripos($contentType, 'application/json') !== false;
            $data = $isJson ? ($this->request->getJSON(true) ?? []) : $this->request->getPost();

            // Upload d'icône (multipart/form-data) si fournie
            $iconFile = $this->request->getFile('icon');
            if ($iconFile && $iconFile->isValid()) {
                $uploadService = new UploadService();
                $uploadResult = $uploadService->upload('icon', 'uploads/categories', false);
                if (!empty($uploadResult['path'])) {
                    $filename = basename($uploadResult['path']);
                    $data['icon_path'] = '/uploads/categories/' . $filename;
                }
            }

            // Validation dynamique: ne valider que les champs fournis (slug et autres deviennent facultatifs)
            $rules = [];
            if (array_key_exists('name', $data)) {
                $rules['name'] = 'required|min_length[2]|max_length[100]';
            }
            if (array_key_exists('slug', $data)) {
                $rules['slug'] = "permit_empty|min_length[2]|max_length[120]|is_unique[categories.slug,id,{$id}]";
            }
            if (array_key_exists('icon_path', $data)) {
                $rules['icon_path'] = 'permit_empty|max_length[255]';
            }
            if (array_key_exists('is_active', $data)) {
                $rules['is_active'] = 'permit_empty|in_list[0,1]';
            }
            if (array_key_exists('display_order', $data)) {
                $rules['display_order'] = 'permit_empty|integer';
            }

            if (!empty($rules) && !$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Note: Cette table n'a pas de champ updated_at

            $success = $this->categoryModel->update($id, $data);

            if (!$success) {
                return $this->serverError('Erreur lors de la mise à jour de la catégorie');
            }

            $updatedCategory = $this->categoryModel->find($id);
            return $this->success($updatedCategory, 'Catégorie mise à jour avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer une catégorie
     */
    public function deleteCategory($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la catégorie requis']);
            }

            $category = $this->categoryModel->find($id);
            if (!$category) {
                return $this->notFound('Catégorie non trouvée');
            }

            // Vérifier s'il y a des sous-catégories associées
            $subcategoriesCount = $this->subcategoryModel->where('category_id', $id)->countAllResults();
            if ($subcategoriesCount > 0) {
                return $this->validationError(['category' => 'Impossible de supprimer une catégorie contenant des sous-catégories']);
            }

            $success = $this->categoryModel->delete($id);

            if (!$success) {
                return $this->serverError('Erreur lors de la suppression de la catégorie');
            }

            return $this->success(null, 'Catégorie supprimée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ============ GESTION DES SOUS-CATÉGORIES ============

    /**
     * Liste des sous-catégories
     */
    public function subcategories()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 20;
            $page = $this->request->getGet('page') ?? 1;
            $search = $this->request->getGet('search');
            $categoryId = $this->request->getGet('category_id');
            $isActive = $this->request->getGet('is_active');

            $builder = $this->subcategoryModel->select('subcategories.*, categories.name as category_name')
                                             ->join('categories', 'categories.id = subcategories.category_id');

            if ($search) {
                $builder->groupStart()
                        ->like('subcategories.name', $search)
                        ->orLike('subcategories.slug', $search)
                        ->groupEnd();
            }

            if ($categoryId) {
                $builder->where('subcategories.category_id', $categoryId);
            }

            if ($isActive !== null) {
                $builder->where('subcategories.is_active', (bool)$isActive);
            }

            $total = $builder->countAllResults(false);
            $subcategories = $builder->orderBy('subcategories.display_order', 'ASC')
                                    ->orderBy('subcategories.name', 'ASC')
                                    ->paginate($perPage, 'default', $page);

            return $this->success([
                'subcategories' => $subcategories,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ], 'Sous-catégories récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Créer une sous-catégorie
     */
    public function createSubcategory()
    {
        try {
            $contentType = $this->request->getHeaderLine('Content-Type');
            $isJson = stripos($contentType, 'application/json') !== false;
            $data = $isJson ? ($this->request->getJSON(true) ?? []) : $this->request->getPost();

            // Upload d'icône (multipart/form-data)
            $iconFile = $this->request->getFile('icon');
            if ($iconFile && $iconFile->isValid()) {
                $uploadService = new UploadService();
                $uploadResult = $uploadService->upload('icon', 'uploads/subcategories', false);
                if (!empty($uploadResult['path'])) {
                    $filename = basename($uploadResult['path']);
                    $data['icon_path'] = '/uploads/subcategories/' . $filename;
                }
            }

            // Validation des données
            $rules = [
                'category_id' => 'required|integer|is_not_unique[categories.id]',
                'name' => 'required|min_length[2]|max_length[100]',
                'slug' => 'permit_empty|min_length[2]|max_length[120]|is_unique[subcategories.slug]',
                'icon_path' => 'permit_empty|max_length[255]',
                'is_active' => 'permit_empty|in_list[0,1]',
                'display_order' => 'permit_empty|integer'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Les valeurs par défaut (is_active, display_order) 
            // sont maintenant gérées automatiquement par le modèle
            // Note: Cette table n'a pas de champs created_at/updated_at

            $id = $this->subcategoryModel->insert($data);

            if (!$id) {
                return $this->serverError('Erreur lors de la création de la sous-catégorie');
            }

            $subcategory = $this->subcategoryModel->select('subcategories.*, categories.name as category_name')
                                                 ->join('categories', 'categories.id = subcategories.category_id')
                                                 ->find($id);

            return $this->success($subcategory, 'Sous-catégorie créée avec succès', 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Mettre à jour une sous-catégorie
     */
    public function updateSubcategory($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la sous-catégorie requis']);
            }

            $subcategory = $this->subcategoryModel->find($id);
            if (!$subcategory) {
                return $this->notFound('Sous-catégorie non trouvée');
            }

            $contentType = $this->request->getHeaderLine('Content-Type');
            $isJson = stripos($contentType, 'application/json') !== false;
            $data = $isJson ? ($this->request->getJSON(true) ?? []) : $this->request->getPost();

            // Upload d'icône (multipart/form-data) si fournie
            $iconFile = $this->request->getFile('icon');
            if ($iconFile && $iconFile->isValid()) {
                $uploadService = new UploadService();
                $uploadResult = $uploadService->upload('icon', 'uploads/subcategories', false);
                if (!empty($uploadResult['path'])) {
                    $filename = basename($uploadResult['path']);
                    $data['icon_path'] = '/uploads/subcategories/' . $filename;
                }
            }

            // Validation dynamique: ne valider que les champs fournis
            $rules = [];
            if (array_key_exists('category_id', $data)) {
                $rules['category_id'] = 'permit_empty|integer|is_not_unique[categories.id]';
            }
            if (array_key_exists('name', $data)) {
                $rules['name'] = 'required|min_length[2]|max_length[100]';
            }
            if (array_key_exists('slug', $data)) {
                $rules['slug'] = "permit_empty|min_length[2]|max_length[120]|is_unique[subcategories.slug,id,{$id}]";
            }
            if (array_key_exists('icon_path', $data)) {
                $rules['icon_path'] = 'permit_empty|max_length[255]';
            }
            if (array_key_exists('is_active', $data)) {
                $rules['is_active'] = 'permit_empty|in_list[0,1]';
            }
            if (array_key_exists('display_order', $data)) {
                $rules['display_order'] = 'permit_empty|integer';
            }

            if (!empty($rules) && !$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Note: Cette table n'a pas de champ updated_at

            $success = $this->subcategoryModel->update($id, $data);

            if (!$success) {
                return $this->serverError('Erreur lors de la mise à jour de la sous-catégorie');
            }

            $updatedSubcategory = $this->subcategoryModel->select('subcategories.*, categories.name as category_name')
                                                        ->join('categories', 'categories.id = subcategories.category_id')
                                                        ->find($id);

            return $this->success($updatedSubcategory, 'Sous-catégorie mise à jour avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer une sous-catégorie
     */
    public function deleteSubcategory($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la sous-catégorie requis']);
            }

            $subcategory = $this->subcategoryModel->find($id);
            if (!$subcategory) {
                return $this->notFound('Sous-catégorie non trouvée');
            }

            // Vérifier s'il y a des annonces associées
            $adsCount = model('App\Models\AdModel')->where('subcategory_id', $id)->countAllResults();
            if ($adsCount > 0) {
                return $this->validationError(['subcategory' => 'Impossible de supprimer une sous-catégorie contenant des annonces']);
            }

            $success = $this->subcategoryModel->delete($id);

            if (!$success) {
                return $this->serverError('Erreur lors de la suppression de la sous-catégorie');
            }

            return $this->success(null, 'Sous-catégorie supprimée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ============ GESTION DES FILTRES ============

    /**
     * Liste de tous les filtres de toutes les sous-catégories
     */
    public function allFilters()
    {
        try {
            // Récupérer toutes les sous-catégories avec leurs filtres
            $subcategories = $this->subcategoryModel->select('subcategories.*, categories.name as category_name')
                                                  ->join('categories', 'categories.id = subcategories.category_id')
                                                  ->where('subcategories.is_active', 1)
                                                  ->where('categories.is_active', 1)
                                                  ->orderBy('categories.display_order', 'ASC')
                                                  ->orderBy('subcategories.display_order', 'ASC')
                                                  ->findAll();

            $result = [];
            foreach ($subcategories as $subcategory) {
                // Récupérer les filtres pour cette sous-catégorie
                $filters = $this->filterModel->where('subcategory_id', $subcategory['id'])
                                            ->where('is_active', 1)
                                            ->orderBy('display_order', 'ASC')
                                            ->findAll();

                // Récupérer les options pour chaque filtre
                foreach ($filters as &$filter) {
                    $filter['options'] = $this->filterOptionModel->where('filter_id', $filter['id'])
                                                               ->where('is_active', 1)
                                                               ->orderBy('display_order', 'ASC')
                                                               ->findAll();
                }

                if (!empty($filters)) {
                    $result[] = [
                        'subcategory' => [
                            'id' => $subcategory['id'],
                            'name' => $subcategory['name'],
                            'slug' => $subcategory['slug'],
                            'category_name' => $subcategory['category_name']
                        ],
                        'filters' => $filters
                    ];
                }
            }

            return $this->success($result, 'Tous les filtres récupérés avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Liste des filtres par sous-catégorie
     */
    public function filters($subcategoryId = null)
    {
        try {
            if (!$subcategoryId) {
                return $this->validationError(['subcategory_id' => 'ID de la sous-catégorie requis']);
            }

            $filters = $this->filterModel->where('subcategory_id', $subcategoryId)
                                        ->where('is_active', 1)
                                        ->orderBy('display_order', 'ASC')
                                        ->findAll();

            // Récupérer les options pour chaque filtre
            foreach ($filters as &$filter) {
                $filter['options'] = $this->filterOptionModel->where('filter_id', $filter['id'])
                                                           ->where('is_active', 1)
                                                           ->orderBy('display_order', 'ASC')
                                                           ->findAll();
            }

            return $this->success($filters, 'Filtres récupérés avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Créer un filtre
     */
    public function createFilter()
    {
        try {
            $data = $this->request->getJSON(true);

            // Séparer les options du reste des données
            $options = isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
            unset($data['options']);

            // Validation des données du filtre
            $rules = [
                'subcategory_id' => 'required|integer|is_not_unique[subcategories.id]',
                'name' => 'required|min_length[2]|max_length[100]',
                'type' => 'required|in_list[text,select,multiselect,number,boolean,date]',
                'is_required' => 'permit_empty|in_list[0,1]',
                'display_order' => 'permit_empty|integer',
                'is_active' => 'permit_empty|in_list[0,1]'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Création du filtre
            $filterId = $this->filterModel->insert($data);

            if (!$filterId) {
                return $this->serverError('Erreur lors de la création du filtre');
            }

            // Création des options associées si présentes
            $createdOptions = [];
            foreach ($options as $option) {
                // Préparer les données de l'option
                $optionData = [
                    'filter_id' => $filterId,
                    'value' => isset($option['value']) ? $option['value'] : null,
                    'display_order' => isset($option['display_order']) ? $option['display_order'] : null,
                    'is_active' => 1
                ];
                // Validation simple (on peut améliorer si besoin)
                if (!empty($optionData['value'])) {
                    $optionId = $this->filterOptionModel->insert($optionData);
                    if ($optionId) {
                        $createdOptions[] = $this->filterOptionModel->find($optionId);
                    }
                }
            }

            // Retourner le filtre avec ses options
            $filter = $this->filterModel->find($filterId);
            $filter['options'] = $createdOptions;
            return $this->success($filter, 'Filtre et options créés avec succès', 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Mettre à jour un filtre
     */
    public function updateFilter($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du filtre requis']);
            }

            $filter = $this->filterModel->find($id);
            if (!$filter) {
                return $this->notFound('Filtre non trouvé');
            }

            $data = $this->request->getJSON(true);

            // Séparer les options du reste des données
            $options = isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
            unset($data['options']);

            // Validation des données du filtre
            $rules = [
                'subcategory_id' => 'required|integer|is_not_unique[subcategories.id]',
                'name' => 'required|min_length[2]|max_length[100]',
                'type' => 'required|in_list[text,select,multiselect,number,boolean,date]',
                'is_required' => 'permit_empty|in_list[0,1]',
                'display_order' => 'permit_empty|integer',
                'is_active' => 'permit_empty|in_list[0,1]'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Mise à jour du filtre
            $success = $this->filterModel->update($id, $data);

            if (!$success) {
                return $this->serverError('Erreur lors de la mise à jour du filtre');
            }

            // Mise à jour des options associées
            $updatedOptions = [];
            if (is_array($options)) {
                foreach ($options as $option) {
                    // Si l'option a un id, on la met à jour, sinon on la crée
                    if (isset($option['id']) && $option['id']) {
                        $optionData = [
                            'value' => isset($option['value']) ? $option['value'] : null,
                            'display_order' => isset($option['display_order']) ? $option['display_order'] : null,
                            'is_active' => isset($option['is_active']) ? $option['is_active'] : 1
                        ];
                        $this->filterOptionModel->update($option['id'], $optionData);
                        $updatedOptions[] = $this->filterOptionModel->find($option['id']);
                    } else {
                        $optionData = [
                            'filter_id' => $id,
                            'value' => isset($option['value']) ? $option['value'] : null,
                            'display_order' => isset($option['display_order']) ? $option['display_order'] : null,
                            'is_active' => 1
                        ];
                        if (!empty($optionData['value'])) {
                            $newId = $this->filterOptionModel->insert($optionData);
                            if ($newId) {
                                $updatedOptions[] = $this->filterOptionModel->find($newId);
                            }
                        }
                    }
                }
            }

            // Retourner le filtre avec ses options
            $updatedFilter = $this->filterModel->find($id);
            $updatedFilter['options'] = $updatedOptions;
            return $this->success($updatedFilter, 'Filtre et options mis à jour avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer un filtre
     */
    public function deleteFilter($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du filtre requis']);
            }

            $filter = $this->filterModel->find($id);
            if (!$filter) {
                return $this->notFound('Filtre non trouvé');
            }

            // Supprimer d'abord les options
            $this->filterOptionModel->where('filter_id', $id)->delete();

            $success = $this->filterModel->delete($id);

            if (!$success) {
                return $this->serverError('Erreur lors de la suppression du filtre');
            }

            return $this->success(null, 'Filtre supprimé avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ============ GESTION DES MARQUES ============

    /**
     * Liste des marques par sous-catégorie
     */
    public function brands()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 20;
            $page = $this->request->getGet('page') ?? 1;
            $search = $this->request->getGet('search');
            $subcategoryId = $this->request->getGet('subcategory_id');
            $isActive = $this->request->getGet('is_active');

            $builder = $this->brandModel->select('brands.*, subcategories.name as subcategory_name')
                                       ->join('subcategories', 'subcategories.id = brands.subcategory_id', 'left');

            if ($search) {
                $builder->groupStart()
                        ->like('brands.name', $search)
                        ->orLike('brands.description', $search)
                        ->groupEnd();
            }

            if ($subcategoryId) {
                $builder->where('brands.subcategory_id', $subcategoryId);
            }

            if ($isActive !== null) {
                $builder->where('brands.is_active', (bool)$isActive);
            }

            $total = $builder->countAllResults(false);
            $brands = $builder->orderBy('brands.name', 'ASC')
                             ->paginate($perPage, 'default', $page);

            return $this->success([
                'brands' => $brands,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ], 'Marques récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Créer une marque
     */
    public function createBrand()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validation des données
            $rules = [
                'subcategory_id' => 'required|integer|is_not_unique[subcategories.id]',
                'name' => 'required|min_length[2]|max_length[100]',
                'description' => 'permit_empty|max_length[500]',
                'logo_url' => 'permit_empty|max_length[500]',
                'is_active' => 'permit_empty|in_list[0,1]'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Les valeurs par défaut (is_active, created_at et updated_at) 
            // sont maintenant gérées automatiquement par le modèle

            $id = $this->brandModel->insert($data);

            if (!$id) {
                return $this->serverError('Erreur lors de la création de la marque');
            }

            $brand = $this->brandModel->select('brands.*, subcategories.name as subcategory_name')
                                     ->join('subcategories', 'subcategories.id = brands.subcategory_id')
                                     ->find($id);

            return $this->success($brand, 'Marque créée avec succès', 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Mettre à jour une marque
     */
    public function updateBrand($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la marque requis']);
            }

            $brand = $this->brandModel->find($id);
            if (!$brand) {
                return $this->notFound('Marque non trouvée');
            }

            $data = $this->request->getJSON(true);

            // Validation
            $rules = [
                'subcategory_id' => 'required|integer|is_not_unique[subcategories.id]',
                'name' => 'required|min_length[2]|max_length[100]',
                'description' => 'permit_empty|max_length[500]',
                'logo_url' => 'permit_empty|max_length[500]',
                'is_active' => 'permit_empty|in_list[0,1]'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // updated_at est maintenant géré automatiquement par le modèle

            $success = $this->brandModel->update($id, $data);

            if (!$success) {
                return $this->serverError('Erreur lors de la mise à jour de la marque');
            }

            $updatedBrand = $this->brandModel->select('brands.*, subcategories.name as subcategory_name')
                                            ->join('subcategories', 'subcategories.id = brands.subcategory_id')
                                            ->find($id);

            return $this->success($updatedBrand, 'Marque mise à jour avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer une marque
     */
    public function deleteBrand($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la marque requis']);
            }

            $brand = $this->brandModel->find($id);
            if (!$brand) {
                return $this->notFound('Marque non trouvée');
            }

            $success = $this->brandModel->delete($id);

            if (!$success) {
                return $this->serverError('Erreur lors de la suppression de la marque');
            }

            return $this->success(null, 'Marque supprimée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ============ GESTION DES CODES DE PARRAINAGE ============

    /**
     * GET /api/admin/referral-codes - Liste des codes avec stats et filleuls
     */
    public function listReferralCodes()
    {
        $db = \Config\Database::connect();
        $codes = $db->table('referral_codes')
            ->select('referral_codes.id, referral_codes.code, referral_codes.current_uses, referral_codes.is_active, users.first_name, users.last_name, users.email, users.phone')
            ->join('users', 'users.id_user = referral_codes.user_id')
            ->get()->getResultArray();

        foreach ($codes as &$code) {
            $filleuls = $db->table('referral_uses')
                ->select('users.id_user, users.first_name, users.last_name, users.email, users.phone, referral_uses.used_at')
                ->join('users', 'users.id_user = referral_uses.referred_user_id')
                ->where('referral_uses.referral_code_id', $code['id'])
                ->get()->getResultArray();
            $code['filleuls'] = $filleuls;
        }

        return $this->respond($codes);
    }

    /**
     * GET /api/admin/referral-codes/{id}/filleuls
     * Liste des filleuls pour un code
     */
    public function referralCodeFilleuls($id)
    {
        $db = \Config\Database::connect();
        $filleuls = $db->table('referral_uses')
            ->select('users.id_user, users.first_name, users.last_name, users.email, users.phone, referral_uses.used_at')
            ->join('users', 'users.id_user = referral_uses.referred_user_id')
            ->where('referral_uses.referral_code_id', $id)
            ->get()->getResultArray();
        return $this->respond($filleuls);
    }

    /**
     * POST /api/admin/referral-codes/{id}/activate
     * Active un code de parrainage
     */
    public function activateReferralCode($id)
    {
        $db = \Config\Database::connect();
        $db->table('referral_codes')->update(['is_active' => 1], ['id' => $id]);
        return $this->respond(['success' => true]);
    }

    /**
     * POST /api/admin/referral-codes/{id}/deactivate
     * Désactive un code de parrainage
     */
    public function deactivateReferralCode($id)
    {
        $db = \Config\Database::connect();
        $db->table('referral_codes')->update(['is_active' => 0], ['id' => $id]);
        return $this->respond(['success' => true]);
    }
}
