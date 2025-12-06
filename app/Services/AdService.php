<?php

namespace App\Services;

use App\Models\AdModel;
use App\Models\AdPhotoModel;
use App\Models\AdPromotionModel;
use App\Models\AdFilterValueModel;
use App\Models\CategoryModel;
use App\Models\SubcategoryModel;
use App\Models\LocationModel;
use App\Models\BrandModel;
use App\Entities\AdEntity;

class AdService
{
    protected $adModel;
    protected $adPhotoModel;
    protected $adPromotionModel;
    protected $adFilterValueModel;
    protected $categoryModel;
    protected $subcategoryModel;
    protected $locationModel;
    protected $brandModel;
    protected $uploadService;
    protected $userService;

    public function __construct()
    {
        $this->adModel = new AdModel();
        $this->adPhotoModel = new AdPhotoModel();
        $this->adPromotionModel = new AdPromotionModel();
        $this->adFilterValueModel = new AdFilterValueModel();
        $this->categoryModel = new CategoryModel();
        $this->subcategoryModel = new SubcategoryModel();
        $this->locationModel = new LocationModel();
        $this->brandModel = new BrandModel();
        $this->uploadService = service('uploadService');
        $this->userService = service('userService');
    }

    /**
     * Créer une nouvelle annonce
     */
    public function createAd(array $data, int $userId): int
    {
        // Validation des données de base
        $this->validateAdData($data);

        // Générer le slug
        $data['slug'] = $this->generateSlug($data['title']);
        $data['user_id'] = $userId;
        $data['status'] = 'active';
        $data['moderation_status'] = 'pending';
        $data['view_count'] = 0;

        // Définir la date d'expiration (30 jours par défaut)
        if (!isset($data['expires_at'])) {
            $data['expires_at'] = date('Y-m-d H:i:s', strtotime('+30 days'));
        }

        $adId = $this->adModel->insert($data, true);

        if (!$adId) {
            throw new \RuntimeException('Erreur lors de la création de l\'annonce');
        }

        // Traiter les photos si présentes
        if (isset($data['photos']) && is_array($data['photos'])) {
            $this->processAdPhotos($adId, $data['photos']);
        }

        // Traiter les valeurs de filtres si présentes
        if (isset($data['filters']) && is_array($data['filters'])) {
            $this->processAdFilters($adId, $data['filters']);
        }

        return $adId;
    }

    /**
     * Mettre à jour une annonce
     */
    public function updateAd(int $adId, array $data, int $userId): bool
    {
        $ad = $this->adModel->find($adId);

        if (!$ad) {
            throw new \RuntimeException('Annonce non trouvée');
        }

        if ($ad->user_id !== $userId) {
            throw new \RuntimeException('Accès non autorisé');
        }

        // Validation des données
        $this->validateAdData($data, true);

        // Régénérer le slug si le titre a changé
        if (isset($data['title']) && $data['title'] !== $ad->title) {
            $data['slug'] = $this->generateSlug($data['title'], $adId);
        }

        // Remettre en statut pending si modération nécessaire
        if ($this->requiresModeration($data)) {
            $data['moderation_status'] = 'pending';
        }

        $success = $this->adModel->update($adId, $data);

        if ($success) {
            // Traiter les nouvelles photos
            if (isset($data['photos']) && is_array($data['photos'])) {
                $this->processAdPhotos($adId, $data['photos']);
            }

            // Mettre à jour les filtres
            if (isset($data['filters']) && is_array($data['filters'])) {
                $this->updateAdFilters($adId, $data['filters']);
            }
        }

        return $success;
    }

    /**
     * Récupérer une annonce avec toutes ses données
     */
    public function getAd(int $adId, ?int $userId = null): ?array
    {
        $ad = $this->adModel->find($adId);

        if (!$ad) {
            return null;
        }

        // Incrémenter le compteur de vues (sauf pour le propriétaire)
        if ($userId !== $ad->user_id) {
            $this->incrementViewCount($adId);
        }

        $adData = $ad->toArray();

        // Ajouter les données liées
        $adData['photos'] = $this->adPhotoModel->getByAd($adId);
        $adData['promotions'] = $this->adPromotionModel->getActiveByAd($adId);
        $adData['filters'] = $this->adFilterValueModel->getByAd($adId);
        $adData['category'] = $this->categoryModel->find($ad->subcategory_id);
        $adData['subcategory'] = $this->subcategoryModel->find($ad->subcategory_id);
        $adData['location'] = $this->locationModel->find($ad->location_id);

        if ($ad->brand_id) {
            $adData['brand'] = $this->brandModel->find($ad->brand_id);
        }

        return $adData;
    }

