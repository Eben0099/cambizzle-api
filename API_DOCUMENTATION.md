# Cambizzle API - Documentation Complète

## Vue d'ensemble

Cambizzle est une API REST complète pour une plateforme de marketplace développée avec **CodeIgniter 4** et **PHP 8.1+**. Cette API permet la gestion complète d'annonces, d'utilisateurs, de messages, de signalements et d'un système de parrainage.

## Architecture

### Technologies utilisées
- **Framework**: CodeIgniter 4.4
- **PHP**: Version 8.1 minimum
- **Base de données**: MySQL/MariaDB
- **Authentification**: JWT (Firebase JWT)
- **Upload de fichiers**: Gestion intégrée avec validation
- **Tests**: PHPUnit

### Structure du projet
```
app/
├── Controllers/Api/     # Contrôleurs REST API
├── Entities/           # Entités avec logique métier
├── Models/            # Modèles de données
├── Services/          # Logique métier réutilisable
├── Validation/        # Règles de validation personnalisées
├── Filters/           # Filtres middleware (Auth, CORS)
└── Database/Migrations/ # Migrations base de données
```

## Authentification

L'API utilise l'authentification JWT. Tous les endpoints protégés nécessitent un token Bearer dans l'en-tête Authorization.

### Endpoints d'authentification

#### POST /api/auth/register
Inscription d'un nouvel utilisateur.

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "password": "password123",
  "phone": "+33123456789"
}
```

**Response (201):**
```json
{
  "status": "success",
  "message": "Utilisateur créé avec succès",
  "data": {
    "user": {...},
    "token": "jwt_token_here"
  }
}
```

#### POST /api/auth/login
Connexion utilisateur.

**Request Body:**
```json
{
  "email": "john.doe@example.com",
  "password": "password123"
}
```

#### GET /api/auth/me
Récupération du profil utilisateur connecté.

**Headers:**
```
Authorization: Bearer {token}
```

## Gestion des utilisateurs

### Endpoints utilisateurs

#### GET /api/v1/users
Liste paginée des utilisateurs (Admin seulement).

**Query Parameters:**
- `page`: numéro de page (défaut: 1)
- `per_page`: éléments par page (défaut: 10)
- `search`: terme de recherche

#### GET /api/v1/users/{id}
Détails d'un utilisateur.

#### PUT /api/v1/users/{id}
Mise à jour d'un utilisateur.

**Request Body:**
```json
{
  "first_name": "John Updated",
  "last_name": "Doe Updated",
  "phone": "+33111222333"
}
```

#### PUT /api/v1/users/{id}/change-password
Changement de mot de passe.

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "new_password": "newpassword123"
}
```

#### POST /api/v1/users/{id}/verify-identity
Vérification d'identité avec upload de document.

**Request Body (Form Data):**
- `document_type`: "CNI" ou "Passeport"
- `document_number`: numéro du document
- `document`: fichier image

## Gestion des annonces

### Endpoints annonces

#### GET /api/ads
Liste des annonces avec filtres et pagination.

**Query Parameters:**
- `q`: recherche textuelle
- `category_id`: ID de catégorie
- `subcategory_id`: ID de sous-catégorie
- `location_id`: ID de localisation
- `min_price`: prix minimum
- `max_price`: prix maximum
- `sort_by`: champ de tri (created_at, price, view_count)
- `sort_order`: ordre (ASC, DESC)
- `page`: numéro de page
- `limit`: éléments par page

#### GET /api/ads/{id}
Détails d'une annonce avec photos et informations liées.

#### POST /api/ads
Création d'une annonce.

**Request Body:**
```json
{
  "title": "iPhone 13 Pro Max 256GB",
  "description": "Excellent état, boîte et accessoires inclus",
  "price": 800.00,
  "subcategory_id": 2,
  "location_id": 1,
  "brand_id": 1,
  "is_negotiable": true
}
```

#### PUT /api/ads/{id}
Mise à jour d'une annonce.

#### DELETE /api/ads/{id}
Suppression d'une annonce.

#### POST /api/ads/{id}/photos
Upload de photos pour une annonce.

**Request Body (Form Data):**
- `photos[]`: fichiers image (multiple)

#### GET /api/ads/{id}/reviews
Récupération des avis d'une annonce.

## Gestion des messages

### Endpoints messages

#### GET /api/messages
Messages de l'utilisateur connecté.

#### POST /api/messages
Envoi d'un message.

