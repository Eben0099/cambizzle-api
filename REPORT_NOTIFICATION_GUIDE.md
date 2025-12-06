# Guide - Notifier l'Utilisateur quand son Annonce est Reportée

## 📋 Table des matières
1. [Options de Notification](#options-de-notification)
2. [Option 1: WhatsApp (Gratuite avec wa.me)](#option-1-whatsapp-gratuite-avec-wame)
3. [Option 2: Email (Gratuit)](#option-2-email-gratuit)
4. [Option 3: SMS Gratuit](#option-3-sms-gratuit)
5. [Option 4: Notification In-App](#option-4-notification-in-app)
6. [Implémentation Complète](#implémentation-complète)
7. [Bonnes Pratiques](#bonnes-pratiques)

---

## Options de Notification

| Option | Coût | Facilité | Type | Installation |
|--------|------|---------|------|--------------|
| **WhatsApp (wa.me)** | ✅ Gratuit | Très facile | Lien cliquable | Aucun package |
| **Email** | ✅ Gratuit | Facile | Email | Smtp ou Mailgun |
| **SMS Gratuit** | ✅ Gratuit* | Moyen | SMS | Package + API |
| **In-App** | ✅ Gratuit | Très facile | Message BD | Aucun |
| **Firebase** | ❌ Payant | Moyen | Push notification | Package Firebase |

---

## Option 1: WhatsApp (Gratuite avec wa.me)

### 🎯 Concept
Utiliser `wa.me` pour générer un lien WhatsApp que l'utilisateur peut cliquer pour recevoir les informations.

### ✅ Avantages
- ✅ **Complètement gratuit** - Aucun coût API
- ✅ **Aucun package à installer** - Juste du PHP natif
- ✅ **Immédiat** - Lien direct vers WhatsApp
- ✅ **L'utilisateur en contrôle** - Il clique librement
- ✅ **Pré-rempli** - Le message peut être pré-rédigé
- ✅ **Multi-plateforme** - Web, mobile, desktop

### ❌ Inconvénients
- ❌ L'utilisateur doit avoir WhatsApp
- ❌ L'utilisateur clique sur le lien (pas automatique)
- ❌ Ne sauvegarde pas de données
- ❌ Dépend de WhatsApp Web

### 📝 Exemple de lien wa.me

```
https://wa.me/237677123456?text=Bonjour, votre annonce a été reportée
```

### 🔧 Implémentation PHP

```php
// Dans ReportService.php - Ajouter après la création du report

public function notifyAdOwnerViaWhatsApp(int $reportedAdId, int $reporterId): string
{
    // Récupérer l'annonce
    $ad = $this->adModel->find($reportedAdId);
    if (!$ad) {
        return null;
    }

    // Récupérer l'utilisateur propriétaire de l'annonce
    $adOwner = $this->userModel->find($ad->user_id);
    if (!$adOwner || empty($adOwner->phone)) {
        return null;
    }

    // Formater le numéro (enlever les caractères spéciaux)
    $phone = preg_replace('/[^0-9]/', '', $adOwner->phone);

    // Message personnalisé
    $message = "Bonjour, votre annonce '{$ad->title}' a été reportée. ";
    $message .= "Elle est actuellement en attente de modération. ";
    $message .= "Pour plus d'informations, consultez votre compte Cambizzle.";

    // Encoder le message pour l'URL
    $encodedMessage = urlencode($message);

    // Générer le lien wa.me
    $whatsappLink = "https://wa.me/{$phone}?text={$encodedMessage}";

    return $whatsappLink;
}
```

### 🎨 Utilisation dans le Controller

```php
// Dans ReportController::create()

public function create()
{
    try {
        // ... code existant ...

        $reportId = $this->reportService->createReport($userId, $data);

        // Notifier via WhatsApp
        if (isset($data['reported_ad_id'])) {
            $whatsappLink = $this->reportService->notifyAdOwnerViaWhatsApp(
                $data['reported_ad_id'],
                $userId
            );

            // Retourner le lien dans la réponse
            return $this->created([
                'id' => $reportId,
                'whatsapp_notification_link' => $whatsappLink
            ], 'Signalement créé. Notification envoyée.');
        }

        return $this->created(['id' => $reportId], 'Signalement créé avec succès');

    } catch (\InvalidArgumentException $e) {
        // ... gestion d'erreur ...
    }
}
```

### 📱 Frontend - Afficher le bouton

```html
<!-- Bouton WhatsApp pour cliquer -->
<button onclick="window.open('{{ whatsappLink }}', '_blank')">
    📱 Notifier via WhatsApp
</button>

<!-- Ou lien direct -->
<a href="{{ whatsappLink }}" target="_blank" class="btn btn-success">
    Ouvrir WhatsApp
</a>
```

---

## Option 2: Email (Gratuit)

### 🎯 Concept
Envoyer un email automatique avec les détails du report.

### ✅ Avantages
- ✅ **Gratuit** - Avec SMTP (Gmail, Outlook, etc.)
- ✅ **Automatique** - Aucune action de l'utilisateur
- ✅ **Professionnel** - Email formaté
- ✅ **Historique** - Gardé dans la boîte mail
- ✅ **Traçable** - Logs d'envoi

### 🔧 Implémentation

#### 1. Configuration SMTP (dans .env)

```env
# .env
email.fromEmail = noreply@cambizzle.com
email.fromName = Cambizzle Team
email.protocol = smtp
email.SMTPHost = smtp.gmail.com
email.SMTPUser = votre-email@gmail.com
email.SMTPPass = votre-app-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

#### 2. Service d'Email

```php
// app/Services/NotificationService.php

<?php

namespace App\Services;

class NotificationService
{
    protected $email;

    public function __construct()
    {
        $this->email = service('email');
    }

    /**
     * Notifier le propriétaire de l'annonce par email
     */
    public function notifyAdOwnerByEmail(array $adOwner, array $ad, array $report): bool
    {
        try {
            // Template du message
            $subject = "Votre annonce a été reportée - Action requise";

            $message = view('emails/ad_reported', [
                'adOwner' => $adOwner,
                'ad' => $ad,
                'report' => $report
            ]);

            // Envoyer l'email
            return $this->email
                ->setFrom(env('email.fromEmail'), env('email.fromName'))
                ->setTo($adOwner['email'])
                ->setSubject($subject)
                ->setMessage($message)
                ->send();

        } catch (\Exception $e) {
            log_message('error', 'Email notification error: ' . $e->getMessage());
            return false;
        }
    }
}
```

#### 3. Template Email

```html
<!-- app/Views/emails/ad_reported.html -->

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background: #FF6B6B; color: white; padding: 20px; }
        .content { padding: 20px; }
        .footer { background: #f5f5f5; padding: 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚠️ Annonce reportée</h2>
        </div>

        <div class="content">
            <p>Bonjour <?= $adOwner['first_name'] ?>,</p>

            <p>
                Nous vous informons que votre annonce
                <strong>"<?= $ad['title'] ?>"</strong>
                a été reportée par un utilisateur.
            </p>

            <h3>Détails du report:</h3>
            <ul>
                <li><strong>Raison:</strong> <?= $report['report_reason'] ?></li>
                <li><strong>Type:</strong> <?= $report['report_type'] ?></li>
                <li><strong>Description:</strong> <?= $report['description'] ?></li>
            </ul>

            <p>
                <strong>Statut actuel:</strong>
                Votre annonce est en attente de modération.
            </p>

            <p>
                Notre équipe examinera le rapport. Si le contenu est conforme à nos
                conditions d'utilisation, votre annonce restera active.
                Sinon, elle pourra être supprimée.
            </p>

            <p>
                Consultez votre compte Cambizzle pour plus de détails.
            </p>

            <hr>
            <p>Cordialement,<br>L'équipe Cambizzle</p>
        </div>

        <div class="footer">
            <p>© 2024 Cambizzle. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
```

#### 4. Utiliser dans ReportService

```php
// app/Services/ReportService.php

public function createReport(int $reporterId, array $data): int
{
    // ... validation et création du report ...

    $reportId = $this->reportModel->insert($reportData, true);

    // Récupérer les infos pour la notification
    if (isset($data['reported_ad_id'])) {
        $ad = $this->adModel->find($data['reported_ad_id']);
        $adOwner = $this->userModel->find($ad->user_id);
        $report = $this->reportModel->find($reportId);

        // Envoyer email
        $notificationService = service('notificationService');
        $notificationService->notifyAdOwnerByEmail(
            $adOwner->toArray(),
            $ad->toArray(),
            $report->toArray()
        );
    }

    return $reportId;
}
```

---

## Option 3: SMS Gratuit

### 🎯 Fournisseurs SMS Gratuits
1. **Twilio** - 1000 SMS gratuits/mois avec compte trial
2. **AWS SNS** - Gratuit avec crédit d'essai
3. **Nexmo** - Gratuit avec crédit de bienvenue
4. **Africa's Talking** - Meilleur pour Cameroun (237)

### 🔧 Avec Africa's Talking (Recommandé pour Cameroun)

#### 1. Installation

```bash
composer require africastalking/africastalking
```

#### 2. Configuration .env

```env
AFRICAS_TALKING_API_KEY=your_api_key
AFRICAS_TALKING_USERNAME=sandbox
```

#### 3. Service SMS

```php
// app/Services/SmsService.php

<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;

class SmsService
{
    protected $at;

    public function __construct()
    {
        $this->at = new AfricasTalking(
            env('AFRICAS_TALKING_USERNAME'),
            env('AFRICAS_TALKING_API_KEY')
        );
    }

    /**
     * Envoyer SMS de notification de report
     */
    public function notifyAdOwnerBySms(string $phone, array $ad): bool
    {
        try {
            $sms = $this->at->sms();

            $message = "Bonjour, votre annonce '{$ad['title']}' a été reportée. ";
            $message .= "Elle est en attente de modération. ";
            $message .= "Consultez votre compte Cambizzle pour plus d'infos.";

            $result = $sms->send([
                'recipients' => [$phone],
                'message' => $message
            ]);

            if ($result['status'] === 'success') {
                log_message('info', "SMS sent to {$phone}");
                return true;
            }

            log_message('warning', "SMS failed: " . json_encode($result));
            return false;

        } catch (\Exception $e) {
            log_message('error', 'SMS error: ' . $e->getMessage());
            return false;
        }
    }
}
```

---

## Option 4: Notification In-App

### 🎯 Concept
Stocker les notifications dans une table BD et les afficher dans l'app.

### 🔧 Implémentation

#### 1. Créer la migration

```php
<?php
namespace App\Database\Migrations;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'type' => ['type' => 'VARCHAR', 'constraint' => 50], // 'ad_reported', 'report_resolved', etc
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'message' => ['type' => 'TEXT'],
            'data' => ['type' => 'JSON', 'null' => true], // info supplémentaire
            'is_read' => ['type' => 'TINYINT', 'default' => 0],
            'read_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id_user', 'CASCADE');
        $this->forge->createTable('notifications');
    }

    public function down()
    {
        $this->forge->dropTable('notifications');
    }
}
```

#### 2. Service de Notification

```php
// app/Services/NotificationService.php

public function notifyAdReported(int $adId, int $reportId): bool
{
    $ad = $this->adModel->find($adId);
    if (!$ad) return false;

    $notification = [
        'user_id' => $ad->user_id,
        'type' => 'ad_reported',
        'title' => 'Votre annonce a été reportée',
        'message' => "Votre annonce \"{$ad->title}\" a reçu un signalement.",
        'data' => json_encode([
            'ad_id' => $adId,
            'report_id' => $reportId
        ]),
        'created_at' => date('Y-m-d H:i:s')
    ];

    return db_connect()
        ->table('notifications')
        ->insert($notification);
}
```

#### 3. Endpoint API

```php
// app/Controllers/Api/NotificationController.php

public function getMyNotifications()
{
    $token = $this->request->getHeaderLine('Authorization');
    $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
    $userId = $payload->user_id;

    $notifications = db_connect()
        ->table('notifications')
        ->where('user_id', $userId)
        ->where('is_read', 0)
        ->orderBy('created_at', 'DESC')
        ->get()
        ->getResult();

    return $this->success([
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
}

public function markAsRead($id)
{
    db_connect()
        ->table('notifications')
        ->where('id', $id)
        ->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);

    return $this->success(null, 'Notification lue');
}
```

---

## Implémentation Complète

### 📊 Flux Recommandé (Combiné)

**Meilleure pratique = Utiliser PLUSIEURS notifications:**

1. **In-App** - Notification rapide et visible
2. **WhatsApp** - Contact immédiat sans dépendances
3. **Email** - Officiel et tracé

### 🔧 Code Complet

```php
// app/Services/ReportService.php - MODIFIÉ

public function createReport(int $reporterId, array $data): int
{
    // ... validation existante ...

    // Créer le report
    $reportData = [
        'reporter_id' => $reporterId,
        'report_type' => $data['report_type'],
        'report_reason' => $data['report_reason'],
        'description' => $data['description'],
        'status' => 'pending'
    ];

    if (isset($data['reported_ad_id'])) {
        $reportData['reported_ad_id'] = $data['reported_ad_id'];
    }

    $reportId = $this->reportModel->insert($reportData, true);

    // 🔔 ENVOYER NOTIFICATIONS
    if (isset($data['reported_ad_id'])) {
        $this->notifyAdOwner($data['reported_ad_id'], $reportId);
    }

    return $reportId;
}

/**
 * Notifier le propriétaire de l'annonce reportée
 */
private function notifyAdOwner(int $adId, int $reportId): void
{
    $ad = $this->adModel->find($adId);
    if (!$ad) return;

    $adOwner = $this->userModel->find($ad->user_id);
    if (!$adOwner) return;

    // 1️⃣ NOTIFICATION IN-APP
    $this->addInAppNotification($adOwner['id_user'], $ad);

    // 2️⃣ EMAIL
    if (!empty($adOwner['email'])) {
        $this->sendEmailNotification($adOwner, $ad);
    }

    // 3️⃣ WhatsApp
    if (!empty($adOwner['phone'])) {
        $this->sendWhatsAppNotification($adOwner['phone'], $ad);
    }

    // 4️⃣ SMS (optionnel)
    // $this->sendSmsNotification($adOwner['phone'], $ad);
}

/**
 * 1️⃣ Ajouter notification In-App
 */
private function addInAppNotification(int $userId, array $ad): void
{
    try {
        db_connect()->table('notifications')->insert([
            'user_id' => $userId,
            'type' => 'ad_reported',
            'title' => 'Votre annonce a été reportée',
            'message' => "Votre annonce \"{$ad['title']}\" a reçu un signalement.",
            'data' => json_encode(['ad_id' => $ad['id_ad']]),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        log_message('error', 'In-app notification error: ' . $e->getMessage());
    }
}

/**
 * 2️⃣ Email de notification
 */
private function sendEmailNotification(array $adOwner, array $ad): void
{
    try {
        $emailService = service('email');
        $subject = "Alerte: Votre annonce a été reportée";
        
        $message = "
            <h2>Alerte de signalement</h2>
            <p>Bonjour {$adOwner['first_name']},</p>
            <p>Votre annonce <strong>\"{$ad['title']}\"</strong> a été reportée.</p>
            <p>Elle est actuellement en attente de modération.</p>
            <p>Consultez votre compte Cambizzle pour plus d'infos.</p>
        ";

        $emailService->setFrom('noreply@cambizzle.com', 'Cambizzle')
            ->setTo($adOwner['email'])
            ->setSubject($subject)
            ->setMessage($message)
            ->send();

    } catch (\Exception $e) {
        log_message('error', 'Email notification error: ' . $e->getMessage());
    }
}

/**
 * 3️⃣ Lien WhatsApp
 */
private function sendWhatsAppNotification(string $phone, array $ad): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    $message = "Bonjour, votre annonce \"{$ad['title']}\" a été reportée. "
        . "Elle est en attente de modération. "
        . "Consultez votre compte Cambizzle.";

    return "https://wa.me/{$phone}?text=" . urlencode($message);
}

/**
 * 4️⃣ SMS Notification (optionnel)
 */
private function sendSmsNotification(string $phone, array $ad): void
{
    // Utiliser Africa's Talking ou autre provider
    $smsService = service('smsService');
    $smsService->notifyAdOwnerBySms($phone, $ad);
}
```

### 🎨 Utilisation dans le Controller

```php
// app/Controllers/Api/ReportController.php - MODIFIÉ

public function create()
{
    try {
        $token = $this->request->getHeaderLine('Authorization');
        if (!$token) {
            return $this->unauthorized('Token requis');
        }

        $payload = $this->authService->validateToken(str_replace('Bearer ', '', $token));
        $userId = $payload->user_id;

        $data = $this->request->getJSON(true);

        // Créer le report
        $reportId = $this->reportService->createReport($userId, $data);

        // Générer le lien WhatsApp
        $whatsappLink = null;
        if (isset($data['reported_ad_id'])) {
            $ad = (new \App\Models\AdModel())->find($data['reported_ad_id']);
            $adOwner = (new \App\Models\UserModel())->find($ad->user_id);
            $phone = preg_replace('/[^0-9]/', '', $adOwner->phone);
            $message = urlencode("Votre annonce a été reportée. Consultez Cambizzle pour les détails.");
            $whatsappLink = "https://wa.me/{$phone}?text={$message}";
        }

        return $this->created([
            'id' => $reportId,
            'notification' => [
                'in_app' => 'Notification envoyée',
                'email' => 'Email envoyé',
                'whatsapp_link' => $whatsappLink
            ]
        ], 'Signalement créé. Propriétaire notifié.');

    } catch (\Exception $e) {
        return $this->serverError($e->getMessage());
    }
}
```

---

## Bonnes Pratiques

### ✅ À FAIRE

1. **Combiner les canaux** - In-App + Email + WhatsApp
2. **Logging** - Enregistrer tous les envois
3. **Délai de sécurité** - Attendre avant de suspendre
4. **Transparence** - Expliquer les raisons du report
5. **Droit de réponse** - Permettre à l'utilisateur de répondre
6. **Retry automatique** - Réessayer si l'email échoue
7. **Opt-in** - Respecter les préférences de notification

### ❌ À ÉVITER

1. ❌ Ne pas spammer - 1 notification par report
2. ❌ Ne pas révéler le reporter - Garder l'anonymat
3. ❌ Ne pas être agressif - Ton professionnel
4. ❌ Ne pas promettre une action avant audit
5. ❌ Ne pas envoyer à minuit
6. ❌ Ne pas faire de duplicate notifications

### 🔐 Sécurité

```php
// Anonymiser le reporter
$message = "Votre annonce a été reportée par un utilisateur"
    . " pour: {$report['report_reason']}.";

// Ne PAS dire:
// "Utilisateur #123 vous a reporté"

// Rate limiting
if ($this->hasTooManyReports($userId)) {
    throw new \RuntimeException('Trop de reports. Attendez 24h.');
}
```

---

## Récapitulatif Rapide

### 🏆 Meilleure Solution GRATUITE et SIMPLE
**→ WhatsApp (wa.me) + In-App**

```php
// Aucun package
// Aucune configuration
// Gratuit à 100%
// Instant

$whatsappLink = "https://wa.me/{$phone}?text=" . urlencode($message);
db_connect()->table('notifications')->insert($notificationData);
```

### 📧 Pour professionnel
**→ Email + WhatsApp + In-App**

Meilleure couverture avec peu de coût.

### 📱 Pour Cameroun spécifiquement
**→ WhatsApp + Africa's Talking SMS**

Préféré localement, faible coût.

---

**Conclusion:** Utilisez au minimum WhatsApp (gratuit, aucun package) + In-App (gratuit, stocké en BD).
