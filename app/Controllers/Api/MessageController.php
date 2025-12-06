<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Services\MessageService;
use App\Services\AuthService;

class MessageController extends BaseApiController
{
    protected $messageService;
    protected $authService;

    public function __construct()
    {
        $this->messageService = service('messageService');
        $this->authService = service('authService');
    }

    /**
     * GET /api/messages - Liste des messages de l'utilisateur
     */
    public function index()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $filters = [
                'type' => $this->request->getGet('type'),
                'status' => $this->request->getGet('status')
            ];

            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;

            $messages = $this->messageService->getUserMessages($userId, $filters, $limit, $offset);
            $unreadCount = $this->messageService->countUnreadMessages($userId);

            return $this->success([
                'messages' => $messages,
                'unread_count' => $unreadCount,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ], 'Messages récupérés avec succès');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/messages - Envoyer un message
     */
    public function create()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $data = $this->request->getJSON(true);

            if (empty($data['ad_id'])) {
                return $this->validationError(['ad_id' => 'ID de l\'annonce requis']);
            }

            if (empty($data['content'])) {
                return $this->validationError(['content' => 'Contenu du message requis']);
            }

            // Gérer les fichiers uploadés pour les images
            $images = $this->request->getFiles();
            if (!empty($images['images'])) {
                $data['images'] = $images['images'];
            }

            $messageId = $this->messageService->sendMessage($userId, $data['ad_id'], $data);

            return $this->created(['id' => $messageId], 'Message envoyé avec succès');

        } catch (\InvalidArgumentException $e) {
            return $this->validationError(['error' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }

    /**
     * GET /api/messages/{id} - Détails d'un message
     */
    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du message requis']);
            }

            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $message = $this->messageService->getMessageDetails((int)$id, $userId);

            if (!$message) {
                return $this->notFound('Message non trouvé');
            }

            return $this->success($message, 'Message récupéré avec succès');

        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }

    /**
     * PUT /api/messages/{id}/read - Marquer un message comme lu
     */
    public function markAsRead($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du message requis']);
            }

            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $success = $this->messageService->markAsRead((int)$id, $userId);

            if (!$success) {
                return $this->serverError('Échec de la mise à jour');
            }

            return $this->success(null, 'Message marqué comme lu');

        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }

    /**
     * DELETE /api/messages/{id} - Supprimer un message
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID du message requis']);
            }

            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $success = $this->messageService->deleteMessage((int)$id, $userId);

            if (!$success) {
                return $this->serverError('Échec de la suppression');
            }

            return $this->success(null, 'Message supprimé avec succès');

        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }

    /**
     * GET /api/messages/unread/count - Compter les messages non lus
     */
    public function countUnread()
    {
        try {
            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $count = $this->messageService->countUnreadMessages($userId);

            return $this->success(['count' => $count], 'Nombre de messages non lus récupéré');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/messages/ad/{adId} - Messages d'une annonce
     */
    public function getAdMessages($adId = null)
    {
        try {
            if (!$adId) {
                return $this->validationError(['ad_id' => 'ID de l\'annonce requis']);
            }

            // Vérifier l'authentification
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token d\'authentification requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $limit = $this->request->getGet('limit') ?? 50;
            $offset = $this->request->getGet('offset') ?? 0;

            $messages = $this->messageService->getAdMessages((int)$adId, $userId, $limit, $offset);

            return $this->success([
                'messages' => $messages,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ], 'Messages de l\'annonce récupérés avec succès');

        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            return $this->serverError('Erreur interne du serveur');
        }
    }
}
