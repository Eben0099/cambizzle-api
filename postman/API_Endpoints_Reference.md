# Cambizzle API - Référence Rapide des Endpoints

## 📋 Liste Complète des Endpoints

### 🔐 Authentification
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/auth/register` | Inscription utilisateur | ❌ |
| POST | `/api/auth/login` | Connexion utilisateur | ❌ |
| GET | `/api/auth/me` | Profil utilisateur connecté | ✅ JWT |

### 👥 Gestion des Utilisateurs
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/v1/users` | Liste utilisateurs (Admin) | ✅ Admin |
| GET | `/api/v1/users/{id}` | Détails utilisateur | ✅ JWT |
| PUT | `/api/v1/users/{id}` | Mise à jour utilisateur | ✅ JWT |
| PUT | `/api/v1/users/{id}/change-password` | Changer mot de passe | ✅ JWT |
| POST | `/api/v1/users/{id}/verify-identity` | Upload vérification identité | ✅ JWT |
| PUT | `/api/admin/users/{id}/verify-identity` | Vérifier identité (Admin) | ✅ Admin |
| PUT | `/api/admin/users/{id}/reject-identity` | Rejeter vérification (Admin) | ✅ Admin |
| PUT | `/api/admin/users/{id}/suspend` | Suspendre utilisateur (Admin) | ✅ Admin |
| PUT | `/api/admin/users/{id}/unsuspend` | Réactiver utilisateur (Admin) | ✅ Admin |
| DELETE | `/api/admin/users/{id}` | Supprimer utilisateur (Admin) | ✅ Admin |

### 🏷️ Gestion des Annonces
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/ads/creation-data` | Données création annonce | ✅ JWT |
| POST | `/api/ads` | Créer annonce | ✅ JWT |
| GET | `/api/ads` | Lister annonces | ❌ |
| GET | `/api/ads/{id}` | Détails annonce | ❌ |
| PUT | `/api/ads/{id}` | Mise à jour annonce | ✅ JWT |
| POST | `/api/ads/{id}/photos` | Upload photos annonce | ✅ JWT |
| DELETE | `/api/ads/{id}` | Supprimer annonce | ✅ JWT |
| GET | `/api/admin/ads/pending` | Annonces en attente (Admin) | ✅ Admin |
| PUT | `/api/admin/ads/{id}/approve` | Approuver annonce (Admin) | ✅ Admin |
| PUT | `/api/admin/ads/{id}/reject` | Rejeter annonce (Admin) | ✅ Admin |

### 💬 Messages et Avis
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/messages` | Messages utilisateur | ✅ JWT |
| POST | `/api/messages` | Envoyer message/avis | ✅ JWT |
| PUT | `/api/messages/{id}/read` | Marquer comme lu | ✅ JWT |
| GET | `/api/messages/unread/count` | Nombre messages non lus | ✅ JWT |

### 🚨 Signalements
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/reports` | Créer signalement | ✅ JWT |
| GET | `/api/reports` | Signalements utilisateur | ✅ JWT |
| GET | `/api/admin/reports` | Signalements en attente (Admin) | ✅ Admin |
| PUT | `/api/admin/reports/{id}/resolve` | Résoudre signalement (Admin) | ✅ Admin |

### 📂 Catégories et Sous-catégories
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/categories` | Lister catégories | ❌ |
| GET | `/api/categories/{id}/subcategories` | Sous-catégories | ❌ |
| GET | `/api/categories/stats` | Catégories avec stats (Admin) | ✅ Admin |

### 🏢 Marques
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/brands` | Lister marques | ❌ |

### 🎁 Parrainage
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/referrals` | Codes parrainage | ✅ JWT |
| POST | `/api/referrals` | Créer code parrainage | ✅ JWT |
| POST | `/api/referrals/use` | Utiliser code parrainage | ✅ JWT |
| GET | `/api/referrals/stats` | Stats parrainage | ✅ JWT |

