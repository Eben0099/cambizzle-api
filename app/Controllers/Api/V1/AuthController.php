<?php
namespace App\Controllers\Api\V1;
use App\Controllers\Api\BaseApiController;
use App\Models\UserModel;

class AuthController extends BaseApiController {
    // ...squelette AuthController...

    /**
     * Inscription utilisateur sans validation
     */
    public function register()
    {
        $data = $this->request->getPost();
        $userModel = new UserModel();
        // Ajout direct de l'utilisateur
        $userId = $userModel->insert($data);
        if ($userId) {
            return $this->respondCreated([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'user_id' => $userId
            ]);
        } else {
            return $this->failValidationError('Erreur lors de la création du compte');
        }
    }
}