**Request Body:**
```json
{
  "ad_id": 1,
  "content": "Bonjour, votre annonce m'intéresse",
  "type": "message"
}
```

#### POST /api/messages (Avis)
Envoi d'un avis avec note.

**Request Body:**
```json
{
  "ad_id": 1,
  "content": "Très bon vendeur, article conforme",
  "type": "review",
  "rating": 5
}
```

#### GET /api/messages/{id}
Détails d'un message avec réponses.

#### PUT /api/messages/{id}/read
Marquer un message comme lu.

#### DELETE /api/messages/{id}
Supprimer un message.

#### GET /api/messages/ad/{adId}
Messages d'une annonce spécifique.

#### GET /api/messages/unread/count
Nombre de messages non lus.

## Système de signalements

### Endpoints signalements

#### POST /api/reports
Créer un signalement.

**Request Body:**
```json
{
  "reported_ad_id": 1,
  "report_type": "spam",
  "report_reason": "Contenu inapproprié",
  "description": "Cette annonce contient du contenu inapproprié"
}
```

#### GET /api/reports
Signalements de l'utilisateur.

#### GET /api/reports/{id}
Détails d'un signalement.

#### GET /api/reports/admin/pending (Admin)
Signalements en attente de modération.

#### PUT /api/reports/{id}/resolve (Admin)
Résoudre un signalement.

#### PUT /api/reports/{id}/dismiss (Admin)
Rejeter un signalement.

#### GET /api/reports/stats (Admin)
Statistiques des signalements.

## Système de parrainage

### Endpoints parrainage

#### GET /api/referrals
Codes de parrainage de l'utilisateur.

#### POST /api/referrals
Créer un code de parrainage.

**Request Body:**
```json
{
  "description": "Code pour mes amis",
  "max_uses": 50,
  "bonus_amount": 10.00
}
```

#### POST /api/referrals/use
Utiliser un code de parrainage.

**Request Body:**
```json
{
  "code": "ABC123DEF",
  "ad_id": 1
}
```

#### GET /api/referrals/stats
Statistiques de parrainage.

## Administration

### Endpoints admin

#### GET /api/admin/dashboard
Tableau de bord administrateur avec statistiques complètes.

**Response:**
```json
{
  "users": {
    "total": 1250,
    "suspended": 15,
    "verified": 890,
    "deleted": 5,
    "active": 1230,
    "new_this_week": 45
  },
  "ads": {
    "total": 3200,
    "pending": 25,
    "approved": 3100,
    "rejected": 75,
    "total_views": 125000,
    "new_this_week": 120,
    "by_status": [...]
  },
  "reports": {...},
  "activity": {...},
  "top_categories": [...]
}
```

#### GET /api/admin/moderation-logs
Logs de toutes les actions de modération avec pagination et filtres.

**Query Parameters:**
- `moderator_id`: ID de l'admin
- `action_type`: type d'action (ad_approve, user_suspend, etc.)
- `target_type`: type de cible (ad, user)
- `target_id`: ID de la cible
- `limit`, `offset`: pagination

#### GET /api/admin/reporting/global-stats
Statistiques globales détaillées de la plateforme.

#### GET /api/admin/reporting/detailed-stats
Statistiques détaillées pour une période donnée.

**Query Parameters:**
- `start_date`: date de début (YYYY-MM-DD)
- `end_date`: date de fin (YYYY-MM-DD)

#### GET /api/admin/reporting/export
Export des données pour reporting externe.

**Query Parameters:**
- `type`: ads, users, messages, reports
- `start_date`, `end_date`: période
- `format`: json ou csv

### Gestion des utilisateurs

#### GET /api/admin/users
Liste paginée des utilisateurs avec filtres.

**Query Parameters:**
- `per_page`, `page`: pagination
- `search`: recherche textuelle
- `is_active`, `is_suspended`, `is_identity_verified`: filtres

#### PUT /api/admin/users/{id}/verify-identity
Vérifier manuellement l'identité d'un utilisateur.

**Request Body:**
```json
{
  "notes": "Documents validés - CNI et justificatif de domicile conformes"
}
```

#### PUT /api/admin/users/{id}/reject-identity
Rejeter la vérification d'identité.

**Request Body:**
```json
{
  "reason": "Document illisible",
  "notes": "Photo de mauvaise qualité"
}
```

#### PUT /api/admin/users/{id}/suspend
Suspendre un utilisateur.

