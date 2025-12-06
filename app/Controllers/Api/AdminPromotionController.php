<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\AdPromotionModel;

class AdminPromotionController extends BaseApiController
{
    protected $promotionPackModel;
    protected $adPromotionModel;

    public function __construct()
    {
        // Le modèle PromotionPack n'existe pas encore, nous utiliserons la table directement
        $this->adPromotionModel = new AdPromotionModel();
    }

    /**
     * Liste des packs promotionnels
     */
    public function packs()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 20;
            $page = $this->request->getGet('page') ?? 1;
            $search = $this->request->getGet('search');
            $isActive = $this->request->getGet('is_active');
            $isFeatured = $this->request->getGet('is_featured');

            $db = \Config\Database::connect();
            $builder = $db->table('promotion_packs');

            if ($search) {
                $builder->groupStart()
                        ->like('name', $search)
                        ->orLike('description', $search)
                        ->groupEnd();
            }

            if ($isActive !== null) {
                $builder->where('is_active', (bool)$isActive);
            }

            if ($isFeatured !== null) {
                $builder->where('is_featured', (bool)$isFeatured);
            }

            $total = $builder->countAllResults(false);
            $packs = $builder->orderBy('display_order', 'ASC')
                            ->orderBy('name', 'ASC')
                            ->limit($perPage, ($page - 1) * $perPage)
                            ->get()
                            ->getResultArray();

