# Documentation - Réinitialisation de Mot de Passe

## Vue d'ensemble

La fonctionnalité de réinitialisation de mot de passe permet aux utilisateurs de récupérer l'accès à leur compte en fournissant leur numéro de téléphone. Le système génère un token sécurisé valide pendant 24 heures.

## Flux du processus

```
1. Utilisateur demande la réinitialisation (numéro de téléphone)
   ↓
2. Système génère un token sécurisé et l'expiration
   ↓
3. Token stocké en base de données
   ↓
4. Utilisateur reçoit le code par SMS* (à implémenter)
   ↓
5. Utilisateur soumet le token + nouveau mot de passe
   ↓
6. Système valide le token et met à jour le mot de passe
   ↓
7. Token est supprimé (utilisé une seule fois)

* À intégrer avec un fournisseur SMS (Twilio, AWS SNS, etc.)
```

## Endpoints API

### 1. POST `/api/auth/forgot-password`

**Demander la réinitialisation du mot de passe**

#### Request
```bash
POST /api/auth/forgot-password
Content-Type: application/json

{
  "phone": "+237677123456"
}
```

#### Response (200 OK)
```json
{
  "success": true,
  "message": "Un code de réinitialisation a été envoyé au numéro associé à votre compte",
  "data": {
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "expires_in": 86400
  }
}
```

#### Response (User Not Found)
Pour la sécurité, même si l'utilisateur n'existe pas, la réponse est identique:
```json
{
  "success": true,
  "message": "Si ce numéro de téléphone existe dans notre système, vous recevrez un code de réinitialisation",
  "data": {}
}
```

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| phone | string | Yes | Numéro de téléphone associé au compte (format: +237XXXXXXXXX) |

---

### 2. POST `/api/auth/reset-password`

**Réinitialiser le mot de passe avec le token**

#### Request
```bash
POST /api/auth/reset-password
Content-Type: application/json

{
  "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
  "password": "nouveauMotDePasse123"
}
```

#### Response (200 OK)
```json
{
  "success": true,
  "message": "Mot de passe réinitialisé avec succès",
  "data": {
    "user": {
      "id_user": 42,
      "first_name": "Jean",
      "last_name": "Dupont",
      "email": "jean@example.com",
      "phone": "+237677123456",
      "is_verified": false,
      "role_id": 2,
      "created_at": "2024-01-15 10:30:00"
    }
  }
}
```

#### Response (Invalid/Expired Token - 400)
```json
{
  "success": false,
  "message": "Code de réinitialisation invalide ou expiré",
  "code": "RESET_TOKEN_INVALID"
}
```

#### Response (Expired Token - 400)
```json
{
  "success": false,
  "message": "Code de réinitialisation expiré. Veuillez faire une nouvelle demande",
  "code": "RESET_TOKEN_INVALID"
}
```

#### Response (Password Too Short - 422)
```json
{
  "success": false,
  "message": "Veuillez corriger les erreurs ci-dessous",
  "errors": {
    "password": "Le mot de passe doit contenir au moins 6 caractères"
  },
  "code": "VALIDATION_ERROR"
}
```

#### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| token | string | Yes | Token de réinitialisation reçu depuis `/forgot-password` |
| password | string | Yes | Nouveau mot de passe (minimum 6 caractères) |

**Alternative:** Accepte aussi `new_password` à la place de `password`

---

## Schéma de Base de Données

### Colonnes ajoutées à la table `users`

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| reset_token | VARCHAR(255) | Yes | NULL | Token sécurisé pour réinitialisation |
| reset_token_expires | DATETIME | Yes | NULL | Date/heure d'expiration du token (24h) |

### Migration
```sql
ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL;
```

Fichier de migration: `app/Database/Migrations/2024-01-01-000000_AddPasswordResetTokens.php`

---

## Configuration de l'Intégration SMS

### Implémentation actuelle
En développement, le code de réinitialisation est retourné dans la réponse (à retirer en production).

```php
// Dans AuthService::forgotPassword()
$resetCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Retourné en dev (à supprimer en production)
return [
    'code' => $resetCode, // À retirer!
    ...
];
```

### Pour la production

#### Option 1: Twilio (recommandé)
```php
// Installer: composer require twilio/sdk

use Twilio\Rest\Client;

private function sendResetCodeViaSMS(string $phone, string $code)
{
    $sid = env('TWILIO_ACCOUNT_SID');
    $token = env('TWILIO_AUTH_TOKEN');
    $from = env('TWILIO_PHONE_NUMBER');
    
    $client = new Client($sid, $token);
    $client->messages->create($phone, [
        'from' => $from,
        'body' => "Votre code de réinitialisation: {$code}. Valide 24h."
    ]);
}
```

