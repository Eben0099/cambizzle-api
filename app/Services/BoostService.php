<?php
namespace App\Services;

use App\Models\PromotionPackModel;
use App\Models\AdModel;
use App\Models\PaymentModel;
use App\Models\AdPromotionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * BoostService
 * Gère la logique de boost d'annonce (gratuit, payant, paiement, relance, etc.)
 */
class BoostService
{
    protected $promotionPackModel;
    protected $adModel;
    protected $paymentModel;
    protected $adPromotionModel;
    protected $db;
    protected $campayToken;
    protected $campayApiId;
    protected $campayApiUser;
    protected $campayApiPass;

    public function __construct()
    {
        $this->promotionPackModel = new PromotionPackModel();
        $this->adModel = new AdModel();
        $this->paymentModel = new PaymentModel();
        $this->adPromotionModel = new AdPromotionModel();
        $this->db = \Config\Database::connect();
        
        // Clés Campay
        $this->campayToken = getenv('CAMPAY_TOKEN') ?: '92eae805ff544579e42cf5cca55aa80896e71eb0';
        $this->campayApiId = getenv('CAMPAY_API_ID') ?: 'suqQ-3BXPpu6EK2asPctKfjdGtyp0hk4_prR210dSSF8XHrPChpHvJyyOxMIq3zep0uxQOSfK3XZfEkudWYPjA';
        $this->campayApiUser = getenv('CAMPAY_API_USER') ?: 'yt4sQKPOjFw4ZI9yuS-G9dF1hh6oJUodDFfo1J-UwR3aCVOfz48m3H91_HJCaWOYprCQSLhnFWXGPD7mJMO55Q';
        $this->campayApiPass = getenv('CAMPAY_API_PASS') ?: 'pzB8_ZrZjL9O58nFZlZOAieWsAp6ij1cfSXdi9hvswiSE8Zr9Ru9z-H2J6P-fyg9OD2nhA53lZFItzqNv6-9Vg';
    }

    /**
     * Vérifie si l'utilisateur est éligible au boost gratuit
     */
    public function isEligibleForFreeBoost($userId)
    {
        // À adapter selon la logique métier (ex: 1 seul essai gratuit par user)
        $count = $this->adPromotionModel
            ->where('user_id', $userId)
            ->where('promotion_type', 'boost')
            ->where('price_paid', 0)
            ->countAllResults();
        return $count === 0;
    }

    /**
     * Applique un boost gratuit à une annonce
     */
    public function applyFreeBoost($adId, $durationDays)
    {
        $now = date('Y-m-d H:i:s');
        $end = date('Y-m-d H:i:s', strtotime("+$durationDays days"));
        // Mettre à jour l'annonce
        $this->adModel->update($adId, [
            'is_boosted' => 1,
            'boost_start' => $now,
            'boost_end' => $end
        ]);
        // Créer une entrée promotion
        $this->adPromotionModel->insert([
            'ad_id' => $adId,
            'promotion_type' => 'boost',
            'starts_at' => $now,
            'expires_at' => $end,
            'price_paid' => 0,
            'payment_reference' => null,
            'is_active' => 1,
            'created_at' => $now
        ]);
    }

