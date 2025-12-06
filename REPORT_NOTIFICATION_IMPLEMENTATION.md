# Implémentation Rapide - Notifier via WhatsApp & In-App

Ce fichier contient le code prêt à copier-coller pour ajouter les notifications.

## 📦 Fichiers à modifier/créer

1. **ReportService.php** - Ajouter les méthodes de notification
2. **ReportController.php** - Intégrer les notifications
3. **NotificationController.php** - CRÉER (pour lire les notifications)
4. **Routes.php** - Ajouter les routes des notifications
5. **migrations/CreateNotificationsTable.php** - CRÉER (table BD)

---

## 1️⃣ Migration - Table notifications

**Fichier:** `app/Database/Migrations/YYYY-MM-DD-XXXXXX_CreateNotificationsTable.php`

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                // 'ad_reported', 'report_resolved', 'report_dismissed'
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'data' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'is_read' => [
                'type'    => 'TINYINT',
                'default' => 0,
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id_user', 'CASCADE', 'CASCADE');
        $this->forge->createTable('notifications');

        // Index pour performance
        $this->forge->addField([]);
        $this->db->disableForeignKeyChecks();
        $this->db->query('CREATE INDEX idx_user_unread ON notifications(user_id, is_read)');
        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        $this->forge->dropTable('notifications');
    }
}
```

---

## 2️⃣ Service - Ajouter les notifications

**Fichier:** `app/Services/ReportService.php` - **MODIFIER EXISTANT**

```php
// Ajouter ces méthodes à la fin de la classe ReportService

    /**
     * Notifier le propriétaire de l'annonce
     */
    public function notifyAdOwnerOfReport(int $adId, int $reportId): array
    {
        $ad = $this->adModel->find($adId);
        if (!$ad) {
            return ['error' => 'Ad not found'];
        }

        $adOwner = $this->userModel->find($ad->user_id);
        if (!$adOwner) {
            return ['error' => 'Owner not found'];
        }

        $results = [];

        // 1️⃣ IN-APP NOTIFICATION
        $results['in_app'] = $this->addInAppNotification(
            $adOwner['id_user'],
            $ad,
            $reportId
        );

        // 2️⃣ EMAIL
        if (!empty($adOwner['email'])) {
            $results['email'] = $this->sendEmailNotification($adOwner, $ad);
        }

        // 3️⃣ WhatsApp LINK
        if (!empty($adOwner['phone'])) {
            $results['whatsapp_link'] = $this->generateWhatsAppLink(
                $adOwner['phone'],
                $ad
            );
        }

        return $results;
    }

    /**
     * 1️⃣ Ajouter notification In-App
     */
    private function addInAppNotification(int $userId, array $ad, int $reportId): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->table('notifications')->insert([
                'user_id' => $userId,
                'type' => 'ad_reported',
                'title' => 'Votre annonce a été reportée',
                'message' => "Votre annonce \"{$ad['title']}\" a reçu un signalement. "
                    . "Elle est en attente de modération.",
                'data' => json_encode([
                    'ad_id' => $ad['id_ad'] ?? $ad['id'],
                    'report_id' => $reportId
                ]),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            log_message('info', "In-app notification created for user {$userId}");
            return true;

        } catch (\Exception $e) {
            log_message('error', 'In-app notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 2️⃣ Email de notification
     */
    private function sendEmailNotification(array $adOwner, array $ad): bool
    {
        try {
            $email = service('email');

            $subject = "⚠️ Votre annonce a été reportée - Action requise";

            $htmlMessage = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; }
                        .alert { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
                        .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h2>⚠️ Alerte: Votre annonce a été reportée</h2>

                        <p>Bonjour {$adOwner['first_name']},</p>

                        <div class='alert'>
                            <p>Votre annonce <strong>\"{$ad['title']}\"</strong> a été reportée par un utilisateur.</p>
                        </div>

                        <h3>État actuel:</h3>
                        <ul>
                            <li>Statut: <strong>En attente de modération</strong></li>
                            <li>Date: " . date('d/m/Y H:i') . "</li>
                        </ul>

                        <p>
                            Notre équipe examinera le rapport dans les 48 heures.
                            Si le contenu respecte nos conditions d'utilisation,
                            votre annonce restera active.
                        </p>

                        <p>Pour plus de détails, consultez votre compte Cambizzle.</p>

                        <hr>
                        <small>© 2024 Cambizzle. Tous droits réservés.</small>
                    </div>
                </body>
                </html>
            ";

            return $email
                ->setFrom(env('email.fromEmail', 'noreply@cambizzle.com'))
                ->setTo($adOwner['email'])
                ->setSubject($subject)
                ->setMessage($htmlMessage)
                ->send();

        } catch (\Exception $e) {
            log_message('error', 'Email notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 3️⃣ Générer lien WhatsApp
     */
    private function generateWhatsAppLink(string $phone, array $ad): ?string
    {
        try {
            // Nettoyer le numéro
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

            // S'assurer que le numéro a un code pays
            if (!str_starts_with($cleanPhone, '237')) {
                // Si c'est un numéro camerounais sans code
                if (strlen($cleanPhone) === 9) {
                    $cleanPhone = '237' . $cleanPhone;
                }
            }

            // Créer le message
            $message = "Bonjour, votre annonce \"{$ad['title']}\" a été reportée. "
                . "Elle est actuellement en attente de modération par notre équipe. "
                . "Consultez votre compte Cambizzle pour plus de détails.";

            // Générer le lien
            $link = "https://wa.me/{$cleanPhone}?text=" . urlencode($message);

            return $link;

        } catch (\Exception $e) {
            log_message('error', 'WhatsApp link generation error: ' . $e->getMessage());
            return null;
        }
    }
```

---

## 3️⃣ Controller - Utiliser les notifications

**Fichier:** `app/Controllers/Api/ReportController.php` - **MODIFIER EXISTANT**

Remplacer la méthode `create()`:

```php
    /**
     * POST /api/reports - Créer un signalement
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

            // Gérer les fichiers uploadés
            $files = $this->request->getFiles();
            if (!empty($files['evidence_files'])) {
                $data['evidence_files'] = $files['evidence_files'];
            }

            // Créer le report
            $reportId = $this->reportService->createReport($userId, $data);

            // 🔔 NOTIFIER LE PROPRIÉTAIRE
            $notification = [];
            if (isset($data['reported_ad_id'])) {
                $notification = $this->reportService->notifyAdOwnerOfReport(
                    $data['reported_ad_id'],
                    $reportId
                );
            }

            return $this->created([
                'id' => $reportId,
                'notification' => $notification
            ], 'Signalement créé. Propriétaire notifié.');

        } catch (\InvalidArgumentException $e) {
            return $this->validationError(['error' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return $this->serverError($e->getMessage());
        } catch (\Exception $e) {
            log_message('error', 'Report creation error: ' . $e->getMessage());
            return $this->serverError('Erreur interne du serveur');
        }
    }
```

---

## 4️⃣ NotificationController - CRÉER NOUVEAU

**Fichier:** `app/Controllers/Api/NotificationController.php` - **CRÉER**

```php
<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Services\AuthService;

class NotificationController extends BaseApiController
{
    protected $authService;

    public function __construct()
    {
        $this->authService = service('authService');
    }

    /**
     * GET /api/notifications - Récupérer les notifications non lues
     */
    public function index()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $limit = (int)($this->request->getGet('limit') ?? 50);
            $offset = (int)($this->request->getGet('offset') ?? 0);

            $db = \Config\Database::connect();

            // Récupérer les notifications
            $notifications = $db->table('notifications')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get()
                ->getResult();

            // Compter les non-lues
            $unreadCount = $db->table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->countAllResults();

            return $this->success([
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ], 'Notifications récupérées');

        } catch (\Exception $e) {
            log_message('error', 'Notification fetch error: ' . $e->getMessage());
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/notifications/unread - Compter les non-lues
     */
    public function unreadCount()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $db = \Config\Database::connect();
            $count = $db->table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->countAllResults();

            return $this->success(['unread_count' => $count]);

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/notifications/{id}/read - Marquer comme lue
     */
    public function markAsRead($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID requis']);
            }

            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $db = \Config\Database::connect();

            // Vérifier que c'est la notification de l'utilisateur
            $notification = $db->table('notifications')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->get()
                ->getRow();

            if (!$notification) {
                return $this->notFound('Notification non trouvée');
            }

            // Marquer comme lue
            $db->table('notifications')
                ->where('id', $id)
                ->update([
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s')
                ]);

            return $this->success(null, 'Notification lue');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/notifications/read-all - Marquer toutes comme lues
     */
    public function markAllAsRead()
    {
        try {
            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $db = \Config\Database::connect();
            $db->table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->update([
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s')
                ]);

            return $this->success(null, 'Toutes les notifications marquées comme lues');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/notifications/{id} - Supprimer une notification
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->validationError(['id' => 'ID requis']);
            }

            $token = $this->request->getHeaderLine('Authorization');
            if (!$token) {
                return $this->unauthorized('Token requis');
            }

            $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
            $userId = $payload->user_id;

            $db = \Config\Database::connect();

            // Vérifier que c'est la notification de l'utilisateur
            $notification = $db->table('notifications')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->get()
                ->getRow();

            if (!$notification) {
                return $this->notFound('Notification non trouvée');
            }

            // Supprimer
            $db->table('notifications')
                ->where('id', $id)
                ->delete();

            return $this->success(null, 'Notification supprimée');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
```

---

## 5️⃣ Routes - Ajouter les nouvelles routes

**Fichier:** `app/Config/Routes.php` - **AJOUTER**

```php
// Ajouter après les routes d'authentification (dans le groupe 'api'):

    // Routes pour les notifications
    $routes->group('notifications', ['filter' => 'auth'], function ($routes) {
        $routes->get('/', 'NotificationController::index');
        $routes->get('unread', 'NotificationController::unreadCount');
        $routes->put('(:num)/read', 'NotificationController::markAsRead/$1');
        $routes->put('read-all', 'NotificationController::markAllAsRead');
        $routes->delete('(:num)', 'NotificationController::delete/$1');
        
        // CORS preflight
        $routes->options('/', 'NotificationController::options');
        $routes->options('unread', 'NotificationController::options');
        $routes->options('(:num)/read', 'NotificationController::options');
        $routes->options('read-all', 'NotificationController::options');
        $routes->options('(:num)', 'NotificationController::options');
    });
```

---

## 6️⃣ Exécuter la migration

```bash
php spark migrate
```

---

## 📋 Checklist d'installation

- [ ] 1. Créer le fichier migration `CreateNotificationsTable.php`
- [ ] 2. Ajouter les méthodes au `ReportService.php`
- [ ] 3. Modifier la méthode `create()` dans `ReportController.php`
- [ ] 4. Créer le fichier `NotificationController.php`
- [ ] 5. Ajouter les routes dans `Routes.php`
- [ ] 6. Exécuter `php spark migrate`
- [ ] 7. Tester avec Postman

---

## 🧪 Test avec cURL

### 1. Créer un report (qui notifiera le propriétaire)

```bash
curl -X POST http://localhost:8000/api/reports \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reported_ad_id": 1,
    "report_type": "ad",
    "report_reason": "spam",
    "description": "Annonce spam"
  }'
```

**Réponse:**
```json
{
  "success": true,
  "message": "Signalement créé. Propriétaire notifié.",
  "data": {
    "id": 123,
    "notification": {
      "in_app": true,
      "email": true,
      "whatsapp_link": "https://wa.me/237677123456?text=..."
    }
  }
}
```

### 2. Récupérer les notifications

```bash
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Marquer comme lue

```bash
curl -X PUT http://localhost:8000/api/notifications/1/read \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Compter les non-lues

```bash
curl -X GET http://localhost:8000/api/notifications/unread \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🔧 Configuration optionnelle

### Si vous voulez ajouter SMS (Africa's Talking)

```bash
composer require africastalking/africastalking
```

**Dans .env:**
```env
AFRICAS_TALKING_API_KEY=your_key
AFRICAS_TALKING_USERNAME=sandbox
```

**Ajouter au ReportService:**
```php
private function sendSmsNotification(string $phone, array $ad): bool
{
    try {
        $sms = service('smsService');
        $message = "Votre annonce \"{$ad['title']}\" a été reportée.";
        return $sms->send($phone, $message);
    } catch (\Exception $e) {
        log_message('error', 'SMS error: ' . $e->getMessage());
        return false;
    }
}
```

---

## 🎉 Résultat

✅ Quand un utilisateur crée un report:
1. **In-App** → Notification sauvegardée en BD
2. **Email** → Email envoyé au propriétaire
3. **WhatsApp** → Lien généré (l'utilisateur clique pour ouvrir)

L'utilisateur reporté peut:
- Voir les notifications dans l'app
- Consulter l'historique
- Marquer comme lues
- Supprimer

---

**Aucune dépendance supplémentaire requise! ✨**