#### Option 2: AWS SNS
```php
// Installer: composer require aws/aws-sdk-php

private function sendResetCodeViaSMS(string $phone, string $code)
{
    $sns = new SnsClient([
        'version' => 'latest',
        'region'  => env('AWS_REGION')
    ]);
    
    $sns->publish([
        'Message' => "Votre code de réinitialisation: {$code}. Valide 24h.",
        'PhoneNumber' => $phone
    ]);
}
```

---

## Flux Complet - Exemple

### Étape 1: Demander la réinitialisation
```bash
curl -X POST http://localhost:8000/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"phone": "+237677123456"}'
```

Réponse:
```json
{
  "success": true,
  "message": "Un code de réinitialisation a été envoyé au numéro associé à votre compte",
  "data": {
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "expires_in": 86400
  }
}
```

En développement (temporaire):
```json
{
  "success": true,
  "data": {
    "code": "123456"  // À retirer en production!
  }
}
```

### Étape 2: Valider le code et réinitialiser
```bash
curl -X POST http://localhost:8000/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "password": "MonNouveauMotDePasse123"
  }'
```

Réponse:
```json
{
  "success": true,
  "message": "Mot de passe réinitialisé avec succès",
  "data": {
    "user": {
      "id_user": 42,
      "first_name": "Jean",
      "last_name": "Dupont",
      "phone": "+237677123456",
      ...
    }
  }
}
```

### Étape 3: Se connecter avec le nouveau mot de passe
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+237677123456",
    "password": "MonNouveauMotDePasse123"
  }'
```

---

## Fonctionnalités de Sécurité

✅ **Tokens cryptographiquement sécurisés** - Générés avec `random_bytes(32)` et converti en hexadécimal
✅ **Expiration 24 heures** - Les tokens expirent automatiquement
✅ **Une seule utilisation** - Le token est supprimé après utilisation
✅ **Protection d'énumération** - Même message pour utilisateurs existants et non-existants
✅ **Validation stricte** - Mot de passe minimum 6 caractères
✅ **Logging** - Toutes les tentatives sont enregistrées pour audit
✅ **Password hashing** - Utilise PASSWORD_DEFAULT (bcrypt)

---

## Codes d'Erreur

| Code | HTTP | Description |
|------|------|-------------|
| VALIDATION_ERROR | 422 | Validation des paramètres échouée |
| RESET_TOKEN_INVALID | 400 | Token invalide ou expiré |
| INTERNAL_ERROR | 500 | Erreur serveur |

---

## Points d'Implémentation

### AuthService
- ✅ `forgotPassword(string $phone): array`
- ✅ `resetPassword(string $token, string $newPassword): array`

### AuthController
- ✅ `forgotPassword()`
- ✅ `resetPassword()`

### Routes
- ✅ `POST /api/auth/forgot-password`
- ✅ `POST /api/auth/reset-password`
- ✅ OPTIONS pour CORS preflight

### Base de Données
- ✅ Migration: `AddPasswordResetTokens.php`
- ✅ Colonnes: `reset_token`, `reset_token_expires`

### UserModel
- ✅ `allowedFields` inclut les colonnes de reset

---

## À Faire - Production Ready

- [ ] Intégrer SMS gateway (Twilio ou AWS SNS)
- [ ] Retirer le `'code'` de la réponse `forgotPassword()` en production
- [ ] Ajouter rate limiting sur `/forgot-password` (max 3 demandes par heure)
- [ ] Implémenter double validation: SMS + email optionnel
- [ ] Ajouter notification email de réinitialisation
- [ ] Ajouter historique des tentatives échouées
- [ ] Tester avec de vrais numéros de téléphone

---

## Tests

### Test avec cURL

```bash
# 1. Demander réinitialisation
curl -X POST http://localhost:8000/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"phone": "+237677123456"}'

# 2. Réinitialiser avec le token
curl -X POST http://localhost:8000/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{"token": "TOKEN_FROM_STEP_1", "password": "newpassword123"}'

# 3. Vérifier avec la nouvelle connexion
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "+237677123456", "password": "newpassword123"}'
```

### Postman Collection

Voir `postman/PASSWORD_RESET_COLLECTION.json` pour les requêtes Postman prêtes à l'emploi.

---

## Dépannage

### "Code de réinitialisation invalide ou expiré"
- Le token a peut-être expiré (24h)
- Le token a déjà été utilisé
- Solution: Demander une nouvelle réinitialisation

### "Le numéro de téléphone n'existe pas"
- Vérifier le format du numéro (+237...)
- Assurez-vous que l'utilisateur a bien fourni un téléphone à l'inscription

### SMS non reçu
- Vérifier que le SMS gateway est configuré en production
- En développement, le code est retourné dans la réponse JSON
- Vérifier les logs: `writable/logs/`

---

## Support

Pour plus d'informations sur les autres endpoints d'authentification:
- Voir `API_DOCUMENTATION.md`
- Voir les collections Postman dans `/postman/`
