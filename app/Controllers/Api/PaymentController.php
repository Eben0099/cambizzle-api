<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\PaymentModel;
use Config\Services;

class PaymentController extends BaseApiController
{
    protected $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
    }

    public function create()
    {
        try {
            // Auth via filtre 'auth' déjà appliqué dans routes
            $data = $this->request->getJSON(true);

            if (empty($data['ad_id']) || empty($data['amount']) || empty($data['payment_method'])) {
                return $this->validationError(['fields' => 'ad_id, amount, payment_method requis']);
            }

            // Générer une référence simple
            $data['reference'] = $data['reference'] ?? strtoupper(substr(md5(uniqid('', true)), 0, 10));
            $data['status'] = $data['status'] ?? 'pending';

            if ($this->paymentModel->insert($data) === false) {
                return $this->validationError($this->paymentModel->errors());
            }

            $id = $this->paymentModel->getInsertID();
            $payment = $this->paymentModel->find($id);

            return $this->created($payment, 'Paiement créé');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du paiement requis']);
            }
            $payment = $this->paymentModel->find($id);
            if (!$payment) {
                return $this->notFound('Paiement non trouvé');
            }
            return $this->success($payment, 'Paiement récupéré');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function index()
    {
        try {
            $payments = $this->paymentModel->orderBy('id', 'DESC')->findAll();
            return $this->success($payments, 'Liste des paiements');
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}