    /**
     * Lance un paiement pour un boost payant
     */
    public function startBoostPayment($adId, $userId, $packId, $phone, $paymentMethod)
    {
        $pack = $this->promotionPackModel->find($packId);
        if (!$pack) {
            throw new \Exception('Pack de promotion introuvable');
        }
        
        // Récupérer l'annonce pour le titre
        $ad = $this->adModel->find($adId);
        $description = "Boost pour annonce: {$ad['title']} - {$pack['name']} pour {$pack['duration_days']} jours";
        
        // Générer une référence locale temporaire (sera remplacée par celle de Campay)
        $tempReference = 'TEMP_' . $adId . '_' . uniqid() . '_' . time();
        
        // Appel à Campay AVANT de créer le paiement en BD
        $paymentResponse = $this->collectPaiement(
            $pack['price'],
            $phone,
            $description,
            $tempReference,
            $userId
        );
        
        if (!$paymentResponse || !isset($paymentResponse['reference'])) {
            throw new \Exception('Échec de l\'initiation du paiement Campay');
        }
        
        // Utiliser la référence Campay
        $campayReference = $paymentResponse['reference'];
        
        // Créer une entrée de paiement avec la référence Campay
        $paymentData = [
            'user_id' => $userId,
            'ad_id' => $adId,
            'reference' => $campayReference,
            'amount' => $pack['price'],
            'phone' => $phone,
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'description' => 'Boost annonce - ' . $pack['name'],
            'metadata' => json_encode([
                'pack_id' => $packId,
                'duration_days' => $pack['duration_days'],
                'pack_name' => $pack['name'],
                'ad_id' => $adId
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $paymentId = $this->paymentModel->insert($paymentData, true);
        
        return [
            'payment_id' => $paymentId,
            'reference' => $paymentResponse['reference']
        ];
    }

    /**
     * Callback de paiement réussi : applique le boost
     */
    public function confirmBoostPayment($paymentId)
    {
        $payment = $this->paymentModel->find($paymentId);
        if (!$payment || $payment['status'] !== 'paid') {
            log_message('error', "[confirmBoostPayment] Paiement non validé ou introuvable: paymentId={$paymentId}");
            throw new \Exception('Paiement non validé');
        }
        $pack = $this->promotionPackModel->where('price', $payment['amount'])->first();
        if (!$pack) {
            log_message('error', "[confirmBoostPayment] Aucun pack trouvé pour le montant: " . $payment['amount']);
            throw new \Exception('Pack promotion introuvable pour ce montant');
        }
        $adId = $payment['ad_id'];
        if (!$adId) {
            log_message('error', "[confirmBoostPayment] ad_id manquant dans le paiement: paymentId={$paymentId}");
            throw new \Exception('ad_id manquant dans le paiement');
        }
        $ad = $this->adModel->find($adId);
        if (!$ad) {
            log_message('error', "[confirmBoostPayment] Annonce non trouvée: adId={$adId}");
            throw new \Exception('Annonce non trouvée');
        }
        $now = date('Y-m-d H:i:s');
        $end = date('Y-m-d H:i:s', strtotime("+{$pack['duration_days']} days"));
        // Mettre à jour l'annonce
        $updateResult = $this->adModel->update($adId, [
            'is_boosted' => 1,
            'boost_start' => $now,
            'boost_end' => $end
        ]);
        if ($updateResult === false) {
            log_message('error', "[confirmBoostPayment] Echec de la mise à jour de l'annonce: adId={$adId}");
            throw new \Exception('Echec de la mise à jour de l\'annonce');
        } else {
            log_message('info', "[confirmBoostPayment] Annonce boostée avec succès: adId={$adId}");
        }
        // Créer une entrée promotion
        $promoId = $this->adPromotionModel->insert([
            'ad_id' => $adId,
            'promotion_type' => 'boost',
            'starts_at' => $now,
            'expires_at' => $end,
            'price_paid' => $payment['amount'],
            'payment_reference' => $payment['reference'] ?? null,
            'is_active' => 1,
            'created_at' => $now
        ]);
        if (!$promoId) {
            log_message('error', "[confirmBoostPayment] Echec de l'insertion dans ad_promotions pour adId={$adId}");
        } else {
            log_message('info', "[confirmBoostPayment] Promotion enregistrée: promoId={$promoId}, adId={$adId}");
        }
    }

    /**
     * Relance un paiement échoué
     */
    public function retryPayment($paymentId, $phone, $paymentMethod)
    {
        $payment = $this->paymentModel->find($paymentId);
        if (!$payment || $payment['status'] !== 'failed') {
            throw new \Exception('Paiement non éligible à la relance');
        }
        // Appel à l'API Campay/Mobile Money ici (à implémenter)
        // ...
        // Mettre à jour le statut en pending
        $this->paymentModel->update($paymentId, [
            'status' => 'pending',
            'phone' => $phone,
            'payment_method' => $paymentMethod
        ]);
    }

    /**
     * Lance le paiement via Campay
     */
    protected function collectPaiement($amount, $from, $description, $externalReference, $userId)
    {
        $url = getenv('CAMPAY_URL') ?: 'https://campay.net/api/collect/';
        
        // Normaliser le numéro
        $from = preg_replace('/[^0-9]/', '', $from);
        if (substr($from, 0, 3) !== '237') {
            $from = '237' . substr($from, -9);
        }

        $parameters = [
            'amount' => (int)$amount,
            'currency' => 'XAF',
            'from' => $from,
            'description' => $description,
            'external_reference' => $externalReference,
            'external_user' => (string)$userId,
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($parameters),
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $this->campayToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            log_message('error', 'Erreur cURL Campay: ' . $err);
            return false;
        }

        log_message('debug', 'Réponse Campay (HTTP ' . $httpCode . '): ' . $response);
        $decodedResponse = json_decode($response, true);

        if ($httpCode !== 200 || !$decodedResponse) {
            log_message('error', 'Réponse invalide Campay: ' . $response);
            return false;
        }

        if (isset($decodedResponse['error'])) {
            log_message('error', 'Erreur Campay: ' . $decodedResponse['error']);
            return false;
        }

        return $decodedResponse;
    }

    /**
     * Vérifie le statut d'un paiement Campay
     */
    public function checkStatus($reference)
    {
        $url = (getenv('CAMPAY_URL') ?: 'https://campay.net/api/') . 'transaction/' . $reference . '/';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $this->campayToken,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            log_message('error', 'Erreur cURL check status: ' . $err);
            return false;
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode !== 200 || !$decodedResponse) {
            log_message('error', 'Réponse invalide check status: ' . $response);
            return false;
        }

        return $decodedResponse;
    }

    /**
     * Vérifie et met à jour automatiquement le statut d'un paiement depuis Campay
     * @return array ['updated' => bool, 'status' => string, 'campay_response' => array, 'message' => string]
     */
    public function verifyAndUpdatePaymentStatus($paymentId)
    {
        // Récupérer le paiement
        $payment = $this->paymentModel->find($paymentId);
        
        if (!$payment) {
            return [
                'updated' => false,
                'status' => 'error',
                'message' => 'Paiement introuvable'
            ];
        }

        // Si déjà payé ou annulé, pas besoin de vérifier
        if (in_array($payment['status'], ['paid', 'cancelled'])) {
            return [
                'updated' => false,
                'status' => $payment['status'],
                'message' => 'Paiement déjà finalisé',
                'campay_response' => null
            ];
        }

        // Vérifier auprès de Campay
        $campayResponse = $this->checkStatus($payment['reference']);
        
        if (!$campayResponse) {
            return [
                'updated' => false,
                'status' => $payment['status'],
                'message' => 'Impossible de contacter Campay',
                'campay_response' => null
            ];
        }

        $campayStatus = $campayResponse['status'] ?? 'UNKNOWN';
        $newStatus = null;
        $shouldActivateBoost = false;

        // Mapper le statut Campay vers notre statut
        switch ($campayStatus) {
            case 'SUCCESSFUL':
                $newStatus = 'paid';
                $shouldActivateBoost = true;
                break;
            case 'FAILED':
                $newStatus = 'failed';
                break;
            case 'PENDING':
                $newStatus = 'pending';
                break;
            default:
                $newStatus = 'pending';
                break;
        }

        // Mettre à jour le paiement si le statut a changé
        $updated = false;
        if ($newStatus !== $payment['status']) {
            $updateData = [
                'status' => $newStatus,
            ];

            if ($newStatus === 'paid') {
                $updateData['processed_at'] = date('Y-m-d H:i:s');
            }

            $this->paymentModel->update($paymentId, $updateData);
            $updated = true;

            log_message('info', "Paiement #{$paymentId} mis à jour: {$payment['status']} → {$newStatus}");
        }

        // Si paiement réussi, activer le boost
        if ($shouldActivateBoost && $updated) {
            try {
                $this->confirmBoostPayment($paymentId);
                log_message('info', "Boost activé pour le paiement #{$paymentId}");
            } catch (\Exception $e) {
                log_message('error', "Erreur activation boost: " . $e->getMessage());
            }
        }

        return [
            'updated' => $updated,
            'status' => $newStatus,
            'message' => $updated ? 'Statut mis à jour' : 'Aucun changement',
            'campay_response' => $campayResponse
        ];
    }
}
