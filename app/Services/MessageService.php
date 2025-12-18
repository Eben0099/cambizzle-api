<?php

namespace App\Services;

use App\Models\MessageModel;
use App\Models\AdModel;
use App\Models\UserModel;

class MessageService
{
    protected $messageModel;
    protected $adModel;
    protected $userModel;

    public function __construct()
    {
        $this->messageModel = new MessageModel();
        $this->adModel = new AdModel();
        $this->userModel = new UserModel();
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(int $senderId, int $adId, array $data): int
    {
        // Vérifier que l'annonce existe
        $ad = $this->adModel->find($adId);
        if (!$ad) {
            throw new \RuntimeException('Annonce non trouvée');
        }

        // Vérifier que l'utilisateur n'est pas le propriétaire de l'annonce
        if ($ad->user_id === $senderId) {
            throw new \RuntimeException('Vous ne pouvez pas envoyer de message sur votre propre annonce');
        }

        // Préparer les données du message
        $messageData = [
            'user_id' => $senderId,
            'ad_id' => $adId,
            'type' => $data['type'] ?? 'message',
            'content' => $data['content'],
            'status' => 'unread'
        ];

        // Ajouter le parent si c'est une réponse
        if (isset($data['parent_id'])) {
            $parentMessage = $this->messageModel->find($data['parent_id']);
            if (!$parentMessage || $parentMessage->ad_id !== $adId) {
                throw new \RuntimeException('Message parent invalide');
            }
            $messageData['parent_id'] = $data['parent_id'];
        }

        // Ajouter la note si c'est un avis
        if ($messageData['type'] === 'review' && isset($data['rating'])) {
            $messageData['rating'] = $data['rating'];

            // Vérifier que l'utilisateur n'a pas déjà noté cette annonce
            if ($this->hasUserReviewedAd($senderId, $adId)) {
                throw new \RuntimeException('Vous avez déjà noté cette annonce');
            }
        }

        // Traiter les images si présentes
        if (isset($data['images']) && is_array($data['images'])) {
            $messageData['images'] = json_encode($data['images']);
        }

        $messageId = $this->messageModel->insert($messageData, true);

        if (!$messageId) {
            throw new \RuntimeException('Erreur lors de l\'envoi du message');
        }

        return $messageId;
    }

    /**
     * Récupérer les messages d'une annonce
     */
    public function getAdMessages(int $adId, int $userId, int $limit = 50, int $offset = 0): array
    {
        $ad = $this->adModel->find($adId);
        if (!$ad) {
            throw new \RuntimeException('Annonce non trouvée');
        }

        // Vérifier que l'utilisateur peut voir ces messages
        if ($ad->user_id !== $userId) {
            $hasMessages = $this->messageModel->where('ad_id', $adId)
                                             ->where('user_id', $userId)
                                             ->countAllResults() > 0;
            if (!$hasMessages) {
                throw new \RuntimeException('Accès non autorisé');
            }
        }

        $messages = $this->messageModel->getByAd($adId, $limit, $offset);

        // Marquer les messages comme lus si l'utilisateur est le destinataire
        if ($ad->user_id === $userId) {
            $this->markMessagesAsRead($adId, $userId);
        }

        return $messages;
    }

    /**
     * Récupérer les messages d'un utilisateur
     */
    public function getUserMessages(int $userId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $messages = $this->messageModel->getByUser($userId, $limit, $offset);

        // Enrichir avec les informations de l'annonce et de l'expéditeur
        foreach ($messages as &$message) {
            $ad = $this->adModel->find($message->ad_id);
            $sender = $this->userModel->find($message->user_id);

            $message->ad = $ad;
            $message->sender = $sender;
        }

        return $messages;
    }

    /**
     * Récupérer les détails d'un message avec ses réponses
     */
    public function getMessageDetails(int $messageId, int $userId): ?array
    {
        $message = $this->messageModel->find($messageId);

        if (!$message) {
            return null;
        }

        // Vérifier l'accès
        $ad = $this->adModel->find($message->ad_id);
        if (!$ad || ($ad->user_id !== $userId && $message->user_id !== $userId)) {
            throw new \RuntimeException('Accès non autorisé');
        }

        $messageData = $message->toArray();

        // Ajouter les réponses
        if (!$message->isReply()) {
            $messageData['replies'] = $this->messageModel->getReplies($messageId);
        }

        // Ajouter les informations de l'annonce et des utilisateurs
        $messageData['ad'] = $this->adModel->find($message->ad_id);
        $messageData['sender'] = $this->userModel->find($message->user_id);

        return $messageData;
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(int $messageId, int $userId): bool
    {
        $message = $this->messageModel->find($messageId);

        if (!$message) {
            throw new \RuntimeException('Message non trouvé');
        }

        // Vérifier que l'utilisateur peut marquer ce message comme lu
        $ad = $this->adModel->find($message->ad_id);
        if (!$ad || $ad->user_id !== $userId) {
            throw new \RuntimeException('Accès non autorisé');
        }

        return $this->messageModel->markAsRead($messageId);
    }

    /**
     * Supprimer un message
     */
    public function deleteMessage(int $messageId, int $userId): bool
    {
        $message = $this->messageModel->find($messageId);

        if (!$message) {
            throw new \RuntimeException('Message non trouvé');
        }

        // Vérifier que l'utilisateur peut supprimer ce message
        if ($message->user_id !== $userId) {
            $ad = $this->adModel->find($message->ad_id);
            if (!$ad || $ad->user_id !== $userId) {
                throw new \RuntimeException('Accès non autorisé');
            }
        }

        return $this->messageModel->softDelete($messageId);
    }

    /**
     * Compter les messages non lus d'un utilisateur
     */
    public function countUnreadMessages(int $userId): int
    {
        return $this->messageModel->countUnreadByUser($userId);
    }

    /**
     * Récupérer les avis d'une annonce
     */
    public function getAdReviews(int $adId): array
    {
        return $this->messageModel->getReviewsByAd($adId);
    }

    /**
     * Calculer la moyenne des notes d'une annonce
     */
    public function getAdAverageRating(int $adId): ?float
    {
        return $this->messageModel->getAverageRating($adId);
    }

    /**
     * Vérifier si un utilisateur a déjà noté une annonce
     */
    private function hasUserReviewedAd(int $userId, int $adId): bool
    {
        return $this->messageModel->where('user_id', $userId)
                                 ->where('ad_id', $adId)
                                 ->where('type', 'review')
                                 ->countAllResults() > 0;
    }

    /**
     * Marquer tous les messages d'une annonce comme lus pour un utilisateur
     */
    private function markMessagesAsRead(int $adId, int $userId): void
    {
        $this->messageModel->where('ad_id', $adId)
                          ->where('user_id !=', $userId)
                          ->where('status', 'unread')
                          ->set('status', 'read')
                          ->update();
    }

    /**
     * Récupérer les erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->messageModel->errors();
    }
}