### 📊 Administration - Dashboard
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/admin/dashboard` | Dashboard admin complet | ✅ Admin |
| GET | `/api/admin/moderation-logs` | Logs de modération | ✅ Admin |

### 🗂️ Administration - Référentiels
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| **Catégories** | | | |
| GET | `/api/admin/referentials/categories` | Lister catégories | ✅ Admin |
| POST | `/api/admin/referentials/categories` | Créer catégorie | ✅ Admin |
| PUT | `/api/admin/referentials/categories/{id}` | Modifier catégorie | ✅ Admin |
| DELETE | `/api/admin/referentials/categories/{id}` | Supprimer catégorie | ✅ Admin |
| **Sous-catégories** | | | |
| GET | `/api/admin/referentials/subcategories` | Lister sous-catégories | ✅ Admin |
| POST | `/api/admin/referentials/subcategories` | Créer sous-catégorie | ✅ Admin |
| PUT | `/api/admin/referentials/subcategories/{id}` | Modifier sous-catégorie | ✅ Admin |
| DELETE | `/api/admin/referentials/subcategories/{id}` | Supprimer sous-catégorie | ✅ Admin |
| **Filtres** | | | |
| GET | `/api/admin/referentials/filters/{subcategoryId}` | Lister filtres | ✅ Admin |
| POST | `/api/admin/referentials/filters` | Créer filtre | ✅ Admin |
| PUT | `/api/admin/referentials/filters/{id}` | Modifier filtre | ✅ Admin |
| DELETE | `/api/admin/referentials/filters/{id}` | Supprimer filtre | ✅ Admin |
| **Marques** | | | |
| GET | `/api/admin/referentials/brands` | Lister marques | ✅ Admin |
| POST | `/api/admin/referentials/brands` | Créer marque | ✅ Admin |
| PUT | `/api/admin/referentials/brands/{id}` | Modifier marque | ✅ Admin |
| DELETE | `/api/admin/referentials/brands/{id}` | Supprimer marque | ✅ Admin |

### 💰 Administration - Promotions
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/admin/promotions/packs` | Lister packs promo | ✅ Admin |
| POST | `/api/admin/promotions/packs` | Créer pack promo | ✅ Admin |
| PUT | `/api/admin/promotions/packs/{id}` | Modifier pack promo | ✅ Admin |
| DELETE | `/api/admin/promotions/packs/{id}` | Supprimer pack promo | ✅ Admin |
| GET | `/api/admin/promotions/active` | Promotions actives | ✅ Admin |
| POST | `/api/admin/promotions/activate` | Activer promotion | ✅ Admin |
| PUT | `/api/admin/promotions/{id}/deactivate` | Désactiver promotion | ✅ Admin |
| GET | `/api/admin/promotions/stats` | Stats promotions | ✅ Admin |

### 📈 Administration - Reporting
| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/admin/reporting/global-stats` | Stats globales | ✅ Admin |
| GET | `/api/admin/reporting/detailed-stats` | Stats détaillées | ✅ Admin |
| GET | `/api/admin/reporting/export` | Export données | ✅ Admin |

## 🔧 Paramètres de Requête Courants

### Pagination
- `page` : numéro de page (défaut: 1)
- `per_page` ou `limit` : éléments par page (défaut: 20)

### Recherche et Filtres
- `q` : recherche textuelle
- `search` : terme de recherche
- `category_id` : ID catégorie
- `subcategory_id` : ID sous-catégorie
- `location_id` : ID localisation
- `min_price`, `max_price` : fourchette de prix
- `is_active` : statut actif (0/1)
- `is_suspended` : statut suspendu (0/1)

### Tri
- `sort_by` : champ de tri (created_at, price, etc.)
- `sort_order` : ordre (ASC, DESC)

### Périodes (pour reporting)
- `start_date` : date début (YYYY-MM-DD)
- `end_date` : date fin (YYYY-MM-DD)

## 📊 Codes de Réponse HTTP

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 400 | Requête invalide |
| 401 | Non autorisé (token invalide) |
| 403 | Interdit (permissions insuffisantes) |
| 404 | Ressource non trouvée |
| 422 | Erreur de validation |
| 500 | Erreur serveur |

## 🔑 Types d'Authentification

- **❌** : Aucune authentification requise
- **✅ JWT** : Token JWT requis (utilisateur connecté)
- **✅ Admin** : Token JWT admin requis

## 📝 Formats de Données

### Requête JSON Standard
```json
{
  "field1": "value1",
  "field2": 123,
  "field3": true
}
```

### Réponse de Succès
```json
{
  "status": "success",
  "message": "Opération réussie",
  "data": { ... }
}
```

### Réponse d'Erreur
```json
{
  "status": "error",
  "message": "Description de l'erreur",
  "errors": {
    "field": "Message d'erreur spécifique"
  }
}
```

## 🏃 Workflows Courants

### 1. Inscription → Connexion → Création d'Annonce
```
POST /auth/register → GET /auth/me → GET /ads/creation-data → POST /ads
```

### 2. Modération d'Annonce (Admin)
```
GET /admin/ads/pending → PUT /admin/ads/{id}/approve
```

### 3. Gestion Utilisateur (Admin)
```
GET /admin/users → PUT /admin/users/{id}/suspend
```

### 4. Reporting Complet
```
GET /admin/dashboard → GET /admin/reporting/global-stats → GET /admin/reporting/export
```

---

**Total d'endpoints** : 65+
**Version API** : v1
**Date** : Octobre 2025