**Request Body:**
```json
{
  "reason": "Violation des conditions d'utilisation",
  "notes": "Spam répété"
}
```

#### PUT /api/admin/users/{id}/unsuspend
Réactiver un utilisateur suspendu.

**Request Body:**
```json
{
  "notes": "Période de suspension écoulée"
}
```

#### DELETE /api/admin/users/{id}
Supprimer définitivement un utilisateur (soft delete).

**Request Body:**
```json
{
  "reason": "Demande de suppression du compte",
  "notes": "Utilisateur a fait une demande explicite"
}
```

### Modération des annonces

#### GET /api/admin/ads/pending
Liste des annonces en attente de modération.

#### PUT /api/admin/ads/{id}/approve
Approuver une annonce.

**Request Body:**
```json
{
  "notes": "Annonce conforme aux règles"
}
```

#### PUT /api/admin/ads/{id}/reject
Rejeter une annonce.

**Request Body:**
```json
{
  "reason": "Contenu inapproprié",
  "notes": "Photo suggestive"
}
```

### Gestion des référentiels

#### GET /api/admin/referentials/categories
Liste des catégories avec pagination.

#### POST /api/admin/referentials/categories
Créer une catégorie.

**Request Body:**
```json
{
  "name": "Véhicules",
  "slug": "vehicules",
  "icon_path": "icons/car.png",
  "is_active": true,
  "display_order": 1
}
```

#### PUT /api/admin/referentials/categories/{id}
Modifier une catégorie.

#### DELETE /api/admin/referentials/categories/{id}
Supprimer une catégorie (si pas de sous-catégories associées).

#### GET /api/admin/referentials/subcategories
Liste des sous-catégories.

#### POST /api/admin/referentials/subcategories
Créer une sous-catégorie.

**Request Body:**
```json
{
  "category_id": 1,
  "name": "Voitures",
  "slug": "voitures",
  "is_active": true,
  "display_order": 1
}
```

#### GET /api/admin/referentials/filters/{subcategoryId}
Liste des filtres pour une sous-catégorie.

#### POST /api/admin/referentials/filters
Créer un filtre.

**Request Body:**
```json
{
  "subcategory_id": 1,
  "name": "Marque",
  "type": "select",
  "is_required": false,
  "display_order": 1
}
```

#### GET /api/admin/referentials/brands
Liste des marques.

#### POST /api/admin/referentials/brands
Créer une marque.

**Request Body:**
```json
{
  "subcategory_id": 1,
  "name": "Peugeot",
  "description": "Constructeur automobile français",
  "logo_url": "logos/peugeot.png",
  "is_active": true
}
```

### Gestion des promotions

#### GET /api/admin/promotions/packs
Liste des packs promotionnels.

#### POST /api/admin/promotions/packs
Créer un pack promotionnel.

**Request Body:**
```json
{
  "name": "Pack Premium",
  "slug": "pack-premium",
  "description": "Visibilité maximale pendant 30 jours",
  "price": 19.99,
  "duration_days": 30,
  "features": ["badge_premium", "top_annonce", "stats_detaillees"],
  "is_featured": true,
  "is_active": true,
  "display_order": 1
}
```

#### PUT /api/admin/promotions/packs/{id}
Modifier un pack promotionnel.

#### DELETE /api/admin/promotions/packs/{id}
Supprimer un pack promotionnel.

#### GET /api/admin/promotions/active
Liste des promotions actives.

#### POST /api/admin/promotions/activate
Activer une promotion pour une annonce.

**Request Body:**
```json
{
  "ad_id": 123,
  "pack_id": 1,
  "user_id": 456
}
```

#### PUT /api/admin/promotions/{id}/deactivate
Désactiver une promotion.

#### GET /api/admin/promotions/stats
Statistiques des promotions.

## Catégories et Marques

### Endpoints catégories

#### GET /api/categories
Liste des catégories.

#### GET /api/categories/{id}
Détails d'une catégorie.

#### GET /api/categories/{id}/subcategories
Sous-catégories d'une catégorie.

### Endpoints marques

#### GET /api/brands
Liste des marques.

**Query Parameters:**
- `subcategory_id`: filtrer par sous-catégorie

#### GET /api/brands/{id}
Détails d'une marque.

## Modèle de données

### Tables principales

#### Users (Utilisateurs)
```sql
- id_user (PK)
- role_id (FK)
- slug (unique)
- first_name, last_name
- email (unique), phone (unique)
- password_hash
- photo_url
- otp_code, otp_expires_at
- is_verified, verification_token
- google_id, facebook_id
- is_identity_verified, identity_document_*
- created_at, updated_at
```

