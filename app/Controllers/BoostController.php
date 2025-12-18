<?php
namespace App\Controllers;

use App\Services\BoostService;
use CodeIgniter\RESTful\ResourceController;

class BoostController extends ResourceController
{
    protected $boostService;

    public function __construct()
    {
        $this->boostService = new BoostService();
    }

    /**
     * Créer une annonce boostée (création + boost gratuit ou payant)
     */
    public function createBoostedAd()
    {
        $data = $this->request->getJSON(true);
        // 1. Créer l'annonce (à adapter selon ton AdModel)
        // ...
        $adId = null; // À remplacer par l'ID de l'annonce créée
        $userId = $data['user_id'];
        $packId = $data['pack_id'] ?? null;
        $phone = $data['phone'] ?? null;
        $paymentMethod = $data['payment_method'] ?? null;
        $isFree = $data['is_free'] ?? false;

        if ($isFree) {
            // Vérifier l'éligibilité et appliquer le boost gratuit
            if ($this->boostService->isEligibleForFreeBoost($userId)) {
                $pack = model('PromotionPackModel')->find($packId);
                $this->boostService->applyFreeBoost($adId, $pack['duration_days']);
                return $this->respond(['message' => 'Annonce boostée gratuitement'], 201);
            } else {
                return $this->fail('Non éligible au boost gratuit', 403);
            }
        } else {
            // Paiement boost payant
            $paymentId = $this->boostService->startBoostPayment($adId, $userId, $packId, $phone, $paymentMethod);
            return $this->respond(['payment_id' => $paymentId, 'message' => 'Paiement lancé'], 201);
        }
    }

    /**
     * Booster une annonce existante (payant uniquement)
     */
    public function boostExistingAd($adSlug)
    {
        $data = $this->request->getJSON(true);
        $userId = $data['user_id'];
        $packId = $data['pack_id'];
        $phone = $data['phone'];
        $paymentMethod = $data['payment_method'] ?? 'mobile_money';
        
        // Récupérer l'annonce par slug
        $ad = model('AdModel')->where('slug', $adSlug)->first();
        if (!$ad) {
            return $this->fail('Annonce introuvable', 404);
        }
        
        // Vérifier que l'utilisateur est propriétaire
        if ($ad['user_id'] != $userId) {
            return $this->fail('Vous n\'êtes pas propriétaire de cette annonce', 403);
        }
        
        try {
            $result = $this->boostService->startBoostPayment($ad['id'], $userId, $packId, $phone, $paymentMethod);
            
            return $this->respond([
                'payment_id' => $result['payment_id'],
                'reference' => $result['reference'],
                'message' => 'Paiement lancé'
            ], 201);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * Callback de paiement réussi (à appeler par Campay ou le frontend après confirmation)
     */
    public function confirmBoostPayment($paymentId)
    {
        $this->boostService->confirmBoostPayment($paymentId);
        return $this->respond(['message' => 'Boost activé après paiement'], 200);
    }

    /**
     * Relancer un paiement échoué
     */
    public function retryPayment($paymentId)
    {
        $data = $this->request->getJSON(true);
        $phone = $data['phone'];
        $paymentMethod = $data['payment_method'];
        $this->boostService->retryPayment($paymentId, $phone, $paymentMethod);
        return $this->respond(['message' => 'Paiement relancé'], 200);
    }

    /**
     * Lister les annonces boostées
     */
    public function listBoostedAds()
    {
        $ads = model('AdModel')
            ->where('is_boosted', 1)
            ->where('boost_end >=', date('Y-m-d H:i:s'))
            ->orderBy('boost_end', 'DESC')
            ->findAll();
        return $this->respond($ads, 200);
    }
    
    /**
     * Vérifier le statut d'un paiement de boost
     * Appelle Campay et met à jour la BD automatiquement
     * GET /api/boost/check-payment/{id}
     */
    public function checkBoostPayment($paymentId)
    {
        $payment = model('PaymentModel')->find($paymentId);
        if (!$payment) {
            return $this->fail('Paiement introuvable', 404);
        }
        
        // Décoder le metadata si nécessaire
        if (isset($payment['metadata']) && is_string($payment['metadata'])) {
            $payment['metadata'] = json_decode($payment['metadata'], true);
        }
        
        // Vérifier et mettre à jour le statut automatiquement
        $result = $this->boostService->verifyAndUpdatePaymentStatus($paymentId);
        
        // Construire la réponse
        $response = [
            'payment_id' => $paymentId,
            'status' => $result['status'],
            'updated' => $result['updated'],
            'message' => $result['message']
        ];

        // Ajouter les détails Campay si disponibles
        if ($result['campay_response']) {
            $response['campay'] = [
                'reference' => $result['campay_response']['reference'] ?? null,
                'status' => $result['campay_response']['status'] ?? null,
                'amount' => $result['campay_response']['amount'] ?? null,
                'currency' => $result['campay_response']['currency'] ?? null,
                'operator' => $result['campay_response']['operator'] ?? null,
                'operator_reference' => $result['campay_response']['operator_reference'] ?? null,
            ];
        }

        // Si le paiement est réussi, ajouter les infos de l'annonce boostée
        if ($result['status'] === 'paid' && isset($payment['metadata']['ad_id'])) {
            $ad = model('AdModel')->find($payment['metadata']['ad_id']);
            if ($ad) {
                $response['ad'] = [
                    'id' => $ad['id'],
                    'slug' => $ad['slug'],
                    'title' => $ad['title'],
                    'is_boosted' => $ad['is_boosted'],
                    'boost_start' => $ad['boost_start'],
                    'boost_end' => $ad['boost_end'],
                ];
            }
        }

        return $this->respond($response, 200);
    }
    
    /**
     * Publier une annonce en version gratuite (boost gratuit)
     */
    public function publishFreeVersion()
    {
        $data = $this->request->getJSON(true);
        $adId = $data['ad_id'];
        $userId = $data['user_id'];
        $freeDays = $data['free_days'] ?? 14; // 14 jours par défaut
        
        // Vérifier que l'annonce appartient à l'utilisateur
        $ad = model('AdModel')->find($adId);
        if (!$ad || $ad['user_id'] != $userId) {
            return $this->fail('Annonce introuvable ou non autorisée', 403);
        }
        
        // Vérifier l'éligibilité au boost gratuit
        if (!$this->boostService->isEligibleForFreeBoost($userId)) {
            return $this->fail('Non éligible au boost gratuit', 403);
        }
        
        // Appliquer le boost gratuit
        $this->boostService->applyFreeBoost($adId, $freeDays);
        
        return $this->respond([
            'message' => 'Annonce publiée avec boost gratuit',
            'boost_end' => date('Y-m-d H:i:s', strtotime("+{$freeDays} days"))
        ], 200);
    }

    /**
     * OPTIONS handler for CORS preflight
     */
    public function options()
    {
        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setStatusCode(204);
    }
}
