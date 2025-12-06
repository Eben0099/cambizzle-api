<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\AdModel;
use App\Models\AdFilterValueModel;
use App\Models\AdPhotoModel;
use App\Models\BrandModel;
use App\Models\CategoryModel;
use App\Models\FilterModel;
use App\Models\FilterOptionModel;
use App\Models\LocationModel;
use App\Models\SubcategoryModel;
use CodeIgniter\API\ResponseTrait;
use App\Services\UploadService;
use Config\Services;
helper(['url', 'slug_helper']);

class AdsController extends BaseApiController
{
    use ResponseTrait;

    protected $userService;
    protected $sellerService;

    public function __construct()
    {
        $this->userService = Services::userService();
        $this->sellerService = Services::sellerService();
    }

    /**
     * Traite un élément de filtre individuel
     */
    private function processFilterItem($filterItem, &$filtersFound, $index)
    {
        log_message('error', '[API] AdsController create: Analyse filterItem ' . $index . ': ' . json_encode($filterItem) . ' (type: ' . gettype($filterItem) . ')');

        if (is_array($filterItem) && isset($filterItem['filter_id']) && isset($filterItem['value'])) {
            $filterId = $filterItem['filter_id'];
            $value = $filterItem['value'];
            log_message('error', '[API] AdsController create: Extraction - filterId: ' . $filterId . ', value: ' . $value);

            if (is_numeric($filterId) && !empty($value)) {
                $filtersFound[$filterId] = $value;
                log_message('error', '[API] AdsController create: ✅ FILTRE TROUVÉ (méthode 4 - filter_values): filter_id=' . $filterId . ', value=' . $value);
            } else {
                log_message('error', '[API] AdsController create: ❌ Filtre rejeté - filterId non numérique ou valeur vide: filterId=' . $filterId . ', value=' . $value);
            }
        } else {
            log_message('error', '[API] AdsController create: ❌ filterItem invalide - manque filter_id ou value: ' . json_encode($filterItem));
        }
    }

    public function getCreationData()
    {
        $locationModel = new LocationModel();
        $categoryModel = new CategoryModel();
        $subcategoryModel = new SubcategoryModel();

        try {
            $locations = $locationModel->select('id, city')
                ->where('is_active', 1)
                ->findAll();

            $categories = $categoryModel->select('id, name, slug')
                ->where('is_active', 1)
                ->orderBy('id', 'ASC')
                ->findAll();

            $subcategories = $subcategoryModel->select('id, category_id, name, slug')
                ->where('is_active', 1)
                ->orderBy('id', 'ASC')
                ->findAll();

            $categoriesWithSubs = [];
            foreach ($categories as $category) {
                $category['subcategories'] = [];
                foreach ($subcategories as $subcategory) {
                    if ($subcategory['category_id'] === $category['id']) {
                        $category['subcategories'][] = $subcategory;
                    }
                }
                $categoriesWithSubs[] = $category;
            }

            $data = [
                'locations'  => $locations,
                'categories' => $categoriesWithSubs,
            ];

            return $this->respond($data);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getCreationData: ' . $e->getMessage());
            return $this->failServerError('Une erreur interne est survenue lors de la récupération des données.');
        }
    }

    public function getSubcategoryFields(string $subcategorySlug)
    {
        $subcategoryModel = new SubcategoryModel();
        $brandModel = new BrandModel();
        $filterModel = new FilterModel();
        $filterOptionModel = new FilterOptionModel();

        try {
            $subcategory = $subcategoryModel->select('id')->where('slug', $subcategorySlug)->where('is_active', 1)->first();
            if (!$subcategory) {
                return $this->failNotFound('Aucune sous-catégorie active trouvée pour ce slug.');
            }
            $subcategoryId = $subcategory['id'];

            $brands = $brandModel->select('id, name')->where('subcategory_id', $subcategoryId)->where('is_active', 1)->findAll();

            $filters = $filterModel->select('id, name, type, is_required')
                ->where('subcategory_id', $subcategoryId)
                ->where('is_active', 1)
                ->orderBy('display_order', 'ASC')
                ->findAll();

            $filterIds = array_column($filters, 'id');

            if (!empty($filterIds)) {
                $allOptions = $filterOptionModel->whereIn('filter_id', $filterIds)
                    ->orderBy('display_order', 'ASC')
                    ->findAll();

                $optionsByFilterId = [];
                foreach ($allOptions as $option) {
                    $optionsByFilterId[$option['filter_id']][] = $option['value'];
                }

                foreach ($filters as &$filter) {
                    if (in_array($filter['type'], ['select', 'radio', 'checkbox'])) {
                        $filter['options'] = $optionsByFilterId[$filter['id']] ?? [];
                    }
                }

    
            }

            $data = [
                'brands' => $brands,
                'filters' => $filters,
            ];

            return $this->respond($data);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getSubcategoryFields Slug: ' . $e->getMessage());
            return $this->failServerError('Une erreur interne est survenue.');
        }
    }