            return $this->success([
                'packs' => $packs,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ], 'Packs promotionnels récupérés avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Créer un pack promotionnel
     */
    public function createPack()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validation des données
            $rules = [
                'name' => 'required|min_length[2]|max_length[100]',
                'slug' => 'required|min_length[2]|max_length[120]|is_unique[promotion_packs.slug]',
                'description' => 'permit_empty|max_length[500]',
                'price' => 'required|decimal',
                'duration_days' => 'required|integer|greater_than[0]',
                'features' => 'permit_empty',
                'is_featured' => 'permit_empty|in_list[0,1]',
                'is_active' => 'permit_empty|in_list[0,1]',
                'display_order' => 'permit_empty|integer'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            // Valeurs par défaut
            $data['is_featured'] = $data['is_featured'] ?? 0;
            $data['is_active'] = $data['is_active'] ?? 1;
            $data['display_order'] = $data['display_order'] ?? 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Encoder les features en JSON si c'est un array
            if (isset($data['features']) && is_array($data['features'])) {
                $data['features'] = json_encode($data['features']);
            }

            $db = \Config\Database::connect();
            $success = $db->table('promotion_packs')->insert($data);

            if (!$success) {
                return $this->serverError('Erreur lors de la création du pack promotionnel');
            }

            $id = $db->insertID();
            $pack = $db->table('promotion_packs')->where('id', $id)->get()->getRowArray();

            // Décoder les features
            if ($pack['features']) {
                $pack['features'] = json_decode($pack['features'], true);
            }

            return $this->success($pack, 'Pack promotionnel créé avec succès', 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Mettre à jour un pack promotionnel
     */
    public function updatePack($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du pack promotionnel requis']);
            }

            $db = \Config\Database::connect();
            $pack = $db->table('promotion_packs')->where('id', $id)->get()->getRowArray();

            if (!$pack) {
                return $this->notFound('Pack promotionnel non trouvé');
            }

            $data = $this->request->getJSON(true);

            // Validation avec vérification d'unicité du slug
            $rules = [
                'name' => 'required|min_length[2]|max_length[100]',
                'slug' => "required|min_length[2]|max_length[120]|is_unique[promotion_packs.slug,id,{$id}]",
                'description' => 'permit_empty|max_length[500]',
                'price' => 'required|decimal',
                'duration_days' => 'required|integer|greater_than[0]',
                'features' => 'permit_empty',
                'is_featured' => 'permit_empty|in_list[0,1]',
                'is_active' => 'permit_empty|in_list[0,1]',
                'display_order' => 'permit_empty|integer'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            $data['updated_at'] = date('Y-m-d H:i:s');

            // Encoder les features en JSON si c'est un array
            if (isset($data['features']) && is_array($data['features'])) {
                $data['features'] = json_encode($data['features']);
            }

            $success = $db->table('promotion_packs')->where('id', $id)->update($data);

            if (!$success) {
                return $this->serverError('Erreur lors de la mise à jour du pack promotionnel');
            }

            $updatedPack = $db->table('promotion_packs')->where('id', $id)->get()->getRowArray();

            // Décoder les features
            if ($updatedPack['features']) {
                $updatedPack['features'] = json_decode($updatedPack['features'], true);
            }

            return $this->success($updatedPack, 'Pack promotionnel mis à jour avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer un pack promotionnel
     */
    public function deletePack($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du pack promotionnel requis']);
            }

            $db = \Config\Database::connect();
            $pack = $db->table('promotion_packs')->where('id', $id)->get()->getRowArray();

            if (!$pack) {
                return $this->notFound('Pack promotionnel non trouvé');
            }

            $success = $db->table('promotion_packs')->where('id', $id)->delete();

            if (!$success) {
                return $this->serverError('Erreur lors de la suppression du pack promotionnel');
            }

            return $this->success(null, 'Pack promotionnel supprimé avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Liste des promotions actives
     */
    public function activePromotions()
    {
        try {
            $perPage = $this->request->getGet('per_page') ?? 20;
            $page = $this->request->getGet('page') ?? 1;

            $promotions = $this->adPromotionModel->getActivePromotions($perPage, ($page - 1) * $perPage);

            return $this->success([
                'promotions' => $promotions['promotions'],
                'pagination' => [
                    'total' => $promotions['total'],
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($promotions['total'] / $perPage)
                ]
            ], 'Promotions actives récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Activer une promotion pour une annonce
     */
    public function activatePromotion()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validation des données
            $rules = [
                'ad_id' => 'required|integer',
                'pack_id' => 'required|integer',
                'user_id' => 'required|integer'
            ];

            if (!$this->validate($rules)) {
                return $this->validationError($this->validator->getErrors());
            }

            $db = \Config\Database::connect();

            // Vérifier que le pack existe et est actif
            $pack = $db->table('promotion_packs')
                      ->where('id', $data['pack_id'])
                      ->where('is_active', 1)
                      ->get()
                      ->getRowArray();

            if (!$pack) {
                return $this->notFound('Pack promotionnel non trouvé ou inactif');
            }

            // Calculer la date de fin
            $endDate = date('Y-m-d H:i:s', strtotime("+{$pack['duration_days']} days"));

            // Créer la promotion
            $promotionData = [
                'ad_id' => $data['ad_id'],
                'pack_id' => $data['pack_id'],
                'user_id' => $data['user_id'],
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => $endDate,
                'is_active' => 1,
                'features' => $pack['features'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $success = $db->table('ad_promotions')->insert($promotionData);

            if (!$success) {
                return $this->serverError('Erreur lors de l\'activation de la promotion');
            }

            $promotionId = $db->insertID();
            $promotion = $db->table('ad_promotions')->where('id', $promotionId)->get()->getRowArray();

            // Décoder les features
            if ($promotion['features']) {
                $promotion['features'] = json_decode($promotion['features'], true);
            }

            return $this->success($promotion, 'Promotion activée avec succès', 201);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Désactiver une promotion
     */
    public function deactivatePromotion($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID de la promotion requis']);
            }

            $success = $this->adPromotionModel->deactivate($id);

            if (!$success) {
                return $this->serverError('Erreur lors de la désactivation de la promotion');
            }

            return $this->success(null, 'Promotion désactivée avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Statistiques des promotions
     */
    public function promotionStats()
    {
        try {
            $db = \Config\Database::connect();

            // Statistiques générales
            $stats = $db->table('ad_promotions')
                       ->select('
                           COUNT(*) as total_promotions,
                           SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_promotions,
                           SUM(CASE WHEN end_date < NOW() AND is_active = 1 THEN 1 ELSE 0 END) as expired_promotions
                       ')
                       ->get()
                       ->getRowArray();

            // Top packs utilisés
            $topPacks = $db->table('ad_promotions ap')
                          ->select('pp.name, pp.slug, COUNT(ap.id) as usage_count, SUM(pp.price) as total_revenue')
                          ->join('promotion_packs pp', 'pp.id = ap.pack_id')
                          ->groupBy('ap.pack_id')
                          ->orderBy('usage_count', 'DESC')
                          ->limit(10)
                          ->get()
                          ->getResultArray();

            // Revenus par mois (derniers 12 mois)
            $monthlyRevenue = $db->table('ad_promotions ap')
                                ->select('DATE_FORMAT(ap.created_at, "%Y-%m") as month, SUM(pp.price) as revenue, COUNT(ap.id) as promotions_count')
                                ->join('promotion_packs pp', 'pp.id = ap.pack_id')
                                ->where('ap.created_at >=', date('Y-m-d H:i:s', strtotime('-12 months')))
                                ->groupBy('month')
                                ->orderBy('month', 'DESC')
                                ->get()
                                ->getResultArray();

            return $this->success([
                'general_stats' => $stats,
                'top_packs' => $topPacks,
                'monthly_revenue' => $monthlyRevenue
            ], 'Statistiques des promotions récupérées avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