    /**
     * Rechercher des annonces
     */
    public function searchAds(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->adModel->builder();

        // Filtres de base
        $builder->where('status', 'active')
                ->where('moderation_status', 'approved')
                ->where('expires_at >', date('Y-m-d H:i:s'));

        // Recherche textuelle
        if (!empty($filters['query'])) {
            $builder->groupStart()
                    ->like('title', $filters['query'])
                    ->orLike('description', $filters['query'])
                    ->groupEnd();
        }

        // Filtres par catégorie
        if (!empty($filters['category_id'])) {
            $subcategories = $this->subcategoryModel->where('category_id', $filters['category_id'])
                                                   ->findColumn('id');
            if ($subcategories) {
                $builder->whereIn('subcategory_id', $subcategories);
            }
        }

        if (!empty($filters['subcategory_id'])) {
            $builder->where('subcategory_id', $filters['subcategory_id']);
        }

        // Filtres par localisation
        if (!empty($filters['location_id'])) {
            $builder->where('location_id', $filters['location_id']);
        }

        // Filtres par prix
        if (!empty($filters['min_price'])) {
            $builder->where('price >=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $builder->where('price <=', $filters['max_price']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'DESC';

        $allowedSortFields = ['created_at', 'price', 'view_count', 'title'];
        if (in_array($sortBy, $allowedSortFields)) {
            $builder->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $total = $builder->countAllResults(false);
        $ads = $builder->limit($limit, $offset)->get()->getResult();

        return [
            'ads' => $ads,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Supprimer une annonce
     */
    public function deleteAd(int $adId, int $userId): bool
    {
        $ad = $this->adModel->find($adId);

        if (!$ad) {
            throw new \RuntimeException('Annonce non trouvée');
        }

        if ($ad->user_id !== $userId) {
            throw new \RuntimeException('Accès non autorisé');
        }

        // Supprimer les données liées
        $this->adPhotoModel->deleteByAd($adId);
        $this->adFilterValueModel->deleteByAd($adId);

        return $this->adModel->delete($adId);
    }

    /**
     * Approuver une annonce (admin)
     */
    public function approveAd(int $adId, int $moderatorId, ?string $notes = null): bool
    {
        return $this->adModel->update($adId, [
            'moderation_status' => 'approved',
            'moderator_id' => $moderatorId,
            'moderation_notes' => $notes,
            'moderated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Rejeter une annonce (admin)
     */
    public function rejectAd(int $adId, int $moderatorId, string $notes): bool
    {
        return $this->adModel->update($adId, [
            'moderation_status' => 'rejected',
            'moderator_id' => $moderatorId,
            'moderation_notes' => $notes,
            'moderated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Incrémenter le compteur de vues
     */
    private function incrementViewCount(int $adId): void
    {
        $this->adModel->set('view_count', 'view_count + 1', false)
                     ->where('id', $adId)
                     ->update();
    }

    /**
     * Traiter l'upload des photos d'une annonce
     */
    private function processAdPhotos(int $adId, array $photos): void
    {
        $displayOrder = $this->adPhotoModel->countByAd($adId);

        foreach ($photos as $photo) {
            if ($photo instanceof \CodeIgniter\HTTP\Files\UploadedFile && $photo->isValid()) {
                $photoData = $this->uploadService->uploadAdPhoto($photo);
                $photoData['ad_id'] = $adId;
                $photoData['display_order'] = $displayOrder++;

                $this->adPhotoModel->insert($photoData);
            }
        }
    }

    /**
     * Traiter les valeurs de filtres d'une annonce
     */
    private function processAdFilters(int $adId, array $filters): void
    {
        foreach ($filters as $filterId => $value) {
            $this->adFilterValueModel->upsert($adId, $filterId, $value);
        }
    }

    /**
     * Mettre à jour les valeurs de filtres
     */
    private function updateAdFilters(int $adId, array $filters): void
    {
        // Supprimer les anciennes valeurs
        $this->adFilterValueModel->deleteByAd($adId);

        // Ajouter les nouvelles
        $this->processAdFilters($adId, $filters);
    }

    /**
     * Valider les données d'une annonce
     */
    private function validateAdData(array $data, bool $isUpdate = false): void
    {
        $required = ['title', 'subcategory_id', 'location_id'];

        foreach ($required as $field) {
            if (!$isUpdate || isset($data[$field])) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("Le champ {$field} est obligatoire");
                }
            }
        }

        // Validation des relations
        if (isset($data['subcategory_id']) && !$this->subcategoryModel->find($data['subcategory_id'])) {
            throw new \InvalidArgumentException('Sous-catégorie non trouvée');
        }

        if (isset($data['location_id']) && !$this->locationModel->find($data['location_id'])) {
            throw new \InvalidArgumentException('Localisation non trouvée');
        }

        if (isset($data['brand_id']) && $data['brand_id'] && !$this->brandModel->find($data['brand_id'])) {
            throw new \InvalidArgumentException('Marque non trouvée');
        }
    }

    /**
     * Vérifier si une modification nécessite une remodération
     */
    private function requiresModeration(array $data): bool
    {
        $moderationFields = ['title', 'description', 'price', 'photos'];
        return array_intersect(array_keys($data), $moderationFields) !== [];
    }

    /**
     * Générer un slug unique pour l'annonce
     */
    private function generateSlug(string $title, ?int $excludeId = null): string
    {
        // Utiliser la fonction helper pour générer un slug sans accents
        helper('slug_helper');
        $slug = safe_url_title($title); // Utiliser safe_url_title pour éviter le timestamp

        $builder = $this->adModel->where('slug', $slug);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        $counter = 1;
        $originalSlug = $slug;

        while ($builder->countAllResults() > 0) {
            $slug = $originalSlug . '-' . $counter;
            $builder = $this->adModel->where('slug', $slug);

            if ($excludeId) {
                $builder->where('id !=', $excludeId);
            }
            $counter++;
        }

        return $slug;
    }

    /**
     * Récupérer les erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->adModel->errors();
    }
}
