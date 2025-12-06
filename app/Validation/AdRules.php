<?php

namespace App\Validation;

class AdRules
{
    /**
     * Vérifier que l'utilisateur peut créer des annonces
     */
    public function canCreateAds(string $str, string $fields, array $data): bool
    {
        // Cette règle nécessiterait l'accès au service d'authentification
        // Pour l'instant, on retourne true
        return true;
    }

    /**
     * Vérifier que la sous-catégorie existe et est active
     */
    public function validSubcategory(string $str, string $fields, array $data): bool
    {
        $subcategoryModel = new \App\Models\SubcategoryModel();
        $subcategory = $subcategoryModel->find($str);

        return $subcategory && $subcategory->is_active;
    }

    /**
     * Vérifier que la catégorie existe
     */
    public function validCategory(string $str, string $fields, array $data): bool
    {
        $categoryModel = new \App\Models\CategoryModel();
        return $categoryModel->find($str) !== null;
    }

    /**
     * Vérifier que la localisation existe
     */
    public function validLocation(string $str, string $fields, array $data): bool
    {
        $locationModel = new \App\Models\LocationModel();
        return $locationModel->find($str) !== null;
    }

    /**
     * Vérifier que la marque existe et appartient à la sous-catégorie
     */
    public function validBrand(string $str, string $fields, array $data): bool
    {
        if (empty($str)) {
            return true; // La marque est optionnelle
        }

        $brandModel = new \App\Models\BrandModel();
        $brand = $brandModel->find($str);

        if (!$brand || !$brand->is_active) {
            return false;
        }

        // Vérifier que la marque appartient à la sous-catégorie sélectionnée
        return isset($data['subcategory_id']) && $brand->subcategory_id == $data['subcategory_id'];
    }

    /**
     * Vérifier que le prix est valide
     */
    public function validPrice(string $str, string $fields, array $data): bool
    {
        $price = (float) $str;
        return $price >= 0 && $price <= 999999.99;
    }

    /**
     * Vérifier que le prix original est supérieur au prix actuel si remisé
     */
    public function validDiscountPrice(string $str, string $fields, array $data): bool
    {
        if (!isset($data['price']) || !isset($data['original_price'])) {
            return true;
        }

        $price = (float) $data['price'];
        $originalPrice = (float) $data['original_price'];

        return $originalPrice > $price;
    }

    /**
     * Vérifier que le slug est unique
     */
    public function uniqueSlug(string $str, string $fields, array $data): bool
    {
        $adModel = new \App\Models\AdModel();

        $builder = $adModel->where('slug', $str);

        // Exclure l'annonce actuelle en cas de mise à jour
        if (isset($data['id'])) {
            $builder->where('id !=', $data['id']);
        }

        return $builder->countAllResults() === 0;
    }

    /**
     * Vérifier que l'utilisateur est propriétaire de l'annonce
     */
    public function ownsAd(string $str, string $fields, array $data): bool
    {
        $adModel = new \App\Models\AdModel();
        $ad = $adModel->find($str);

        // Cette vérification nécessiterait le contexte de l'utilisateur connecté
        // Pour l'instant, on simule
        return $ad !== null;
    }
}