#### Ads (Annonces)
```sql
- id (PK)
- user_id (FK), location_id (FK), subcategory_id (FK), brand_id (FK)
- slug (unique), title, description
- price, original_price, discount_percentage, has_discount
- is_negotiable, referral_code
- status, moderation_status, moderation_notes
- view_count, created_at, updated_at, expires_at
```

#### Messages (Messages & Avis)
```sql
- id (PK)
- user_id (FK), ad_id (FK), parent_id (FK)
- type, content, rating, images, status
- created_at
```

#### Reports (Signalements)
```sql
- id (PK)
- reporter_id (FK), reported_user_id (FK), reported_ad_id (FK)
- report_type, report_reason, description
- evidence_files, status, admin_notes, handled_by, handled_at
- created_at
```

#### Referral Codes (Codes de parrainage)
```sql
- id (PK)
- code (unique), user_id (FK)
- description, max_uses, current_uses, bonus_amount
- is_active, expires_at, created_at
```

## Codes d'erreur

### Codes HTTP
- **200**: Succès
- **201**: Créé avec succès
- **400**: Requête invalide
- **401**: Non autorisé
- **403**: Interdit
- **404**: Ressource non trouvée
- **422**: Erreur de validation
- **500**: Erreur serveur

### Structure des erreurs
```json
{
  "status": "error",
  "message": "Description de l'erreur",
  "errors": {
    "field_name": "Message d'erreur spécifique"
  }
}
```

## Sécurité

### Mesures de sécurité implémentées
- **JWT Authentication** avec expiration
- **Validation des données** côté serveur
- **Protection CSRF** intégrée
- **Rate limiting** (recommandé)
- **CORS** configuré
- **Upload sécurisé** avec validation de type et taille
- **Hachage des mots de passe** (bcrypt)
- **Logs de sécurité**

### Bonnes pratiques
- Utilisez toujours HTTPS en production
- Validez toutes les entrées utilisateur
- Implémentez un rate limiting
- Surveillez les logs de sécurité
- Utilisez des tokens JWT avec expiration courte

## Tests

### Collection Postman
Importez le fichier `Cambizzle_API_Full.postman_collection.json` dans Postman pour tester tous les endpoints.

### Variables d'environnement Postman
- `base_url`: URL de base de l'API (ex: http://localhost:8080)
- `auth_token`: Token JWT utilisateur
- `admin_token`: Token JWT administrateur
- `user_id`: ID utilisateur connecté

### Commandes de test
```bash
# Exécuter tous les tests
./vendor/bin/phpunit

# Tests avec couverture
./vendor/bin/phpunit --coverage-html ./tests/coverage
```

## Déploiement

### Prérequis serveur
- PHP 8.1+
- MySQL/MariaDB 5.7+
- Extensions PHP: mysqli, mbstring, intl, curl, gd
- Composer
- Node.js (pour les assets frontend)

### Variables d'environnement
```env
# Base de données
database.default.hostname = localhost
database.default.database = cambizzle
database.default.username = your_db_user
database.default.password = your_db_password

# JWT
jwt.key = your_jwt_secret_key

# Email (optionnel)
email.fromEmail = noreply@cambizzle.com
email.SMTPHost = smtp.gmail.com
email.SMTPUser = your_email@gmail.com
email.SMTPPass = your_app_password
```

### Commandes de déploiement
```bash
# Installation des dépendances
composer install

# Configuration
cp env .env
# Éditer .env avec vos paramètres

# Migrations base de données
php spark migrate

# Seeds (données de test)
php spark db:seed

# Permissions
chmod -R 755 writable/
chmod -R 755 public/uploads/
```

## Support et maintenance

### Logs
Les logs sont stockés dans `writable/logs/`. Surveillez:
- Erreurs PHP (`log-YYYY-MM-DD.php`)
- Logs de sécurité
- Logs d'activités utilisateurs

### Monitoring recommandé
- Temps de réponse des API
- Taux d'erreur par endpoint
- Utilisation de la base de données
- Espace disque (uploads)

### Mise à jour
```bash
# Mise à jour des dépendances
composer update

# Nouvelles migrations
php spark migrate

# Clear cache
php spark cache:clear
```

---

**Version**: 1.0.0
**Date**: Décembre 2024
**Auteur**: Équipe Cambizzle