    public function create()
    {
        log_message('error', '[API] AdsController create: ==== DÉBUT CRÉATION ANNONCE ====');
        log_message('error', '[API] AdsController create: Timestamp: ' . date('Y-m-d H:i:s'));

        // 1. DIAGNOSTICS PRÉLIMINAIRES
        log_message('error', '[API] AdsController create: Method: ' . $this->request->getMethod());
        log_message('error', '[API] AdsController create: URI: ' . $this->request->getUri());

        // Récupérer TOUTES les données - POST, JSON, et RAW
        $postData = $this->request->getPost();
        $jsonData = null;
        $rawData = $this->request->getRawInput();

        // Gestion sécurisée du JSON pour éviter les erreurs fatales
        try {
            $jsonData = $this->request->getJSON(true);
            log_message('error', '[API] AdsController create: JSON parsé avec succès');
        } catch (\Exception $e) {
            log_message('error', '[API] AdsController create: ERREUR JSON: ' . $e->getMessage());
            log_message('error', '[API] AdsController create: Contenu brut: ' . substr(is_string($rawData) ? $rawData : json_encode($rawData), 0, 500));

            // Tentative de parsing manuel du JSON si possible
            if (!empty($rawData)) {
                $rawDataString = is_string($rawData) ? $rawData : json_encode($rawData);
                $manualJson = json_decode($rawDataString, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $jsonData = $manualJson;
                    log_message('error', '[API] AdsController create: JSON récupéré manuellement');
                } else {
                    log_message('error', '[API] AdsController create: JSON invalide - ' . json_last_error_msg());
                }
            }
        }

        log_message('error', '[API] AdsController create: POST Data: ' . json_encode($postData));
        log_message('error', '[API] AdsController create: JSON Data: ' . json_encode($jsonData));
        log_message('error', '[API] AdsController create: Raw Input (first 500 chars): ' . substr(is_string($rawData) ? $rawData : json_encode($rawData), 0, 500));

        // 2. Récupérer l'ID de l'utilisateur - CORRECTION COMPLÈTE
        $user = $this->request->user ?? null;
        $userId = null;

        log_message('error', '[API] AdsController create: User brut reçu: ' . print_r($user, true));
        log_message('error', '[API] AdsController create: Type de user: ' . gettype($user));

        if (is_array($user)) {
            if (isset($user['user_id'])) {
                $userId = $user['user_id'];
                log_message('error', '[API] AdsController create: UserId extrait de user_id (array): ' . $userId);
            } elseif (isset($user['id'])) {
                $userId = $user['id'];
                log_message('error', '[API] AdsController create: UserId extrait de id (array): ' . $userId);
            }
        } elseif (is_object($user)) {
            if (isset($user->user_id)) {
                $userId = $user->user_id;
                log_message('error', '[API] AdsController create: UserId extrait de user_id (object): ' . $userId);
            } elseif (isset($user->id)) {
                $userId = $user->id;
                log_message('error', '[API] AdsController create: UserId extrait de id (object): ' . $userId);
            }
        }

        log_message('error', '[API] AdsController create: UserId final: ' . $userId);

        if (!$userId) {
            log_message('error', '[API] AdsController create: Utilisateur non authentifié');
            return $this->failUnauthorized('Utilisateur non authentifié ou identifiant manquant.');
        }

        // 3. ANALYSE DES DONNÉES REÇUES
        $uploadedFiles = $this->request->getFiles();

        // Fusion intelligente des données POST et JSON
        $allData = [];
        if (!empty($postData)) {
            $allData = array_merge($allData, $postData);
            log_message('error', '[API] AdsController create: Données POST intégrées');
        }
        if (!empty($jsonData)) {
            $allData = array_merge($allData, $jsonData);
            log_message('error', '[API] AdsController create: Données JSON intégrées');
        }

        log_message('error', '[API] AdsController create: Données finales utilisées: ' . json_encode($allData));
        log_message('error', '[API] AdsController create: Fichiers reçus: ' . json_encode(array_keys($uploadedFiles)));

        // 4. ANALYSE SPÉCIFIQUE DES FILTRES AVANT VALIDATION
        log_message('error', '[API] AdsController create: ==== ANALYSE DES FILTRES ====');
        log_message('error', '[API] AdsController create: Toutes les clés de données: [' . implode(', ', array_keys($allData)) . ']');

        $potentialFilters = [];
        foreach ($allData as $key => $value) {
            log_message('error', '[API] AdsController create: Analyse clé "' . $key . '" = "' . (is_array($value) ? json_encode($value) : $value) . '" (type: ' . gettype($value) . ')');

            // Détecter tous les types de filtres possibles
            if (strpos($key, 'filter_') === 0) {
                $potentialFilters['method1'][$key] = $value;
            } elseif (is_numeric($key)) {
                $potentialFilters['method3'][$key] = $value;
            } elseif ($key === 'filters' && is_array($value)) {
                $potentialFilters['method2'] = $value;
            }
        }

        log_message('error', '[API] AdsController create: Filtres potentiels détectés: ' . json_encode($potentialFilters));

        // 5. Validation étendue - Inclure tous les champs possibles
        $rules = [
            'title'              => 'required|string|max_length[150]',
            'description'        => 'permit_empty|string|max_length[2000]',
            'price'              => 'required|numeric|greater_than[0]',
            'original_price'     => 'permit_empty|numeric|greater_than[0]',
            'discount_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'has_discount'       => 'permit_empty|in_list[0,1]',
            'is_negotiable'      => 'permit_empty|in_list[0,1]',
            'referral_code'      => 'permit_empty|string|max_length[50]',
            'subcategory_id'     => 'required|integer|greater_than[0]|is_not_unique[subcategories.id]',
            'location_id'        => 'required|integer|greater_than[0]|is_not_unique[locations.id]',
            'brand_id'           => 'permit_empty|integer|is_not_unique[brands.id]',
            'boost_plan_id'      => 'permit_empty|integer|is_not_unique[promotion_packs.id]',
            'phone'              => 'permit_empty|string|max_length[20]',
            'payment_method'     => 'permit_empty|string|max_length[50]',
            'photos'             => 'permit_empty', // Validation séparée pour les fichiers
        ];

        // Validation des fichiers si présents
        if (isset($uploadedFiles['photos'])) {
            $rules['photos'] = 'uploaded[photos]|max_size[photos,2048]|ext_in[photos,png,jpg,jpeg,webp]';
            log_message('error', '[API] AdsController create: Validation des photos ajoutée');
        }

        if (!$this->validate($rules, $allData)) {
            log_message('error', '[API] AdsController create: Erreurs de validation: ' . json_encode($this->validator->getErrors()));
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // 5.1. Vérifier le plan de boost si fourni
        $boostPlan = null;
        $isPaidBoost = false;
        if (!empty($allData['boost_plan_id'])) {
            $promotionPackModel = new \App\Models\PromotionPackModel();
            $boostPlan = $promotionPackModel->find($allData['boost_plan_id']);
            if (!$boostPlan) {
                return $this->failValidationErrors(['boost_plan_id' => 'Plan de boost invalide']);
            }
            $isPaidBoost = ($boostPlan['price'] > 0);
        }

        // 6. Démarrer la transaction
        $db = \Config\Database::connect();
        $db->transStart();
        log_message('error', '[API] AdsController create: Transaction démarrée');

        try {
            // 7. Préparer et insérer l'annonce principale
            // Générer le slug avec le service commun
            $slug = \App\Services\SlugService::generate($allData['title'] ?? '');

            // S'assurer que les valeurs numériques sont bien typées
            $adData = [
                'user_id'             => (int) $userId,
                'title'               => trim($allData['title'] ?? ''),
                'slug'                => $slug,
                'description'         => trim($allData['description'] ?? ''),
                'price'               => (float) ($allData['price'] ?? 0),
                'original_price'      => isset($allData['original_price']) ? (float) $allData['original_price'] : null,
                'discount_percentage' => isset($allData['discount_percentage']) ? (int) $allData['discount_percentage'] : null,
                'has_discount'        => (int) ($allData['has_discount'] ?? 0),
                'is_negotiable'       => (int) ($allData['is_negotiable'] ?? 0),
                'referral_code'       => !empty($allData['referral_code']) ? trim($allData['referral_code']) : null,
                'subcategory_id'      => (int) ($allData['subcategory_id'] ?? 0),
                'location_id'         => (int) ($allData['location_id'] ?? 0),
                'brand_id'            => isset($allData['brand_id']) && !empty($allData['brand_id']) ? (int) $allData['brand_id'] : null,
                'status'              => 'active',  // Valeur par défaut valide selon l'enum
                'moderation_status'   => 'pending',
                'publication_status'  => $isPaidBoost ? 'draft' : 'published',
                'view_count'          => 0
            ];

            log_message('error', '[API] AdsController create: Données annonce à insérer: ' . json_encode($adData));

            $adModel = new \App\Models\AdModel();
            $newAdId = $adModel->insert($adData, true);

            if (!$newAdId) {
                $modelErrors = $adModel->errors();
                $dbError = $db->error();
                log_message('error', '[API] AdsController create: Échec insertion annonce: ' . json_encode($modelErrors) . ' | DB: ' . json_encode($dbError));
                throw new \Exception('La création de l\'annonce a échoué');
            }

            log_message('error', '[API] AdsController create: Annonce créée avec ID: ' . $newAdId);

            // 8. Traiter les photos
            $photosProcessed = 0;
            $adPhotoModel = new \App\Models\AdPhotoModel();

            if (isset($uploadedFiles['photos']) && is_array($uploadedFiles['photos'])) {
                log_message('error', '[API] AdsController create: Traitement des photos - ' . count($uploadedFiles['photos']) . ' fichiers');

                foreach ($uploadedFiles['photos'] as $order => $img) {
                    if ($img && $img->isValid() && !$img->hasMoved()) {
                        // Upload du fichier
                        $uploadService = new UploadService();
                        $fieldName = "photos.{$order}";
                        $uploadResult = $uploadService->upload($fieldName, 'uploads/ads');

                        if ($uploadResult['success']) {
                            // Stocker le chemin relatif au lieu de l'URL complète
                            $filename = basename($uploadResult['path']);
                            $relativePath = '/uploads/ads/' . $filename;

                            $photoData = [
                                'ad_id' => (int) $newAdId,
                                'original_url' => $relativePath,
                                'thumbnail_url' => null,
                                'display_order' => (int) $order,
                                'alt_text' => 'Photo ' . ($order + 1)
                            ];

                            $photoInsertResult = $adPhotoModel->insertPhoto($photoData);
                            if ($photoInsertResult) {
                                $photosProcessed++;
                                log_message('error', '[API] AdsController create: Photo ' . $order . ' insérée avec succès');
                            }
                        }
                    }
                }
            } else {
                log_message('error', '[API] AdsController create: Aucune photo à traiter');
            }

            // 9. TRAITEMENT DES FILTRES AMÉLIORÉ AVEC LOGS DÉTAILLÉS
            log_message('error', '[API] AdsController create: ==== DÉBUT TRAITEMENT FILTRES ====');
            $filtersProcessed = 0;
            $adFilterValueModel = new \App\Models\AdFilterValueModel();

            // Vérifier que $filtersFound n'est pas vide
            if (empty($filtersFound)) {
                log_message('error', '[API] AdsController create: ⚠️ AUCUN FILTRE À TRAITER');
            } else {
                log_message('error', '[API] AdsController create: Nombre de filtres à traiter: ' . count($filtersFound));
            }

            // Vérifier que le modèle FilterModel existe et fonctionne
            try {
                $filterModel = new \App\Models\FilterModel();
                $allFiltersInDb = $filterModel->findAll();
                log_message('error', '[API] AdsController create: Nombre de filtres en BD: ' . count($allFiltersInDb));

                // Afficher les 5 premiers filtres pour debug
                foreach (array_slice($allFiltersInDb, 0, 5) as $filter) {
                    log_message('error', '[API] AdsController create: Filtre BD: ID=' . $filter['id'] . ', name=' . $filter['name'] . ', subcategory=' . $filter['subcategory_id']);
                }
            } catch (\Exception $e) {
                log_message('error', '[API] AdsController create: ERREUR accès FilterModel: ' . $e->getMessage());
            }

            // Analyse détaillée des filtres reçus
            $filtersFound = [];
            log_message('error', '[API] AdsController create: Données reçues pour analyse filtres: ' . json_encode($allData));
        log_message('error', '[API] AdsController create: Sous-catégorie ID: ' . ($allData['subcategory_id'] ?? 'NON DEFINI'));

        // Diagnostic spécifique pour les filtres
        if (isset($allData['filter_values'])) {
            log_message('error', '[API] AdsController create: filter_values détecté - Type: ' . gettype($allData['filter_values']) . ' - Valeur: ' . json_encode($allData['filter_values']));
        } else {
            log_message('error', '[API] AdsController create: ❌ AUCUN filter_values détecté dans allData');
        }

        // Vérifier toutes les clés qui contiennent 'filter'
        $filterKeys = array_filter(array_keys($allData), function($key) {
            return strpos($key, 'filter') !== false;
        });
        if (!empty($filterKeys)) {
            log_message('error', '[API] AdsController create: Clés contenant "filter": ' . json_encode($filterKeys));
            foreach ($filterKeys as $key) {
                log_message('error', '[API] AdsController create: ' . $key . ' = ' . json_encode($allData[$key]));
            }
        }

            // Méthode 1: Préfixe 'filter_'
            foreach ($allData as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value) && $key !== 'filter_values') {
                    $filterId = str_replace('filter_', '', $key);
                    if (is_numeric($filterId)) {
                        $filtersFound[$filterId] = $value;
                        log_message('error', '[API] AdsController create: FILTRE TROUVÉ (méthode 1): filter_' . $filterId . ' = ' . $value);
                    }
                }
            }

            // Méthode 2: Tableau 'filters'
            if (isset($allData['filters']) && is_array($allData['filters'])) {
                log_message('error', '[API] AdsController create: Tableau filters détecté: ' . json_encode($allData['filters']));
                foreach ($allData['filters'] as $filterId => $value) {
                    if (is_numeric($filterId) && !empty($value)) {
                        // Gérer les valeurs de type tableau (valeurs multiples)
                        if (is_array($value)) {
                            // Joindre les valeurs avec des virgules pour rester cohérent avec l'insertion DB
                            $processedValue = implode(',', array_map('trim', $value));
                            $filtersFound[$filterId] = $processedValue;
                            log_message('error', '[API] AdsController create: FILTRE TROUVÉ (méthode 2, array): filters[' . $filterId . '] = ' . json_encode($value) . ' → ' . $processedValue);
                        } else {
                            $filtersFound[$filterId] = $value;
                            log_message('error', '[API] AdsController create: FILTRE TROUVÉ (méthode 2): filters[' . $filterId . '] = ' . $value);
                        }
                    }
                }
            }

            // Méthode 3: IDs numériques directs
            foreach ($allData as $key => $value) {
                if (is_numeric($key) && !empty($value) && !isset($filtersFound[$key])) {
                    log_message('error', '[API] AdsController create: Test ID numérique: ' . $key . ' = ' . $value);

                    try {
                        $filterModel = new \App\Models\FilterModel();
                        $filter = $filterModel->find($key);
                        if ($filter) {
                            $filtersFound[$key] = $value;
                            log_message('error', '[API] AdsController create: FILTRE TROUVÉ (méthode 3): ' . $key . ' = ' . $value . ' (Filtre: ' . $filter['name'] . ')');
                        } else {
                            log_message('error', '[API] AdsController create: ID numérique ' . $key . ' ne correspond à aucun filtre en BD');
                        }
                    } catch (\Exception $e) {
                        log_message('error', '[API] AdsController create: Erreur vérification filtre ' . $key . ': ' . $e->getMessage());
                    }
                }
            }

            // Méthode 4: Format filter_values du frontend (CORRECTION AMÉLIORÉE)
            if (isset($allData['filter_values'])) {
                $filterValuesData = $allData['filter_values'];
                log_message('error', '[API] AdsController create: filter_values présent - Type: ' . gettype($filterValuesData) . ' - Valeur: ' . json_encode($filterValuesData));

                // Cas 1: C'est déjà un tableau (format normal)
                if (is_array($filterValuesData)) {
                    log_message('error', '[API] AdsController create: Traitement comme tableau');
                    foreach ($filterValuesData as $index => $filterItem) {
                        $this->processFilterItem($filterItem, $filtersFound, $index);
                    }
                }
                // Cas 2: C'est une chaîne JSON (peut-être plusieurs objets séparés)
                elseif (is_string($filterValuesData)) {
                    log_message('error', '[API] AdsController create: filter_values est une chaîne, tentative de parsing');

                    // Essayer de parser comme JSON d'abord
                    $parsed = json_decode($filterValuesData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        log_message('error', '[API] AdsController create: JSON parsé avec succès: ' . json_encode($parsed));
                        if (is_array($parsed)) {
                            foreach ($parsed as $index => $filterItem) {
                                $this->processFilterItem($filterItem, $filtersFound, $index);
                            }
                        } else {
                            // Si c'est un objet unique, le traiter comme un seul filtre
                            $this->processFilterItem($parsed, $filtersFound, 0);
                        }
                    } else {
                        log_message('error', '[API] AdsController create: Échec parsing JSON: ' . json_last_error_msg());
                        // Essayer de splitter sur les virgules si c'est plusieurs objets JSON
                        if (strpos($filterValuesData, '},{') !== false) {
                            log_message('error', '[API] AdsController create: Tentative de split sur },{');
                            $items = explode('},{', $filterValuesData);
                            $totalItems = count($items);

                            foreach ($items as $index => $item) {
                                // Les éléments après explode ont déjà les accolades partielles
                                // On doit juste s'assurer qu'ils sont complets
                                if ($index === 0 && $totalItems > 1) {
                                    // Premier élément : déjà commencé par {, ajouter la fermeture
                                    $item .= '}';
                                } elseif ($index === $totalItems - 1 && $totalItems > 1) {
                                    // Dernier élément : déjà terminé par }, ajouter l'ouverture
                                    $item = '{' . $item;
                                } elseif ($totalItems > 1) {
                                    // Éléments du milieu : ajouter ouverture et fermeture
                                    $item = '{' . $item . '}';
                                }

                                log_message('error', '[API] AdsController create: Item reconstruit: ' . $item);
                                $parsedItem = json_decode($item, true);

                                if ($parsedItem) {
                                    $this->processFilterItem($parsedItem, $filtersFound, $index);
                                } else {
                                    log_message('error', '[API] AdsController create: Échec parsing de l\'item: ' . $item);
                                }
                            }
                        }
                    }
                }
                // Cas 3: C'est un objet unique (traiter comme un seul filtre)
                elseif (is_array($filterValuesData) === false && is_object($filterValuesData)) {
                    log_message('error', '[API] AdsController create: filter_values est un objet, traitement comme filtre unique');
                    $this->processFilterItem((array)$filterValuesData, $filtersFound, 0);
                }
                else {
                    log_message('error', '[API] AdsController create: Format filter_values non reconnu: ' . gettype($filterValuesData));
                }
            } else {
                log_message('error', '[API] AdsController create: Aucun filter_values détecté');
            }

            log_message('error', '[API] AdsController create: RÉSUMÉ FILTRES DÉTECTÉS: ' . json_encode($filtersFound));

            // Traitement de chaque filtre trouvé
            foreach ($filtersFound as $filterId => $value) {
                log_message('error', '[API] AdsController create: ==== TRAITEMENT FILTRE ' . $filterId . ' ====');

                // Validation des données du filtre
                if (!is_numeric($filterId) || empty($value)) {
                    log_message('error', '[API] AdsController create: ❌ Données filtre invalides - filterId: ' . $filterId . ', value: ' . $value);
                    continue;
                }

                try {
                    // Préparer les données du filtre
                    $filterData = [
                        'ad_id' => (int) $newAdId,
                        'filter_id' => (int) $filterId,
                        'value' => is_array($value) ? implode(',', array_map('trim', $value)) : trim((string) $value)
                    ];

                    // Vérifier que la valeur n'est pas vide après trim
                    if (empty($filterData['value'])) {
                        log_message('error', '[API] AdsController create: ❌ Valeur filtre vide après nettoyage - filterId: ' . $filterId);
                        continue;
                    }

                    log_message('error', '[API] AdsController create: Données filtre à insérer: ' . json_encode($filterData));

                    // Insérer le filtre
                    $filterInsertResult = $adFilterValueModel->insertFilterValue($filterData);

                    if ($filterInsertResult) {
                        $filtersProcessed++;
                        log_message('error', '[API] AdsController create: ✅ Filtre ' . $filterId . ' inséré avec succès');
                    } else {
                        log_message('error', '[API] AdsController create: ❌ Échec insertion filtre ' . $filterId);

                        // Récupérer les erreurs du modèle
                        $modelErrors = $adFilterValueModel->errors();
                        if (!empty($modelErrors)) {
                            log_message('error', '[API] AdsController create: Erreurs modèle: ' . json_encode($modelErrors));
                        }

                        // Récupérer l'erreur de la base de données
                        $dbError = $db->error();
                        if (!empty($dbError)) {
                            log_message('error', '[API] AdsController create: Erreur DB: ' . json_encode($dbError));
                        }
                    }
                } catch (\Exception $e) {
                    log_message('error', '[API] AdsController create: ❌ Exception filtre ' . $filterId . ': ' . $e->getMessage());
                    log_message('error', '[API] AdsController create: Trace: ' . $e->getTraceAsString());

                    // Ne pas arrêter le processus pour un filtre défaillant
                    // Continuer avec les autres filtres
                }
            }

            log_message('error', '[API] AdsController create: ==== FIN TRAITEMENT FILTRES ====');
            log_message('error', '[API] AdsController create: Filtres trouvés: ' . count($filtersFound) . ', Filtres traités avec succès: ' . $filtersProcessed);

            // 10. Valider la transaction
            $db->transCommit();
            log_message('error', '[API] AdsController create: Transaction validée avec succès');

            // 11. Gestion du boost
            $paymentInfo = null;
            if ($boostPlan) {
                if ($isPaidBoost) {
                    // Boost payant : initier le paiement
                    log_message('error', '[API] AdsController create: Initiation paiement boost payant');
                    try {
                        $boostService = new \App\Services\BoostService();
                        // Pour l'initiation du paiement, on a besoin du numéro de téléphone et de la méthode de paiement
                        // Ces informations doivent être fournies dans la requête
                        if (empty($allData['phone']) || empty($allData['payment_method'])) {
                            $db->transRollback();
                            return $this->failValidationErrors([
                                'phone' => 'Numéro de téléphone requis pour le paiement',
                                'payment_method' => 'Méthode de paiement requise'
                            ]);
                        }
                        
                        $paymentResult = $boostService->startBoostPayment(
                            $newAdId,
                            $userId,
                            $allData['boost_plan_id'],
                            $allData['phone'],
                            $allData['payment_method']
                        );
                        
                        $paymentInfo = [
                            'payment_id' => $paymentResult['payment_id'],
                            'reference' => $paymentResult['reference'],
                            'amount' => $boostPlan['price'],
                            'currency' => 'XAF',
                            'description' => 'Boost annonce - ' . $boostPlan['name'],
                            'ussd_code' => '*126#', // Code USSD générique pour MTN/Ora
                            'instructions' => 'Composez *126# et suivez les instructions pour payer ' . $boostPlan['price'] . ' XAF'
                        ];
                        
                        log_message('error', '[API] AdsController create: Paiement initié avec succès');
                        
                    } catch (\Exception $e) {
                        log_message('error', '[API] AdsController create: Erreur initiation paiement: ' . $e->getMessage());
                        // En cas d'erreur de paiement, on annule la création de l'annonce
                        $db->transRollback();
                        return $this->failServerError('Erreur lors de l\'initiation du paiement: ' . $e->getMessage());
                    }
                } else {
                    // Boost gratuit : appliquer directement
                    log_message('error', '[API] AdsController create: Application boost gratuit');
                    try {
                        $boostService = new \App\Services\BoostService();
                        $boostService->applyFreeBoost($newAdId, $boostPlan['duration_days']);
                        log_message('error', '[API] AdsController create: Boost gratuit appliqué avec succès');
                    } catch (\Exception $e) {
                        log_message('error', '[API] AdsController create: Erreur application boost gratuit: ' . $e->getMessage());
                        // Pour le boost gratuit, on peut continuer même si ça échoue
                    }
                }
            }

            // 12. Récupérer l'annonce créée avec ses relations
            $newAd = $adModel->find($newAdId);

            log_message('error', '[API] AdsController create: ==== FIN CRÉATION ANNONCE ====');
            log_message('error', '[API] AdsController create: ID: ' . $newAdId . ', Photos: ' . $photosProcessed . ', Filtres: ' . $filtersProcessed);

            // 13. Réponse conditionnelle selon le type de boost
            if ($isPaidBoost && $paymentInfo) {
                log_message('error', '[API] AdsController create: Retour réponse PAYMENT_PENDING');
                return $this->respond([
                    'status' => 'payment_pending',
                    'message' => 'Annonce sauvegardée comme brouillon. Veuillez finaliser le paiement.',
                    'ad_id' => $newAdId,
                    'payment_info' => $paymentInfo
                ], 200);
            } else {
                log_message('error', '[API] AdsController create: Retour réponse SUCCESS - isPaidBoost: ' . ($isPaidBoost ? 'true' : 'false') . ', paymentInfo: ' . ($paymentInfo ? 'present' : 'null'));
                $responseData = [
                    'status' => 'success',
                    'message' => 'Annonce créée avec succès.',
                    'ad' => $newAd,
                    'stats' => [
                        'photos_processed' => $photosProcessed,
                        'filters_processed' => $filtersProcessed,
                        'filters_found' => count($filtersFound)
                    ]
                ];
                log_message('error', '[API] AdsController create: Données de réponse: ' . json_encode($responseData));
                return $this->respond($responseData, 200);
            }

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[API] AdsController create: ❌ Exception générale: ' . $e->getMessage());
            log_message('error', '[API] AdsController create: Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Une erreur est survenue lors de la création de l\'annonce: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier le statut d'une annonce (utilisé pour le polling de paiement)
     * GET /api/ads/{id}/status
     */
    public function getStatus($adId)
    {
        log_message('error', '[API] AdsController getStatus: Vérification statut annonce ' . $adId);

        try {
            $adModel = new \App\Models\AdModel();
            $ad = $adModel->find($adId);

            if (!$ad) {
                return $this->failNotFound('Annonce introuvable');
            }

            // Vérifier si l'utilisateur est propriétaire de l'annonce
            $user = $this->request->user ?? null;
            $userId = null;
            if (is_array($user) && isset($user['user_id'])) {
                $userId = $user['user_id'];
            } elseif (is_object($user) && isset($user->user_id)) {
                $userId = $user->user_id;
            }

            if (!$userId || $ad['user_id'] != $userId) {
                return $this->failForbidden('Accès non autorisé à cette annonce');
            }

            // Si l'annonce est déjà publiée, retourner le statut normal
            if ($ad['publication_status'] === 'published') {
                return $this->respond([
                    'status' => 'published',
                    'moderation_status' => $ad['moderation_status'],
                    'message' => 'Annonce déjà publiée'
                ], 200);
            }

            // Vérifier s'il y a un paiement associé
            $paymentModel = new \App\Models\PaymentModel();
            $payment = $paymentModel->where('ad_id', $adId)->first();

            if (!$payment) {
                // Pas de paiement, annonce normale
                return $this->respond([
                    'status' => 'published',
                    'moderation_status' => $ad['moderation_status'],
                    'message' => 'Annonce publiée'
                ], 200);
            }

            // Il y a un paiement, vérifier son statut
            $boostService = new \App\Services\BoostService();
            $result = $boostService->verifyAndUpdatePaymentStatus($payment['id']);

            if ($result['status'] === 'paid') {
                // Paiement réussi, mettre à jour l'annonce
                $adModel->update($adId, [
                    'publication_status' => 'published'
                ]);

                return $this->respond([
                    'status' => 'payment_success',
                    'message' => 'Paiement confirmé et annonce publiée.',
                    'moderation_status' => $ad['moderation_status']
                ], 200);
            } elseif ($result['status'] === 'failed') {
                return $this->respond([
                    'status' => 'payment_failed',
                    'message' => 'Le paiement a échoué.'
                ], 200);
            } else {
                // Toujours en attente
                return $this->respond([
                    'status' => 'payment_pending',
                    'message' => 'Paiement toujours en attente.'
                ], 200);
            }

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getStatus: Erreur: ' . $e->getMessage());
            return $this->failServerError('Erreur lors de la vérification du statut');
        }
    }

    public function index()
    {
        $adModel = new AdModel();
        $request = $this->request;

        try {
            // Paramètres de pagination
            $page = (int) ($request->getGet('page') ?? 1);
            $perPage = (int) ($request->getGet('per_page') ?? 1000);
            $offset = ($page - 1) * $perPage;

            // Paramètres de filtrage de base
            $filters = [
                'category_id' => $request->getGet('category_id'),
                'subcategory_id' => $request->getGet('subcategory_id'),
                'location_id' => $request->getGet('location_id'),
                'brand_id' => $request->getGet('brand_id'),
                'status' => $request->getGet('status') ?? 'active',
                'moderation_status' => $request->getGet('moderation_status'),
                'min_price' => $request->getGet('min_price'),
                'max_price' => $request->getGet('max_price'),
                'search' => $request->getGet('search'),
                'user_id' => $request->getGet('user_id'),
                'has_discount' => $request->getGet('has_discount'),
            ];

            // Paramètres de filtrage avancés (filtres dynamiques)
            $advancedFilters = [];

            // Récupérer tous les paramètres GET qui commencent par 'filter_'
            $getParams = $request->getGet();
            foreach ($getParams as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value)) {
                    $filterId = str_replace('filter_', '', $key);

                    // Support pour les filtres avec min/max (ex: filter_6_min, filter_6_max)
                    if (strpos($filterId, '_min') !== false) {
                        $cleanFilterId = str_replace('_min', '', $filterId);
                        $advancedFilters[$cleanFilterId]['min'] = $value;
                    } elseif (strpos($filterId, '_max') !== false) {
                        $cleanFilterId = str_replace('_max', '', $filterId);
                        $advancedFilters[$cleanFilterId]['max'] = $value;
                    } else {
                        // Filtre simple avec valeur exacte
                        $advancedFilters[$filterId]['value'] = $value;
                    }
                }
            }

            log_message('error', '[API] AdsController index: Filtres avancés détectés: ' . json_encode($advancedFilters));

            // Paramètres de tri
            $sortBy = $request->getGet('sort_by') ?? 'created_at';
            $sortOrder = $request->getGet('sort_order') ?? 'DESC';

            // Validation des paramètres de tri
            $allowedSortFields = ['created_at', 'updated_at', 'price', 'title', 'view_count'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
                $sortOrder = 'DESC';
            }

            // Construction de la requête
            $query = $adModel->select('ads.*, locations.city as location_name,
                                      subcategories.name as subcategory_name, categories.name as category_name,
                                      brands.name as brand_name, users.first_name as seller_username,
                                      users.is_verified as userVerified')
                            ->join('locations', 'locations.id = ads.location_id', 'left')
                            ->join('subcategories', 'subcategories.id = ads.subcategory_id', 'left')
                            ->join('categories', 'categories.id = subcategories.category_id', 'left')
                            ->join('brands', 'brands.id = ads.brand_id', 'left')
                            ->join('users', 'users.id_user = ads.user_id', 'left')
                            ->where('ads.status !=', 'deleted')
                            ->where('ads.publication_status', 'published');

            // Application des filtres
            if (!empty($filters['category_id'])) {
                $query->where('categories.id', $filters['category_id']);
            }

            if (!empty($filters['subcategory_id'])) {
                $query->where('ads.subcategory_id', $filters['subcategory_id']);
            }

            if (!empty($filters['location_id'])) {
                $query->where('ads.location_id', $filters['location_id']);
            }

            if (!empty($filters['brand_id'])) {
                $query->where('ads.brand_id', $filters['brand_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('ads.status', $filters['status']);
            }

            if (!empty($filters['moderation_status'])) {
                $query->where('ads.moderation_status', $filters['moderation_status']);
            }

            if (!empty($filters['min_price'])) {
                $query->where('ads.price >=', $filters['min_price']);
            }

            if (!empty($filters['max_price'])) {
                $query->where('ads.price <=', $filters['max_price']);
            }

            if (!empty($filters['search'])) {
                $query->groupStart()
                      ->like('ads.title', $filters['search'])
                      ->orLike('ads.description', $filters['search'])
                      ->groupEnd();
            }

            if (!empty($filters['user_id'])) {
                $query->where('ads.user_id', $filters['user_id']);
            }

            if ($filters['has_discount'] !== null) {
                $query->where('ads.has_discount', (int)$filters['has_discount']);
            }

            // Application des filtres avancés (filtres dynamiques)
            if (!empty($advancedFilters)) {
                log_message('error', '[API] AdsController index: Application des filtres avancés: ' . json_encode($advancedFilters));

                foreach ($advancedFilters as $filterId => $filterConfig) {
                    // Vérifier que le filterId est numérique
                    if (!is_numeric($filterId)) {
                        continue;
                    }

                    // Jointure avec ad_filter_values pour ce filtre spécifique
                    $query->join("ad_filter_values fv{$filterId}", "fv{$filterId}.ad_id = ads.id AND fv{$filterId}.filter_id = {$filterId}", 'left');

                    // Application des conditions selon le type de filtre
                    if (isset($filterConfig['value'])) {
                        // Filtre exact
                        $query->where("fv{$filterId}.value", $filterConfig['value']);
                        log_message('error', '[API] AdsController index: Filtre exact appliqué - filter_' . $filterId . ' = ' . $filterConfig['value']);
                    } elseif (isset($filterConfig['min']) || isset($filterConfig['max'])) {
                        // Filtre par plage (min/max)
                        if (isset($filterConfig['min']) && isset($filterConfig['max'])) {
                            // Les deux valeurs sont définies
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min'])
                                  ->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController index: Filtre plage appliqué - filter_' . $filterId . ' entre ' . $filterConfig['min'] . ' et ' . $filterConfig['max']);
                        } elseif (isset($filterConfig['min'])) {
                            // Seulement min
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min']);
                            log_message('error', '[API] AdsController index: Filtre min appliqué - filter_' . $filterId . ' >= ' . $filterConfig['min']);
                        } elseif (isset($filterConfig['max'])) {
                            // Seulement max
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController index: Filtre max appliqué - filter_' . $filterId . ' <= ' . $filterConfig['max']);
                        }
                    }
                }
            }

            // Comptage total pour la pagination
            $totalQuery = clone $query;
            $total = $totalQuery->countAllResults(false);

            // Application du tri avec priorité pour les annonces boostées
            // 1. Annonces boostées actives en premier (is_boosted = 1 ET boost_end >= NOW())
            // 2. Ensuite le tri demandé par l'utilisateur
            $ads = $query->orderBy('CASE WHEN ads.is_boosted = 1 AND ads.boost_end >= NOW() THEN 0 ELSE 1 END', 'ASC')
                        ->orderBy('ads.' . $sortBy, $sortOrder)
                        ->limit($perPage, $offset)
                        ->findAll();

            // Récupération des photos et filtres pour chaque annonce
            $adPhotoModel = new AdPhotoModel();
            $adFilterValueModel = new AdFilterValueModel();

            foreach ($ads as &$ad) {
                // Photos
                $ad['photos'] = $adPhotoModel->where('ad_id', $ad['id'])
                                           ->where('display_order >=', 0)
                                           ->orderBy('display_order', 'ASC')
                                           ->findAll();

                // Filtres
                $ad['filters'] = $adFilterValueModel->select('ad_filter_values.*, filters.name as filter_name, filters.type as filter_type')
                                                   ->join('filters', 'filters.id = ad_filter_values.filter_id')
                                                   ->where('ad_filter_values.ad_id', $ad['id'])
                                                   ->findAll();

                // Formatage des prix
                $ad['price'] = (float) $ad['price'];
                $ad['original_price'] = $ad['original_price'] ? (float) $ad['original_price'] : null;
                
                // Formatage de userVerified
                $ad['userVerified'] = (int) ($ad['userVerified'] ?? 0);
            }

            // Métadonnées de pagination
            $totalPages = ceil($total / $perPage);
            $pagination = [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'total_pages' => (int) $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null,
            ];

            return $this->respond([
                'ads' => $ads,
                'pagination' => $pagination,
                'filters' => array_filter($filters), // Retourne seulement les filtres appliqués
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController index: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération des annonces.');
        }
    }

    public function show($id = null)
    {
        $adModel = new AdModel();
        $adPhotoModel = new AdPhotoModel();
        $adFilterValueModel = new AdFilterValueModel();

        try {
            // Déterminer si c'est un ID ou un slug
            $whereCondition = is_numeric($id) ? ['ads.id' => $id] : ['ads.slug' => $id];

            // Récupération de l'annonce avec ses relations
            $ad = $adModel->select('ads.*, locations.city as location_name, locations.region as location_type,
                                  subcategories.name as subcategory_name, subcategories.slug as subcategory_slug,
                                  categories.name as category_name, categories.slug as category_slug,
                                  brands.name as brand_name, users.first_name as seller_username,
                                  users.email as seller_email, users.is_verified as userVerified')
                         ->join('locations', 'locations.id = ads.location_id', 'left')
                         ->join('subcategories', 'subcategories.id = ads.subcategory_id', 'left')
                         ->join('categories', 'categories.id = subcategories.category_id', 'left')
                         ->join('brands', 'brands.id = ads.brand_id', 'left')
                         ->join('users', 'users.id_user = ads.user_id', 'left')
                         ->where($whereCondition)
                         ->where('ads.status !=', 'deleted')
                         ->first();

            if (!$ad) {
                return $this->failNotFound('Annonce non trouvée ou supprimée.');
            }

            // Récupération des informations complètes de l'utilisateur
            $userDetails = $this->userService->find($ad['user_id']);
            if ($userDetails) {
                // Nettoyer les informations sensibles
                unset($userDetails['password_hash']);
                unset($userDetails['verification_token']);
                unset($userDetails['reset_token']);
                unset($userDetails['reset_token_expires']);
                $ad['user_details'] = $userDetails;
            }

            // Récupération du profil vendeur
            $sellerProfile = $this->sellerService->getSellerProfileByUserId($ad['user_id']);
            if ($sellerProfile) {
                $ad['seller_profile'] = $sellerProfile;
            }

            // Récupération des photos
            $ad['photos'] = $adPhotoModel->where('ad_id', $ad['id'])
                                       ->orderBy('display_order', 'ASC')
                                       ->findAll();

            // Récupération des filtres avec leurs détails
            $ad['filters'] = $adFilterValueModel->select('ad_filter_values.*, filters.name as filter_name,
                                                        filters.type as filter_type, filters.is_required')
                                               ->join('filters', 'filters.id = ad_filter_values.filter_id')
                                               ->where('ad_filter_values.ad_id', $ad['id'])
                                               ->findAll();

            // Formatage des prix
            $ad['price'] = (float) $ad['price'];
            $ad['original_price'] = $ad['original_price'] ? (float) $ad['original_price'] : null;
            
            // Formatage de userVerified
            $ad['userVerified'] = (int) ($ad['userVerified'] ?? 0);

            // Incrémenter le compteur de vues
                $adModel->where('id', $ad['id'])->increment('view_count', 1);

            // Ajouter des alias explicites attendus par le client
            $ad['subcategorySlug'] = $ad['subcategory_slug'] ?? null;
            $ad['categorySlug'] = $ad['category_slug'] ?? null;

            return $this->respond($ad);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController show: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération de l\'annonce.');
        }
    }

    public function update($id = null)
    {
        log_message('error', '[API] AdsController update: ==== DÉBUT MISE À JOUR ANNONCE ====');

        try {
            // Vérifier que l'annonce existe et appartient à l'utilisateur
            $adModel = new AdModel();
            $whereCondition = is_numeric($id) ? ['id' => $id] : ['slug' => $id, 'status !=' => 'deleted'];
            $existingAd = $adModel->where($whereCondition)->first();

            if (!$existingAd) {
                return $this->failNotFound('Annonce non trouvée.');
            }

            // Vérifier les permissions (seul le propriétaire peut modifier)
            $user = $this->request->user ?? null;
            $userId = null;

            if (is_array($user) && isset($user['user_id'])) {
                $userId = $user['user_id'];
            } elseif (is_object($user) && isset($user->user_id)) {
                $userId = $user->user_id;
            }

            if (!$userId) {
                return $this->failUnauthorized('Utilisateur non authentifié.');
            }

            if ($existingAd['user_id'] != $userId) {
                return $this->failForbidden('Vous n\'avez pas les permissions pour modifier cette annonce.');
            }

            // Récupérer les données
            $postData = $this->request->getPost();
            $jsonData = null; // ne pas parser JSON d'emblée pour éviter les erreurs avec form-data
            $rawData = $this->request->getRawInput();
            $allData = !empty($postData) ? $postData : [];

            // Gestion sécurisée du JSON
            if (is_array($rawData)) {
                $rawData = json_encode($rawData);
            }

            try {
                // Parser le JSON uniquement si le Content-Type l'indique
                $contentType = $this->request->getHeaderLine('Content-Type');
                if ($contentType && stripos($contentType, 'application/json') !== false) {
                    $jsonData = $this->request->getJSON(true);
                }
            } catch (\Exception $e) {
                if (!empty($rawData)) {
                    $manualJson = json_decode($rawData, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $jsonData = $manualJson;
                    }
                }
            }

            if (!empty($jsonData) && empty($postData)) {
                $allData = $jsonData;
            }

            // Logs détaillés des données reçues
            log_message('error', '[API] AdsController update: POST Data: ' . json_encode($postData));
            log_message('error', '[API] AdsController update: JSON Data: ' . json_encode($jsonData));
            log_message('error', '[API] AdsController update: Raw Input (first 500 chars): ' . substr(is_string($rawData) ? $rawData : json_encode($rawData), 0, 500));
            $uploadedFilesLog = array_keys($this->request->getFiles() ?: []);
            log_message('error', '[API] AdsController update: Files reçus (clés): ' . json_encode($uploadedFilesLog));
            if (isset($allData['filters'])) {
                // Log des filtres reçus (premiers éléments)
                $filtersPreview = $allData['filters'];
                if (is_array($filtersPreview)) {
                    $previewSlice = array_slice($filtersPreview, 0, 10, true);
                    log_message('error', '[API] AdsController update: Filtres reçus (aperçu): ' . json_encode($previewSlice));
                } else {
                    log_message('error', '[API] AdsController update: Filtres reçus (non-array): ' . json_encode($allData['filters']));
                }
            }
            if (isset($allData['existing_photo_orders'])) {
                log_message('error', '[API] AdsController update: existing_photo_orders (brut): ' . json_encode($allData['existing_photo_orders']));
            }

            // Validation des données
            $rules = [
                'title'              => 'permit_empty|string|max_length[150]',
                'description'        => 'permit_empty|string|max_length[2000]',
                'price'              => 'permit_empty|numeric|greater_than[0]',
                'original_price'     => 'permit_empty|numeric|greater_than[0]',
                'discount_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
                'has_discount'       => 'permit_empty|in_list[0,1]',
                'is_negotiable'      => 'permit_empty|in_list[0,1]',
                'referral_code'      => 'permit_empty|string|max_length[50]',
                'subcategory_id'     => 'permit_empty|integer|greater_than[0]|is_not_unique[subcategories.id]',
                'location_id'        => 'permit_empty|integer|greater_than[0]|is_not_unique[locations.id]',
                'brand_id'           => 'permit_empty|integer|is_not_unique[brands.id]',
                'status'             => 'permit_empty|in_list[active,inactive]',
            ];

            if (!$this->validate($rules, $allData)) {
                return $this->failValidationErrors($this->validator->getErrors());
            }

            // Démarrer la transaction
            $db = \Config\Database::connect();
            $db->transStart();

            // Préparer les données de mise à jour
            $updateData = [];

            if (isset($allData['title']) && !empty(trim($allData['title']))) {
                $updateData['title'] = trim($allData['title']);
                $newSlug = generate_ad_slug($allData['title']);
                log_message('error', '[API] AdsController update: Génération slug pour titre: "' . $allData['title'] . '" -> "' . $newSlug . '"');
                $updateData['slug'] = $newSlug;
            }

            if (isset($allData['description'])) {
                $updateData['description'] = trim($allData['description']);
            }

            if (isset($allData['price'])) {
                $updateData['price'] = (float) $allData['price'];
            }

            if (isset($allData['original_price'])) {
                $updateData['original_price'] = !empty($allData['original_price']) ? (float) $allData['original_price'] : null;
            }

            if (isset($allData['discount_percentage'])) {
                $updateData['discount_percentage'] = !empty($allData['discount_percentage']) ? (int) $allData['discount_percentage'] : null;
            }

            if (isset($allData['has_discount'])) {
                $updateData['has_discount'] = (int) $allData['has_discount'];
            }

            if (isset($allData['is_negotiable'])) {
                $updateData['is_negotiable'] = (int) $allData['is_negotiable'];
            }

            if (isset($allData['referral_code'])) {
                $updateData['referral_code'] = !empty($allData['referral_code']) ? trim($allData['referral_code']) : null;
            }

            if (isset($allData['subcategory_id'])) {
                $updateData['subcategory_id'] = (int) $allData['subcategory_id'];
            }

            if (isset($allData['location_id'])) {
                $updateData['location_id'] = (int) $allData['location_id'];
            }

            if (isset($allData['brand_id'])) {
                $updateData['brand_id'] = !empty($allData['brand_id']) ? (int) $allData['brand_id'] : null;
            }

            if (isset($allData['status'])) {
                $updateData['status'] = $allData['status'];
            }

            // Ajouter la date de mise à jour
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            // Mettre à jour l'annonce
            if (!empty($updateData)) {
                $result = $adModel->update($existingAd['id'], $updateData);
                if (!$result) {
                    $db->transRollback();
                    return $this->failServerError('Échec de la mise à jour de l\'annonce.');
                }
            }

            // Gestion des filtres si fournis
            if (isset($allData['filter_values']) && is_array($allData['filter_values'])) {
                $adFilterValueModel = new AdFilterValueModel();

                // Supprimer les anciens filtres
                $adFilterValueModel->where('ad_id', $existingAd['id'])->delete();

                // Ajouter les nouveaux filtres
                foreach ($allData['filter_values'] as $filterItem) {
                    if (is_array($filterItem) && isset($filterItem['filter_id']) && isset($filterItem['value'])) {
                        $filterId = $filterItem['filter_id'];
                        $value = $filterItem['value'];

                        if (is_numeric($filterId) && !empty($value)) {
                            $filterData = [
                                'ad_id' => (int) $existingAd['id'],
                                'filter_id' => (int) $filterId,
                                'value' => is_array($value) ? implode(',', $value) : (string) $value
                            ];

                            $adFilterValueModel->insertFilterValue($filterData);
                        }
                    }
                }
            } elseif (isset($allData['filters']) && is_array($allData['filters'])) {
                // Compat: accepter le format 'filters' comme dans create()
                $adFilterValueModel = new AdFilterValueModel();
                $adFilterValueModel->where('ad_id', $existingAd['id'])->delete();
                $inserted = 0;
                foreach ($allData['filters'] as $filterId => $value) {
                    if (!is_numeric($filterId)) {
                        continue;
                    }
                    if (is_array($value)) {
                        $value = implode(',', array_filter($value, fn($v) => $v !== '' && $v !== null));
                    }
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    $adFilterValueModel->insertFilterValue([
                        'ad_id' => (int)$existingAd['id'],
                        'filter_id' => (int)$filterId,
                        'value' => (string)$value,
                    ]);
                    $inserted++;
                }
                log_message('error', '[API] AdsController update: Filtres (format filters) insérés: ' . $inserted);
            }

            // Gestion avancée des photos
            $uploadedFiles = $this->request->getFiles();
            $adPhotoModel = new AdPhotoModel();

            // 7.0 Journaliser les clés de contrôle reçues
            log_message('error', '[API] AdsController update: Keys reçues: ' . json_encode(array_keys($allData)));

            // Normaliser les différentes variantes de noms de champs pour compatibilité
            $deletePhotoIdsRaw = $allData['delete_photo_ids']
                ?? $allData['deletePhotos']
                ?? $allData['photos_to_delete']
                ?? null;
            $existingOrdersRaw = $allData['existing_photo_orders']
                ?? $allData['photo_orders']
                ?? $allData['photos_order']
                ?? null;
            // Mapping optionnel tempId -> dbId pour compat front
            $photoIdMapRaw = $allData['existing_photo_id_map']
                ?? $allData['photo_id_map']
                ?? $allData['map_photo_ids']
                ?? null;
            $replacePhotosFlag = $allData['replace_photos']
                ?? $allData['replacePhotos']
                ?? null;

            // 7.1 Suppression ciblée de photos existantes
            if (!empty($deletePhotoIdsRaw)) {
                $idsToDelete = $deletePhotoIdsRaw;
                if (is_string($idsToDelete)) {
                    // Peut être CSV ou JSON
                    $decoded = json_decode($idsToDelete, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $idsToDelete = $decoded;
                    } else {
                        $idsToDelete = array_filter(array_map('intval', explode(',', $idsToDelete)));
                    }
                }
                if (is_array($idsToDelete) && !empty($idsToDelete)) {
                    log_message('error', '[API] AdsController update: Suppression photos IDs: ' . json_encode($idsToDelete));
                    $adPhotoModel->where('ad_id', $existingAd['id'])->whereIn('id', $idsToDelete)->delete();
                }
            }

            // 7.2 Réordonnancement de photos existantes: { photo_id: display_order }
            if (!empty($existingOrdersRaw)) {
                $orders = $existingOrdersRaw;
                if (is_string($orders)) {
                    $decoded = json_decode($orders, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $orders = $decoded;
                    }
                }
                if (is_array($orders)) {
                    // Deux formats supportés:
                    // 1) { photoId: display_order }
                    // 2) [ "order-photoId", ... ]
                    $reordered = 0;
                    $isAssoc = array_keys($orders) !== range(0, count($orders) - 1);
                    // Charger le mapping si fourni
                    $photoIdMap = [];
                    if (!empty($photoIdMapRaw)) {
                        if (is_string($photoIdMapRaw)) {
                            $decodedMap = json_decode($photoIdMapRaw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMap)) {
                                $photoIdMap = $decodedMap;
                            }
                        } elseif (is_array($photoIdMapRaw)) {
                            $photoIdMap = $photoIdMapRaw;
                        }
                    }
                    if (!empty($photoIdMap)) {
                        log_message('error', '[API] AdsController update: Photo ID map fourni: ' . json_encode($photoIdMap));
                    }
                    if ($isAssoc) {
                        foreach ($orders as $photoId => $displayOrder) {
                            $dbPhotoId = null;
                            if (is_numeric($photoId)) {
                                $dbPhotoId = (int)$photoId;
                            } elseif (!empty($photoIdMap) && isset($photoIdMap[$photoId]) && is_numeric($photoIdMap[$photoId])) {
                                $dbPhotoId = (int)$photoIdMap[$photoId];
                            }
                            if ($dbPhotoId !== null) {
                                log_message('error', '[API] AdsController update: Réordonnancement photo ' . $dbPhotoId . ' -> ' . (int)$displayOrder);
                                $adPhotoModel->where('ad_id', $existingAd['id'])->where('id', $dbPhotoId)->set('display_order', (int)$displayOrder)->update();
                                $reordered++;
                            } else {
                                log_message('error', '[API] AdsController update: Impossible de résoudre photoId ' . json_encode($photoId) . ' (mapping manquant ?)');
                            }
                        }
                    } else {
                        foreach ($orders as $entry) {
                            if (is_string($entry) && strpos($entry, '-') !== false) {
                                [$orderStr, $photoIdStr] = explode('-', $entry, 2);
                                if (is_numeric($orderStr) && is_numeric($photoIdStr)) {
                                    $displayOrder = (int)$orderStr;
                                    $photoId = (int)$photoIdStr;
                                    log_message('error', '[API] AdsController update: Réordonnancement (liste) photo ' . $photoId . ' -> ' . $displayOrder);
                                    $adPhotoModel->where('ad_id', $existingAd['id'])->where('id', $photoId)->set('display_order', $displayOrder)->update();
                                    $reordered++;
                                } elseif (is_string($photoIdStr) && !is_numeric($photoIdStr) && !empty($photoIdMap) && isset($photoIdMap[$photoIdStr]) && is_numeric($photoIdMap[$photoIdStr])) {
                                    $displayOrder = (int)$orderStr;
                                    $dbPhotoId = (int)$photoIdMap[$photoIdStr];
                                    log_message('error', '[API] AdsController update: Réordonnancement (liste+map) photo ' . $dbPhotoId . ' -> ' . $displayOrder);
                                    $adPhotoModel->where('ad_id', $existingAd['id'])->where('id', $dbPhotoId)->set('display_order', $displayOrder)->update();
                                    $reordered++;
                                } elseif (is_numeric($orderStr)) {
                                    // Mode fallback: on ignore l'identifiant, on applique simplement l'ordre
                                    // On réindexera plus bas en se basant sur la séquence d'ordres reçus
                                    continue;
                                }
                            }
                        }
                        // Fallback: si aucun réordonnancement précis n'a été possible via IDs/map,
                        // on applique l'ordre en se basant uniquement sur la séquence des ordres reçus
                        if ($reordered === 0) {
                            $desiredOrders = [];
                            foreach ($orders as $entry) {
                                if (is_string($entry) && strpos($entry, '-') !== false) {
                                    [$orderStr] = explode('-', $entry, 2);
                                    if (is_numeric($orderStr)) {
                                        $desiredOrders[] = (int)$orderStr;
                                    }
                                }
                            }
                            sort($desiredOrders);
                            $existingPhotos = $adPhotoModel->where('ad_id', $existingAd['id'])->orderBy('display_order', 'ASC')->findAll();
                            $countToApply = min(count($desiredOrders), count($existingPhotos));
                            for ($i = 0; $i < $countToApply; $i++) {
                                $photo = $existingPhotos[$i];
                                $newOrder = $desiredOrders[$i];
                                log_message('error', '[API] AdsController update: Réordonnancement (fallback) photo ' . $photo['id'] . ' -> ' . $newOrder);
                                $adPhotoModel->where('ad_id', $existingAd['id'])->where('id', $photo['id'])->set('display_order', $newOrder)->update();
                                $reordered++;
                            }
                        }
                    }
                    log_message('error', '[API] AdsController update: Total photos réordonnées: ' . $reordered);
                }
            }

            // 7.3 Remplacement complet des photos existantes si demandé
            $replacePhotos = !empty($replacePhotosFlag) && (int)$replacePhotosFlag === 1;
            if ($replacePhotos) {
                log_message('error', '[API] AdsController update: Remplacement complet des photos (delete all)');
                $adPhotoModel->where('ad_id', $existingAd['id'])->delete();
            }

            // 7.4 Ajout de nouvelles photos uploadées (toujours possible)
            if (isset($uploadedFiles['photos']) && is_array($uploadedFiles['photos'])) {
                $uploadService = new UploadService();

                foreach ($uploadedFiles['photos'] as $order => $img) {
                    if ($img && $img->isValid() && !$img->hasMoved()) {
                        $uploadResult = $uploadService->upload("photos.{$order}", 'uploads/ads');

                        if ($uploadResult['success']) {
                            $filename = basename($uploadResult['path']);
                            $relativePath = '/uploads/ads/' . $filename;

                            $photoData = [
                                'ad_id' => (int) $existingAd['id'],
                                'original_url' => $relativePath,
                                'thumbnail_url' => null,
                                'display_order' => (int) $order,
                                'alt_text' => 'Photo ' . ($order + 1)
                            ];

                            $adPhotoModel->insertPhoto($photoData);
                            log_message('error', '[API] AdsController update: Nouvelle photo ajoutée à l\'ordre ' . (int)$order . ' => ' . $relativePath);
                        }
                    }
                }
            }

            $db->transCommit();

            // Récupérer l'annonce mise à jour
            $updatedAd = $adModel->find($existingAd['id']);

            log_message('error', '[API] AdsController update: ==== FIN MISE À JOUR ANNONCE ====');

            return $this->respondUpdated([
                'ad' => $updatedAd,
                'message' => 'Annonce mise à jour avec succès.'
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController update: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la mise à jour de l\'annonce.');
        }
    }

    public function delete($id = null)
    {
        log_message('error', '[API] AdsController delete: ==== DÉBUT SUPPRESSION ANNONCE ====');

        try {
            // Vérifier que l'annonce existe
            $adModel = new AdModel();
            $whereCondition = is_numeric($id) ? ['id' => $id] : ['slug' => $id, 'status !=' => 'deleted'];
            $ad = $adModel->where($whereCondition)->first();

            if (!$ad) {
                return $this->failNotFound('Annonce non trouvée.');
            }

            // Vérifier les permissions (seul le propriétaire peut supprimer)
            $user = $this->request->user ?? null;
            $userId = null;

            if (is_array($user) && isset($user['user_id'])) {
                $userId = $user['user_id'];
            } elseif (is_object($user) && isset($user->user_id)) {
                $userId = $user->user_id;
            }

            if (!$userId) {
                return $this->failUnauthorized('Utilisateur non authentifié.');
            }

            if ($ad['user_id'] != $userId) {
                return $this->failForbidden('Vous n\'avez pas les permissions pour supprimer cette annonce.');
            }

            // Démarrer la transaction
            $db = \Config\Database::connect();
            $db->transStart();

            // Soft delete - changer le statut au lieu de supprimer physiquement
            $result = $adModel->update($ad['id'], [
                'status' => 'deleted',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$result) {
                $db->transRollback();
                return $this->failServerError('Échec de la suppression de l\'annonce.');
            }

            $db->transCommit();

            log_message('error', '[API] AdsController delete: ==== FIN SUPPRESSION ANNONCE ====');

            return $this->respondDeleted([
                'message' => 'Annonce supprimée avec succès.'
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController delete: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la suppression de l\'annonce.');
        }
    }

    /**
     * Méthode utilitaire pour récupérer une annonce par ID (pour compatibilité)
     * Utile pour les intégrations qui utilisent encore les IDs
     */
    public function getById($id)
    {
        try {
            $adModel = new AdModel();

            // Vérifier que l'ID est numérique
            if (!is_numeric($id)) {
                return $this->failNotFound('ID d\'annonce invalide.');
            }

            // Récupération de l'annonce avec ses relations
            $ad = $adModel->select('ads.*, locations.city as location_name, locations.region as location_type,
                                  subcategories.name as subcategory_name, categories.name as category_name,
                                  brands.name as brand_name, users.first_name as seller_username,
                                  users.email as seller_email, users.is_verified as userVerified')
                         ->join('locations', 'locations.id = ads.location_id', 'left')
                         ->join('subcategories', 'subcategories.id = ads.subcategory_id', 'left')
                         ->join('categories', 'categories.id = subcategories.category_id', 'left')
                         ->join('brands', 'brands.id = ads.brand_id', 'left')
                         ->join('users', 'users.id_user = ads.user_id', 'left')
                         ->where('ads.id', $id)
                         ->where('ads.status !=', 'deleted')
                         ->where('ads.moderation_status', 'approved')
                         ->where('ads.publication_status', 'published')
                         ->first();

            if (!$ad) {
                return $this->failNotFound('Annonce non trouvée ou supprimée.');
            }

            // Récupération des photos
            $adPhotoModel = new AdPhotoModel();
            $ad['photos'] = $adPhotoModel->where('ad_id', $id)
                                       ->orderBy('display_order', 'ASC')
                                       ->findAll();

            // Récupération des filtres avec leurs détails
            $adFilterValueModel = new AdFilterValueModel();
            $ad['filters'] = $adFilterValueModel->select('ad_filter_values.*, filters.name as filter_name,
                                                        filters.type as filter_type, filters.is_required')
                                               ->join('filters', 'filters.id = ad_filter_values.filter_id')
                                               ->where('ad_filter_values.ad_id', $id)
                                               ->findAll();

            // Formatage des prix
            $ad['price'] = (float) $ad['price'];
            $ad['original_price'] = $ad['original_price'] ? (float) $ad['original_price'] : null;
            
            // Formatage de userVerified
            $ad['userVerified'] = (int) ($ad['userVerified'] ?? 0);

            return $this->respond($ad);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getById: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération de l\'annonce.');
        }
    }

    /**
     * Récupérer les annonces d'un utilisateur spécifique
     * GET /api/ads/user/{userId}
     */
    public function getByUser($userId)
    {
        $adModel = new AdModel();
        $request = $this->request;

        try {
            // Vérifier que l'userId est numérique
            if (!is_numeric($userId)) {
                return $this->failNotFound('ID utilisateur invalide.');
            }

            // Paramètres de pagination
            $page = (int) ($request->getGet('page') ?? 1);
            $perPage = (int) ($request->getGet('per_page') ?? 20);
            $offset = ($page - 1) * $perPage;

            // Paramètres de filtrage supplémentaires
            $filters = [
                'status' => $request->getGet('status') ?? 'active',
                'moderation_status' => $request->getGet('moderation_status'),
                'min_price' => $request->getGet('min_price'),
                'max_price' => $request->getGet('max_price'),
                'search' => $request->getGet('search'),
                'has_discount' => $request->getGet('has_discount'),
            ];

            // Paramètres de filtrage avancés (filtres dynamiques)
            $advancedFilters = [];

            // Récupérer tous les paramètres GET qui commencent par 'filter_'
            $getParams = $request->getGet();
            foreach ($getParams as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value)) {
                    $filterId = str_replace('filter_', '', $key);

                    // Support pour les filtres avec min/max (ex: filter_6_min, filter_6_max)
                    if (strpos($filterId, '_min') !== false) {
                        $cleanFilterId = str_replace('_min', '', $filterId);
                        $advancedFilters[$cleanFilterId]['min'] = $value;
                    } elseif (strpos($filterId, '_max') !== false) {
                        $cleanFilterId = str_replace('_max', '', $filterId);
                        $advancedFilters[$cleanFilterId]['max'] = $value;
                    } else {
                        // Filtre simple avec valeur exacte
                        $advancedFilters[$filterId]['value'] = $value;
                    }
                }
            }

            log_message('error', '[API] AdsController getByUser: Filtres avancés détectés: ' . json_encode($advancedFilters));

            // Paramètres de tri
            $sortBy = $request->getGet('sort_by') ?? 'created_at';
            $sortOrder = $request->getGet('sort_order') ?? 'DESC';

            // Validation des paramètres de tri
            $allowedSortFields = ['created_at', 'updated_at', 'price', 'title', 'view_count'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
                $sortOrder = 'DESC';
            }

            // Construction de la requête pour l'utilisateur spécifique
            $query = $adModel->select('ads.*, locations.city as location_name, locations.region as location_type,
                                      subcategories.name as subcategory_name, categories.name as category_name,
                                      brands.name as brand_name, users.is_verified as userVerified')
                            ->join('locations', 'locations.id = ads.location_id', 'left')
                            ->join('subcategories', 'subcategories.id = ads.subcategory_id', 'left')
                            ->join('categories', 'categories.id = subcategories.category_id', 'left')
                            ->join('brands', 'brands.id = ads.brand_id', 'left')
                            ->join('users', 'users.id_user = ads.user_id', 'left')
                            ->where('ads.user_id', $userId)
                            ->where('ads.status !=', 'deleted');

            // Application des filtres
            if (!empty($filters['status'])) {
                $query->where('ads.status', $filters['status']);
            }

            if (!empty($filters['moderation_status'])) {
                $query->where('ads.moderation_status', $filters['moderation_status']);
            }

            if (!empty($filters['min_price'])) {
                $query->where('ads.price >=', $filters['min_price']);
            }

            if (!empty($filters['max_price'])) {
                $query->where('ads.price <=', $filters['max_price']);
            }

            if (!empty($filters['search'])) {
                $query->groupStart()
                      ->like('ads.title', $filters['search'])
                      ->orLike('ads.description', $filters['search'])
                      ->groupEnd();
            }

            if ($filters['has_discount'] !== null) {
                $query->where('ads.has_discount', (int)$filters['has_discount']);
            }

            // Application des filtres avancés (filtres dynamiques)
            if (!empty($advancedFilters)) {
                log_message('error', '[API] AdsController getByUser: Application des filtres avancés: ' . json_encode($advancedFilters));

                foreach ($advancedFilters as $filterId => $filterConfig) {
                    // Vérifier que le filterId est numérique
                    if (!is_numeric($filterId)) {
                        continue;
                    }

                    // Jointure avec ad_filter_values pour ce filtre spécifique
                    $query->join("ad_filter_values fv{$filterId}", "fv{$filterId}.ad_id = ads.id AND fv{$filterId}.filter_id = {$filterId}", 'left');

                    // Application des conditions selon le type de filtre
                    if (isset($filterConfig['value'])) {
                        // Filtre exact
                        $query->where("fv{$filterId}.value", $filterConfig['value']);
                        log_message('error', '[API] AdsController getByUser: Filtre exact appliqué - filter_' . $filterId . ' = ' . $filterConfig['value']);
                    } elseif (isset($filterConfig['min']) || isset($filterConfig['max'])) {
                        // Filtre par plage (min/max)
                        if (isset($filterConfig['min']) && isset($filterConfig['max'])) {
                            // Les deux valeurs sont définies
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min'])
                                  ->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController getByUser: Filtre plage appliqué - filter_' . $filterId . ' entre ' . $filterConfig['min'] . ' et ' . $filterConfig['max']);
                        } elseif (isset($filterConfig['min'])) {
                            // Seulement min
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min']);
                            log_message('error', '[API] AdsController getByUser: Filtre min appliqué - filter_' . $filterId . ' >= ' . $filterConfig['min']);
                        } elseif (isset($filterConfig['max'])) {
                            // Seulement max
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController getByUser: Filtre max appliqué - filter_' . $filterId . ' <= ' . $filterConfig['max']);
                        }
                    }
                }
            }

            // Comptage total pour la pagination
            $totalQuery = clone $query;
            $total = $totalQuery->countAllResults(false);

            // Application du tri et de la pagination
            $ads = $query->orderBy('ads.' . $sortBy, $sortOrder)
                        ->limit($perPage, $offset)
                        ->findAll();

            // Récupération des photos et filtres pour chaque annonce
            $adPhotoModel = new AdPhotoModel();
            $adFilterValueModel = new AdFilterValueModel();

            foreach ($ads as &$ad) {
                // Photos
                $ad['photos'] = $adPhotoModel->where('ad_id', $ad['id'])
                                           ->where('display_order >=', 0)
                                           ->orderBy('display_order', 'ASC')
                                           ->findAll();

                // Filtres
                $ad['filters'] = $adFilterValueModel->select('ad_filter_values.*, filters.name as filter_name, filters.type as filter_type')
                                                   ->join('filters', 'filters.id = ad_filter_values.filter_id')
                                                   ->where('ad_filter_values.ad_id', $ad['id'])
                                                   ->findAll();

                // Formatage des prix
                $ad['price'] = (float) $ad['price'];
                $ad['original_price'] = $ad['original_price'] ? (float) $ad['original_price'] : null;
                
                // Formatage de userVerified
                $ad['userVerified'] = (int) ($ad['userVerified'] ?? 0);
            }

            // Métadonnées de pagination
            $totalPages = ceil($total / $perPage);
            $pagination = [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'total_pages' => (int) $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null,
            ];

            return $this->respond([
                'ads' => $ads,
                'pagination' => $pagination,
                'filters' => array_filter($filters),
                'user_id' => (int) $userId,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getByUser: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération des annonces de l\'utilisateur.');
        }
    }

    /**
     * Récupérer les annonces d'une catégorie spécifique
     * GET /api/ads/category/{categoryId}
     */
    public function getByCategory($categoryId)
    {
        $adModel = new AdModel();
        $request = $this->request;

        try {
            // Vérifier que le categoryId est numérique
            if (!is_numeric($categoryId)) {
                return $this->failNotFound('ID de catégorie invalide.');
            }

            // Vérifier que la catégorie existe
            $categoryModel = new CategoryModel();
            $category = $categoryModel->find($categoryId);
            if (!$category || !$category['is_active']) {
                return $this->failNotFound('Catégorie non trouvée ou inactive.');
            }

            // Paramètres de pagination
            $page = (int) ($request->getGet('page') ?? 1);
            $perPage = (int) ($request->getGet('per_page') ?? 20);
            $offset = ($page - 1) * $perPage;

            // Paramètres de filtrage supplémentaires
            $filters = [
                'subcategory_id' => $request->getGet('subcategory_id'),
                'location_id' => $request->getGet('location_id'),
                'brand_id' => $request->getGet('brand_id'),
                'status' => $request->getGet('status') ?? 'active',
                'moderation_status' => $request->getGet('moderation_status'),
                'min_price' => $request->getGet('min_price'),
                'max_price' => $request->getGet('max_price'),
                'search' => $request->getGet('search'),
                'has_discount' => $request->getGet('has_discount'),
            ];

            // Paramètres de filtrage avancés (filtres dynamiques)
            $advancedFilters = [];

            // Récupérer tous les paramètres GET qui commencent par 'filter_'
            $getParams = $request->getGet();
            foreach ($getParams as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value)) {
                    $filterId = str_replace('filter_', '', $key);

                    // Support pour les filtres avec min/max (ex: filter_6_min, filter_6_max)
                    if (strpos($filterId, '_min') !== false) {
                        $cleanFilterId = str_replace('_min', '', $filterId);
                        $advancedFilters[$cleanFilterId]['min'] = $value;
                    } elseif (strpos($filterId, '_max') !== false) {
                        $cleanFilterId = str_replace('_max', '', $filterId);
                        $advancedFilters[$cleanFilterId]['max'] = $value;
                    } else {
                        // Filtre simple avec valeur exacte
                        $advancedFilters[$filterId]['value'] = $value;
                    }
                }
            }

            log_message('error', '[API] AdsController getByCategory: Filtres avancés détectés: ' . json_encode($advancedFilters));

            // Paramètres de tri
            $sortBy = $request->getGet('sort_by') ?? 'created_at';
            $sortOrder = $request->getGet('sort_order') ?? 'DESC';

            // Validation des paramètres de tri
            $allowedSortFields = ['created_at', 'updated_at', 'price', 'title', 'view_count'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
                $sortOrder = 'DESC';
            }

            // Construction de la requête pour la catégorie spécifique
            $query = $adModel->select('ads.*, locations.city as location_name, locations.region as location_type,
                                      subcategories.name as subcategory_name, categories.name as category_name,
                                      brands.name as brand_name, users.first_name as seller_username,
                                      users.is_verified as userVerified')
                            ->join('locations', 'locations.id = ads.location_id', 'left')
                            ->join('subcategories', 'subcategories.id = ads.subcategory_id', 'left')
                            ->join('categories', 'categories.id = subcategories.category_id', 'left')
                            ->join('brands', 'brands.id = ads.brand_id', 'left')
                            ->join('users', 'users.id_user = ads.user_id', 'left')
                            ->where('categories.id', $categoryId)
                            ->where('ads.status !=', 'deleted')
                            ->where('ads.moderation_status', 'approved')
                            ->where('ads.publication_status', 'published')
                            ->where('subcategories.is_active', 1)
                            ->where('categories.is_active', 1);

            // Application des filtres supplémentaires
            if (!empty($filters['subcategory_id'])) {
                $query->where('ads.subcategory_id', $filters['subcategory_id']);
            }

            if (!empty($filters['location_id'])) {
                $query->where('ads.location_id', $filters['location_id']);
            }

            if (!empty($filters['brand_id'])) {
                $query->where('ads.brand_id', $filters['brand_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('ads.status', $filters['status']);
            }

            if (!empty($filters['moderation_status'])) {
                $query->where('ads.moderation_status', $filters['moderation_status']);
            }

            if (!empty($filters['min_price'])) {
                $query->where('ads.price >=', $filters['min_price']);
            }

            if (!empty($filters['max_price'])) {
                $query->where('ads.price <=', $filters['max_price']);
            }

            if (!empty($filters['search'])) {
                $query->groupStart()
                      ->like('ads.title', $filters['search'])
                      ->orLike('ads.description', $filters['search'])
                      ->groupEnd();
            }

            if ($filters['has_discount'] !== null) {
                $query->where('ads.has_discount', (int)$filters['has_discount']);
            }

            // Application des filtres avancés (filtres dynamiques)
            if (!empty($advancedFilters)) {
                log_message('error', '[API] AdsController getByCategory: Application des filtres avancés: ' . json_encode($advancedFilters));

                foreach ($advancedFilters as $filterId => $filterConfig) {
                    // Vérifier que le filterId est numérique
                    if (!is_numeric($filterId)) {
                        continue;
                    }

                    // Jointure avec ad_filter_values pour ce filtre spécifique
                    $query->join("ad_filter_values fv{$filterId}", "fv{$filterId}.ad_id = ads.id AND fv{$filterId}.filter_id = {$filterId}", 'left');

                    // Application des conditions selon le type de filtre
                    if (isset($filterConfig['value'])) {
                        // Filtre exact
                        $query->where("fv{$filterId}.value", $filterConfig['value']);
                        log_message('error', '[API] AdsController getByCategory: Filtre exact appliqué - filter_' . $filterId . ' = ' . $filterConfig['value']);
                    } elseif (isset($filterConfig['min']) || isset($filterConfig['max'])) {
                        // Filtre par plage (min/max)
                        if (isset($filterConfig['min']) && isset($filterConfig['max'])) {
                            // Les deux valeurs sont définies
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min'])
                                  ->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController getByCategory: Filtre plage appliqué - filter_' . $filterId . ' entre ' . $filterConfig['min'] . ' et ' . $filterConfig['max']);
                        } elseif (isset($filterConfig['min'])) {
                            // Seulement min
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min']);
                            log_message('error', '[API] AdsController getByCategory: Filtre min appliqué - filter_' . $filterId . ' >= ' . $filterConfig['min']);
                        } elseif (isset($filterConfig['max'])) {
                            // Seulement max
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController getByCategory: Filtre max appliqué - filter_' . $filterId . ' <= ' . $filterConfig['max']);
                        }
                    }
                }
            }

            // Comptage total pour la pagination
            $totalQuery = clone $query;
            $total = $totalQuery->countAllResults(false);

            // Application du tri avec priorité pour les annonces boostées (getByCategory)
            // 1. Annonces boostées actives en premier (is_boosted = 1 ET boost_end >= NOW())
            // 2. Ensuite le tri demandé par l'utilisateur
            $ads = $query->orderBy('CASE WHEN ads.is_boosted = 1 AND ads.boost_end >= NOW() THEN 0 ELSE 1 END', 'ASC')
                        ->orderBy('ads.' . $sortBy, $sortOrder)
                        ->limit($perPage, $offset)
                        ->findAll();

            // Récupération des photos et filtres pour chaque annonce
            $adPhotoModel = new AdPhotoModel();
            $adFilterValueModel = new AdFilterValueModel();

            foreach ($ads as &$ad) {
                // Photos
                $ad['photos'] = $adPhotoModel->where('ad_id', $ad['id'])
                                           ->where('display_order >=', 0)
                                           ->orderBy('display_order', 'ASC')
                                           ->findAll();

                // Filtres
                $ad['filters'] = $adFilterValueModel->select('ad_filter_values.*, filters.name as filter_name, filters.type as filter_type')
                                                   ->join('filters', 'filters.id = ad_filter_values.filter_id')
                                                   ->where('ad_filter_values.ad_id', $ad['id'])
                                                   ->findAll();

                // Formatage des prix
                $ad['price'] = (float) $ad['price'];
                $ad['original_price'] = $ad['original_price'] ? (float) $ad['original_price'] : null;
                
                // Formatage de userVerified
                $ad['userVerified'] = (int) ($ad['userVerified'] ?? 0);
            }

            // Métadonnées de pagination
            $totalPages = ceil($total / $perPage);
            $pagination = [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'total_pages' => (int) $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null,
            ];

            return $this->respond([
                'ads' => $ads,
                'pagination' => $pagination,
                'filters' => array_filter($filters),
                'category' => $category,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getByCategory: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération des annonces de la catégorie.');
        }
    }

    /**
     * Récupérer les annonces d'une sous-catégorie spécifique
     * GET /api/ads/subcategory/{subcategoryId}
     */
    public function getBySubcategory($subcategoryId)
    {
        $adModel = new AdModel();
        $request = $this->request;

        try {
            $subcategoryModel = new SubcategoryModel();
            if (is_numeric($subcategoryId)) {
                $subcategory = $subcategoryModel->find($subcategoryId);
                log_message('error', '[API] AdsController getBySubcategory: Recherche par ID: ' . $subcategoryId);
            } else {
                $subcategory = $subcategoryModel->where('slug', $subcategoryId)->first();
                log_message('error', '[API] AdsController getBySubcategory: Recherche par slug: ' . $subcategoryId);
            }
            
            log_message('error', '[API] AdsController getBySubcategory: Sous-catégorie trouvée: ' . json_encode($subcategory));
            
            if (!$subcategory || !$subcategory['is_active']) {
                log_message('error', '[API] AdsController getBySubcategory: Sous-catégorie non trouvée ou inactive');
                return $this->failNotFound('Sous-catégorie non trouvée ou inactive.');
            }
            $subcategoryId = $subcategory['id'];

            // Paramètres de pagination
            $page = (int) ($request->getGet('page') ?? 1);
            $perPage = (int) ($request->getGet('per_page') ?? 20);
            $offset = ($page - 1) * $perPage;

            // Paramètres de filtrage supplémentaires
            $filters = [
                'location_id' => $request->getGet('location_id'),
                'brand_id' => $request->getGet('brand_id'),
                'status' => $request->getGet('status') ?? 'active',
                'moderation_status' => $request->getGet('moderation_status'),
                'min_price' => $request->getGet('min_price'),
                'max_price' => $request->getGet('max_price'),
                'search' => $request->getGet('search'),
                'has_discount' => $request->getGet('has_discount'),
            ];

            // Paramètres de filtrage avancés (filtres dynamiques)
            $advancedFilters = [];

            // Récupérer tous les paramètres GET qui commencent par 'filter_'
            $getParams = $request->getGet();
            foreach ($getParams as $key => $value) {
                if (strpos($key, 'filter_') === 0 && !empty($value)) {
                    $filterId = str_replace('filter_', '', $key);

                    // Support pour les filtres avec min/max (ex: filter_6_min, filter_6_max)
                    if (strpos($filterId, '_min') !== false) {
                        $cleanFilterId = str_replace('_min', '', $filterId);
                        $advancedFilters[$cleanFilterId]['min'] = $value;
                    } elseif (strpos($filterId, '_max') !== false) {
                        $cleanFilterId = str_replace('_max', '', $filterId);
                        $advancedFilters[$cleanFilterId]['max'] = $value;
                    } else {
                        // Filtre simple avec valeur exacte
                        $advancedFilters[$filterId]['value'] = $value;
                    }

	    /* public function updateAd($id = null)
	    {
	        log_message('error', '[API] AdsController updateAd: ==== DÉBUT MISE À JOUR ANNONCE (form-data friendly) ====');

	        try {
	            // 1) Récupérer l'annonce et vérifier l'appartenance
	            $adModel = new AdModel();
	            $whereCondition = is_numeric($id) ? ['id' => $id] : ['slug' => $id, 'status !=' => 'deleted'];
	            $existingAd = $adModel->where($whereCondition)->first();

	            if (!$existingAd) {
	                return $this->failNotFound('Annonce non trouvée.');
	            }

	            // 2) Authentification et permission
	            $user = $this->request->user ?? null;
	            $userId = null;
	            if (is_array($user)) {
	                if (isset($user['user_id'])) {
	                    $userId = $user['user_id'];
	                } elseif (isset($user['id'])) {
	                    $userId = $user['id'];
	                }
	            } elseif (is_object($user)) {
	                if (isset($user->user_id)) {
	                    $userId = $user->user_id;
	                } elseif (isset($user->id)) {
	                    $userId = $user->id;
	                }
	            }

	            if (!$userId) {
	                return $this->failUnauthorized('Utilisateur non authentifié.');
	            }

	            if ((int) $existingAd['user_id'] !== (int) $userId) {
	                return $this->failForbidden('Vous n\'avez pas les permissions pour modifier cette annonce.');
	            }

	            // 3) Récupérer toutes les données (POST, JSON, RAW) + fichiers
	            $postData = $this->request->getPost();
	            $jsonData = null;
	            $rawData = $this->request->getRawInput();
	            try {
	                $jsonData = $this->request->getJSON(true);
	                log_message('error', '[API] AdsController updateAd: JSON parsé avec succès');
	            } catch (\Exception $e) {
	                log_message('error', '[API] AdsController updateAd: ERREUR JSON: ' . $e->getMessage());
	                if (!empty($rawData)) {
	                    $rawDataString = is_string($rawData) ? $rawData : json_encode($rawData);
	                    $manualJson = json_decode($rawDataString, true);
	                    if (json_last_error() === JSON_ERROR_NONE) {
	                        $jsonData = $manualJson;
	                        log_message('error', '[API] AdsController updateAd: JSON récupéré manuellement');
	                    }
	                }
	            }

	            $allData = [];
	            if (!empty($postData)) {
	                $allData = array_merge($allData, $postData);
	                log_message('error', '[API] AdsController updateAd: Données POST intégrées');
	            }
	            if (!empty($jsonData)) {
	                $allData = array_merge($allData, $jsonData);
	                log_message('error', '[API] AdsController updateAd: Données JSON intégrées');
	            }

	            $uploadedFiles = $this->request->getFiles();
	            log_message('error', '[API] AdsController updateAd: Données finales utilisées: ' . json_encode($allData));
	            log_message('error', '[API] AdsController updateAd: Fichiers reçus: ' . json_encode(array_keys($uploadedFiles)));

	            // 4) Analyse des filtres (mêmes règles souples que create())
	            log_message('error', '[API] AdsController updateAd: ==== ANALYSE DES FILTRES ====');
	            $filtersFound = [];

	            // a) Parcours de toutes les clés pour détecter filter_*, array filters, et clés numériques
	            $potentialFilters = [];
	            foreach ($allData as $key => $value) {
	                if (strpos($key, 'filter_') === 0) {
	                    $potentialFilters['method1'][$key] = $value;
	                } elseif (is_numeric($key)) {
	                    $potentialFilters['method3'][$key] = $value;
	                } elseif ($key === 'filters' && is_array($value)) {
	                    $potentialFilters['method2'] = $value;
	                }
	            }

	            // Méthode 1: filter_{id} => value
	            if (isset($potentialFilters['method1'])) {
	                foreach ($potentialFilters['method1'] as $k => $v) {
	                    $filterId = (int) str_replace('filter_', '', $k);
	                    if ($filterId > 0 && !isset($filtersFound[$filterId])) {
	                        $filtersFound[$filterId] = $v;
	                    }
	                }
	            }

	            // Méthode 2: filters[filter_id] = value
	            if (isset($allData['filters']) && is_array($allData['filters'])) {
	                foreach ($allData['filters'] as $filterId => $value) {
	                    if (is_numeric($filterId)) {
	                        $filtersFound[(int) $filterId] = is_array($value) ? implode(',', $value) : $value;
	                    }
	                }
	            }

	            // Méthode 3: clés numériques brutes (si elles correspondent à des filtres existants)
	            if (isset($potentialFilters['method3'])) {
	                try {
	                    $filterModel = new \App\Models\FilterModel();
	                    $allFiltersInDb = $filterModel->findAll();
	                    $existingFilterIds = array_column($allFiltersInDb, 'id');
	                    foreach ($potentialFilters['method3'] as $numericKey => $value) {
	                        if (in_array((int) $numericKey, $existingFilterIds) && !isset($filtersFound[(int) $numericKey])) {
	                            $filtersFound[(int) $numericKey] = $value;
	                        }
	                    }
	                } catch (\Exception $e) {
	                    log_message('error', '[API] AdsController updateAd: ERREUR accès FilterModel: ' . $e->getMessage());
	                }
	            }

	            // Méthode 4: filter_values flexible (objet, array, string JSON)
	            if (isset($allData['filter_values'])) {
	                $filterValuesData = $allData['filter_values'];
	                if (is_array($filterValuesData)) {
	                    foreach ($filterValuesData as $index => $filterItem) {
	                        $this->processFilterItem($filterItem, $filtersFound, $index);
	                    }
	                } elseif (is_string($filterValuesData)) {
	                    $parsed = json_decode($filterValuesData, true);
	                    if (json_last_error() === JSON_ERROR_NONE) {
	                        if (isset($parsed[0]) && is_array($parsed)) {
	                            foreach ($parsed as $index => $filterItem) {
	                                $this->processFilterItem($filterItem, $filtersFound, $index);
	                            }
	                        } else {
	                            $this->processFilterItem($parsed, $filtersFound, 0);
	                        }
	                    }
	                } elseif (is_object($filterValuesData)) {
	                    $this->processFilterItem((array) $filterValuesData, $filtersFound, 0);
	                }
	            }

	            // 5) Validation (similaire à update(), avec validation conditionnelle des photos)
	            $rules = [
	                'title'               => 'permit_empty|string|max_length[150]',
	                'description'         => 'permit_empty|string|max_length[2000]',
	                'price'               => 'permit_empty|numeric|greater_than[0]',
	                'original_price'      => 'permit_empty|numeric|greater_than[0]',
	                'discount_percentage' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
	                'has_discount'        => 'permit_empty|in_list[0,1]',
	                'is_negotiable'       => 'permit_empty|in_list[0,1]',
	                'referral_code'       => 'permit_empty|string|max_length[50]',
	                'subcategory_id'      => 'permit_empty|integer|greater_than[0]|is_not_unique[subcategories.id]',
	                'location_id'         => 'permit_empty|integer|greater_than[0]|is_not_unique[locations.id]',
	                'brand_id'            => 'permit_empty|integer|is_not_unique[brands.id]',
	                'status'              => 'permit_empty|in_list[active,inactive]',
	                'photos'              => 'permit_empty',
	            ];

	            if (isset($uploadedFiles['photos'])) {
	                $rules['photos'] = 'uploaded[photos]|max_size[photos,2048]|ext_in[photos,png,jpg,jpeg,webp]';
	            }

	            if (!$this->validate($rules, $allData)) {
	                return $this->failValidationErrors($this->validator->getErrors());
	            }

	            // 6) Transaction et mise à jour des champs
	            $db = \Config\Database::connect();
	            $db->transStart();

	            $updateData = [];
	            if (isset($allData['title']) && !empty(trim($allData['title']))) {
	                $updateData['title'] = trim($allData['title']);
	                $newSlug = generate_ad_slug($allData['title']);
	                $updateData['slug'] = $newSlug;
	            }
	            if (isset($allData['description'])) {
	                $updateData['description'] = trim($allData['description']);
	            }
	            if (isset($allData['price'])) {
	                $updateData['price'] = (float) $allData['price'];
	            }
	            if (isset($allData['original_price'])) {
	                $updateData['original_price'] = !empty($allData['original_price']) ? (float) $allData['original_price'] : null;
	            }
	            if (isset($allData['discount_percentage'])) {
	                $updateData['discount_percentage'] = !empty($allData['discount_percentage']) ? (int) $allData['discount_percentage'] : null;
	            }
	            if (isset($allData['has_discount'])) {
	                $updateData['has_discount'] = (int) $allData['has_discount'];
	            }
	            if (isset($allData['is_negotiable'])) {
	                $updateData['is_negotiable'] = (int) $allData['is_negotiable'];
	            }
	            if (isset($allData['referral_code'])) {
	                $updateData['referral_code'] = !empty($allData['referral_code']) ? trim($allData['referral_code']) : null;
	            }
	            if (isset($allData['subcategory_id'])) {
	                $updateData['subcategory_id'] = (int) $allData['subcategory_id'];
	            }
	            if (isset($allData['location_id'])) {
	                $updateData['location_id'] = (int) $allData['location_id'];
	            }
	            if (isset($allData['brand_id'])) {
	                $updateData['brand_id'] = !empty($allData['brand_id']) ? (int) $allData['brand_id'] : null;
	            }
	            if (isset($allData['status'])) {
	                $updateData['status'] = $allData['status'];
	            }
	            $updateData['updated_at'] = date('Y-m-d H:i:s');

	            if (!empty($updateData)) {
	                $result = $adModel->update($existingAd['id'], $updateData);
	                if (!$result) {
	                    $db->transRollback();
	                    return $this->failServerError('Échec de la mise à jour de l\'annonce.');
	                }
	            }

	            // 7) Mise à jour des filtres
	            if (!empty($filtersFound)) {
	                $adFilterValueModel = new AdFilterValueModel();
	                // Supprimer les anciens filtres
	                $adFilterValueModel->where('ad_id', $existingAd['id'])->delete();
	                // Réinsérer
	                foreach ($filtersFound as $filterId => $value) {
	                    if (!is_numeric($filterId)) {
	                        continue;
	                    }
	                    $valueToStore = is_array($value) ? implode(',', $value) : (string) $value;
	                    if ($valueToStore === '') {
	                        continue;
	                    }
	                    $adFilterValueModel->insertFilterValue([
	                        'ad_id' => (int) $existingAd['id'],
	                        'filter_id' => (int) $filterId,
	                        'value' => $valueToStore,
	                    ]);
	                }
	            }

	            // 8) Gestion des photos ajoutées (on ajoute en plus des existantes)
	            if (isset($uploadedFiles['photos']) && is_array($uploadedFiles['photos'])) {
	                $adPhotoModel = new AdPhotoModel();
	                $uploadService = new UploadService();

	                foreach ($uploadedFiles['photos'] as $order => $img) {
	                    if ($img && $img->isValid() && !$img->hasMoved()) {
	                        $uploadResult = $uploadService->upload("photos.{$order}", 'uploads/ads');
	                        if ($uploadResult['success']) {
	                            $filename = basename($uploadResult['path']);
	                            $relativePath = '/uploads/ads/' . $filename;
	                            $adPhotoModel->insertPhoto([
	                                'ad_id' => (int) $existingAd['id'],
	                                'original_url' => $relativePath,
	                                'thumbnail_url' => null,
	                                'display_order' => (int) $order,
	                                'alt_text' => 'Photo ' . ($order + 1),
	                            ]);
	                        }
	                    }
	                }
	            }

	            // 9) Commit et réponse
	            $db->transCommit();
	            $updatedAd = $adModel->find($existingAd['id']);
	            log_message('error', '[API] AdsController updateAd: ==== FIN MISE À JOUR ANNONCE ====');
	            return $this->respondUpdated([
	                'ad' => $updatedAd,
	                'message' => 'Annonce mise à jour avec succès.'
	            ]);

	        } catch (\Exception $e) {
	            log_message('error', '[API] AdsController updateAd: ' . $e->getMessage());
	            return $this->failServerError('Une erreur est survenue lors de la mise à jour de l\'annonce.');
	        }
	    }*/
                }
            }

            log_message('error', '[API] AdsController getBySubcategory: Filtres avancés détectés: ' . json_encode($advancedFilters));

            // Paramètres de tri
            $sortBy = $request->getGet('sort_by') ?? 'created_at';
            $sortOrder = $request->getGet('sort_order') ?? 'DESC';

            // Validation des paramètres de tri
            $allowedSortFields = ['created_at', 'updated_at', 'price', 'title', 'view_count'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
                $sortOrder = 'DESC';
            }

            // Construction de la requête pour la sous-catégorie spécifique
            $query = $adModel->select('ads.*, locations.city as location_name, locations.region as location_type,
                                      subcategories.name as subcategory_name, categories.name as category_name,
                                      brands.name as brand_name, users.first_name as seller_username,
                                      users.is_verified as userVerified')
                            ->join('locations', 'locations.id = ads.location_id', 'left')
                            ->join('subcategories', 'subcategories.id = ads.subcategory_id', 'left')
                            ->join('categories', 'categories.id = subcategories.category_id', 'left')
                            ->join('brands', 'brands.id = ads.brand_id', 'left')
                            ->join('users', 'users.id_user = ads.user_id', 'left')
                            ->where('ads.subcategory_id', $subcategoryId)
                            ->where('ads.status !=', 'deleted')
                            ->where('ads.moderation_status', 'approved')
                            ->where('ads.publication_status', 'published')
                            ->where('subcategories.is_active', 1)
                            ->where('categories.is_active', 1);

            // Application des filtres supplémentaires
            if (!empty($filters['location_id'])) {
                $query->where('ads.location_id', $filters['location_id']);
            }

            if (!empty($filters['brand_id'])) {
                $query->where('ads.brand_id', $filters['brand_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('ads.status', $filters['status']);
            }

            if (!empty($filters['moderation_status'])) {
                $query->where('ads.moderation_status', $filters['moderation_status']);
            }

            if (!empty($filters['min_price'])) {
                $query->where('ads.price >=', $filters['min_price']);
            }

            if (!empty($filters['max_price'])) {
                $query->where('ads.price <=', $filters['max_price']);
            }

            if (!empty($filters['search'])) {
                $query->groupStart()
                      ->like('ads.title', $filters['search'])
                      ->orLike('ads.description', $filters['search'])
                      ->groupEnd();
            }

            if ($filters['has_discount'] !== null) {
                $query->where('ads.has_discount', (int)$filters['has_discount']);
            }

            // Application des filtres avancés (filtres dynamiques)
            if (!empty($advancedFilters)) {
                log_message('error', '[API] AdsController getBySubcategory: Application des filtres avancés: ' . json_encode($advancedFilters));

                foreach ($advancedFilters as $filterId => $filterConfig) {
                    // Vérifier que le filterId est numérique
                    if (!is_numeric($filterId)) {
                        continue;
                    }

                    // Jointure avec ad_filter_values pour ce filtre spécifique
                    $query->join("ad_filter_values fv{$filterId}", "fv{$filterId}.ad_id = ads.id AND fv{$filterId}.filter_id = {$filterId}", 'left');

                    // Application des conditions selon le type de filtre
                    if (isset($filterConfig['value'])) {
                        // Filtre exact
                        $query->where("fv{$filterId}.value", $filterConfig['value']);
                        log_message('error', '[API] AdsController getBySubcategory: Filtre exact appliqué - filter_' . $filterId . ' = ' . $filterConfig['value']);
                    } elseif (isset($filterConfig['min']) || isset($filterConfig['max'])) {
                        // Filtre par plage (min/max)
                        if (isset($filterConfig['min']) && isset($filterConfig['max'])) {
                            // Les deux valeurs sont définies
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min'])
                                  ->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController getBySubcategory: Filtre plage appliqué - filter_' . $filterId . ' entre ' . $filterConfig['min'] . ' et ' . $filterConfig['max']);
                        } elseif (isset($filterConfig['min'])) {
                            // Seulement min
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) >=", (float)$filterConfig['min']);
                            log_message('error', '[API] AdsController getBySubcategory: Filtre min appliqué - filter_' . $filterId . ' >= ' . $filterConfig['min']);
                        } elseif (isset($filterConfig['max'])) {
                            // Seulement max
                            $query->where("CAST(fv{$filterId}.value AS DECIMAL) <=", (float)$filterConfig['max']);
                            log_message('error', '[API] AdsController getBySubcategory: Filtre max appliqué - filter_' . $filterId . ' <= ' . $filterConfig['max']);
                        }
                    }
                }
            }

            // Comptage total pour la pagination
            $totalQuery = clone $query;
            $total = $totalQuery->countAllResults(false);

            // Application du tri avec priorité pour les annonces boostées (getBySubcategory)
            // 1. Annonces boostées actives en premier (is_boosted = 1 ET boost_end >= NOW())
            // 2. Ensuite le tri demandé par l'utilisateur
            $ads = $query->orderBy('CASE WHEN ads.is_boosted = 1 AND ads.boost_end >= NOW() THEN 0 ELSE 1 END', 'ASC')
                        ->orderBy('ads.' . $sortBy, $sortOrder)
                        ->limit($perPage, $offset)
                        ->findAll();

            // Récupération des photos et filtres pour chaque annonce
            $adPhotoModel = new AdPhotoModel();
            $adFilterValueModel = new AdFilterValueModel();

            foreach ($ads as &$ad) {
                // Photos
                $ad['photos'] = $adPhotoModel->where('ad_id', $ad['id'])
                                           ->where('display_order >=', 0)
                                           ->orderBy('display_order', 'ASC')
                                           ->findAll();

                // Filtres
                $ad['filters'] = $adFilterValueModel->select('ad_filter_values.*, filters.name as filter_name, filters.type as filter_type')
                                                   ->join('filters', 'filters.id = ad_filter_values.filter_id')
                                                   ->where('ad_filter_values.ad_id', $ad['id'])
                                                   ->findAll();

                // Formatage des prix
                $ad['price'] = (float) $ad['price'];
                $ad['original_price'] = $ad['original_price'] ? (float) $ad['original_price'] : null;
                
                // Formatage de userVerified
                $ad['userVerified'] = (int) ($ad['userVerified'] ?? 0);
            }

            // Métadonnées de pagination
            $totalPages = ceil($total / $perPage);
            $pagination = [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
                'total_pages' => (int) $totalPages,
                'has_next' => $page < $totalPages,
                'has_previous' => $page > 1,
                'next_page' => $page < $totalPages ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null,
            ];

            return $this->respond([
                'ads' => $ads,
                'pagination' => $pagination,
                'filters' => array_filter($filters),
                'subcategory' => $subcategory,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getBySubcategory: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération des annonces de la sous-catégorie.');
        }
    }

    public function updateAd(string $slug = null)
    {
        return $this->update($slug);
    }

    /**
     * Obtenir les statistiques de vues pour un utilisateur
     * GET /api/ads/user/{userId}/views-stats
     * Retourne le nombre total de vues de toutes les annonces de l'utilisateur
     */
    public function getUserViewsStats($userId)
    {
        try {
            // Vérifier que l'userId est numérique
            if (!is_numeric($userId)) {
                return $this->failNotFound('ID utilisateur invalide.');
            }

            $adModel = new AdModel();

            // Obtenir le total des vues de toutes les annonces de l'utilisateur
            $stats = $adModel->select('
                COUNT(ads.id) as total_ads,
                SUM(ads.view_count) as total_views,
                AVG(ads.view_count) as avg_views,
                MAX(ads.view_count) as max_views,
                MIN(ads.view_count) as min_views
            ')
            ->where('ads.user_id', (int)$userId)
            ->where('ads.status !=', 'deleted')
            ->first();

            // Récupérer aussi les annonces avec le plus de vues
            $topAds = $adModel->select('ads.id, ads.title, ads.slug, ads.price, ads.view_count, ads.created_at')
                              ->where('ads.user_id', (int)$userId)
                              ->where('ads.status !=', 'deleted')
                              ->orderBy('ads.view_count', 'DESC')
                              ->limit(10)
                              ->findAll();

            // Calculer les statistiques détaillées
            $totalAds = (int)($stats['total_ads'] ?? 0);
            $totalViews = (int)($stats['total_views'] ?? 0);
            $avgViews = $totalAds > 0 ? round($stats['avg_views'] ?? 0, 2) : 0;
            $maxViews = (int)($stats['max_views'] ?? 0);
            $minViews = (int)($stats['min_views'] ?? 0);

            // Compter les annonces sans vues
            $adsWithoutViews = $adModel->select('COUNT(ads.id) as count')
                                       ->where('ads.user_id', (int)$userId)
                                       ->where('ads.view_count', 0)
                                       ->where('ads.status !=', 'deleted')
                                       ->first();
            $adsWithoutViewsCount = (int)($adsWithoutViews['count'] ?? 0);

            return $this->respond([
                'user_id' => (int)$userId,
                'stats' => [
                    'total_views' => $totalViews,
                    'total_ads' => $totalAds,
                    'ads_with_views' => $totalAds - $adsWithoutViewsCount,
                    'ads_without_views' => $adsWithoutViewsCount,
                    'average_views_per_ad' => $avgViews,
                    'max_views' => $maxViews,
                    'min_views' => $minViews,
                ],
                'top_ads' => $topAds
            ], 'Statistiques de vues récupérées avec succès.');

        } catch (\Exception $e) {
            log_message('error', '[API] AdsController getUserViewsStats: ' . $e->getMessage());
            return $this->failServerError('Une erreur est survenue lors de la récupération des statistiques de vues.');
        }
    }
}
