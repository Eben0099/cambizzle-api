# 📊 Documentation: Système de Suivi des Vues d'Annonces

## 🎯 Fonctionnalité

Ce système permet de tracker le nombre de vues de chaque annonce et fournir des statistiques aux vendeurs.

---

## ✨ Fonctionnalités Implémentées

### 1. **Incrémentation automatique des vues**
- À chaque consultation des détails d'une annonce, le compteur `view_count` est automatiquement incrémenté
- Cela se produit lors de l'appel à `GET /api/ads/{id|slug}`

### 2. **Inclusion du nombre de vues dans les réponses GET**
- Le champ `view_count` est inclus dans toutes les réponses GET des annonces :
  - Réponse `GET /api/ads/{id|slug}` (détails d'une annonce)
  - Réponse `GET /api/ads/` (liste des annonces)
  - Réponse `GET /api/ads/user/{userId}` (annonces par utilisateur)
  - Réponse `GET /api/ads/category/{categoryId}` (annonces par catégorie)
  - Réponse `GET /api/ads/subcategory/{subcategoryId}` (annonces par sous-catégorie)
  - Réponse `GET /api/ads/id/{adId}` (annonce par ID)

### 3. **Endpoint de statistiques de vues**
- Nouveau endpoint pour obtenir les statistiques complètes de vues d'un utilisateur

---

## 🔌 Endpoints API

### **1. Obtenir les détails d'une annonce (incrémente les vues)**
```http
GET /api/ads/{id|slug}
```

**Paramètres:**
- `id|slug` : ID numérique ou slug de l'annonce

**Exemple:**
```http
GET /api/ads/123
GET /api/ads/mon-produit-phenix
```

**Réponse (200 OK):**
```json
{
  "id": 123,
  "title": "iPhone 13 Pro",
  "description": "Excellent état",
  "price": 45000,
  "view_count": 15,
  "photos": [...],
  "filters": [...],
  "user_details": {...},
  "seller_profile": {...}
  // ... autres champs
}
```

**Comportement:** À chaque appel, `view_count` est incrémenté de 1 dans la base de données.

---

### **2. Lister les annonces (avec view_count)**
```http
GET /api/ads/
```

**Paramètres (optionnels):**
- `page` : Numéro de la page (défaut: 1)
- `per_page` : Résultats par page (défaut: 1000)
- `sort_by` : Champ de tri (défaut: `created_at`) 
  - Valeurs possibles: `created_at`, `updated_at`, `price`, `title`, `view_count`
- `sort_order` : `ASC` ou `DESC` (défaut: `DESC`)
- `category_id`, `subcategory_id`, `location_id`, `brand_id`, `status`, etc.

**Exemple:**
```http
GET /api/ads/?page=1&per_page=20&sort_by=view_count&sort_order=DESC
```

**Réponse (200 OK):**
```json
{
  "ads": [
    {
      "id": 123,
      "title": "iPhone 13 Pro",
      "price": 45000,
      "view_count": 150,
      ...
    },
    {
      "id": 124,
      "title": "Samsung Galaxy S21",
      "price": 35000,
      "view_count": 89,
      ...
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 500,
    "total_pages": 25,
    ...
  }
}
```

---

### **3. Annonces par utilisateur (avec view_count)**
```http
GET /api/ads/user/{userId}
```

**Paramètres:**
- `userId` : ID de l'utilisateur (numérique)
- `page`, `per_page`, `sort_by`, `sort_order` : Même que le listing général

**Exemple:**
```http
GET /api/ads/user/42?sort_by=view_count&sort_order=DESC
```

**Réponse (200 OK):**
```json
{
  "ads": [
    {
      "id": 123,
      "title": "iPhone 13 Pro",
      "view_count": 150,
      ...
    }
  ],
  "pagination": {...},
  "user_id": 42
}
```

---

### **4. 📊 Statistiques de vues de l'utilisateur** ⭐ NOUVEAU
```http
GET /api/ads/user/{userId}/views-stats
```

**Paramètres:**
- `userId` : ID de l'utilisateur (numérique)

**Exemple:**
```http
GET /api/ads/user/42/views-stats
```

**Réponse (200 OK):**
```json
{
  "user_id": 42,
  "stats": {
    "total_views": 1250,
    "total_ads": 35,
    "ads_with_views": 28,
    "ads_without_views": 7,
    "average_views_per_ad": 35.71,
    "max_views": 156,
    "min_views": 0
  },
  "top_ads": [
    {
      "id": 123,
      "title": "iPhone 13 Pro",
      "slug": "iphone-13-pro",
      "price": 45000,
      "view_count": 156,
      "created_at": "2025-11-15 10:30:00"
    },
    {
      "id": 124,
      "title": "Samsung Galaxy S21",
      "slug": "samsung-galaxy-s21",
      "price": 35000,
      "view_count": 143,
      "created_at": "2025-11-10 14:20:00"
    },
    ...
  ]
}
```

**Champs expliqués:**
- **total_views** : Somme totale de toutes les vues de toutes les annonces
- **total_ads** : Nombre total d'annonces de l'utilisateur
- **ads_with_views** : Nombre d'annonces qui ont au moins 1 vue
- **ads_without_views** : Nombre d'annonces sans vues
- **average_views_per_ad** : Moyenne de vues par annonce
- **max_views** : Nombre maximum de vues sur une annonce
- **min_views** : Nombre minimum de vues (toujours 0)
- **top_ads** : Les 10 annonces les plus consultées

---

### **5. Annonces par catégorie (avec view_count)**
```http
GET /api/ads/category/{categoryId}
```

**Réponse:** Inclut `view_count` pour chaque annonce

---

### **6. Annonces par sous-catégorie (avec view_count)**
```http
GET /api/ads/subcategory/{subcategoryId|slug}
```

**Réponse:** Inclut `view_count` pour chaque annonce

---

### **7. Annonce par ID (avec view_count)**
```http
GET /api/ads/id/{adId}
```

**Réponse:** Inclut `view_count` pour l'annonce

---

## 📈 Cas d'Usage

### Scénario 1: Afficher les annonces populaires
```http
GET /api/ads/?sort_by=view_count&sort_order=DESC&per_page=10
```

### Scénario 2: Voir les stats de mon profil vendeur
```http
GET /api/ads/user/42/views-stats
```

### Scénario 3: Obtenir les 5 annonces les plus vues d'une catégorie
```http
GET /api/ads/category/5?sort_by=view_count&sort_order=DESC&per_page=5
```

### Scénario 4: Consulter une annonce (incrémente le compteur)
```http
GET /api/ads/mon-produit-phenix
```

---

## 🔧 Détails Techniques

### Base de Données
- **Colonne:** `view_count` dans la table `ads`
- **Type:** `INT` avec défaut `0`
- **Mise à jour:** Lors de chaque `GET` sur les détails d'une annonce

### Code Modifié
- **Fichier:** `app/Controllers/Api/AdsController.php`
  - Méthode `show()` : Incrémente `view_count` (ligne ~916)
  - Nouvelle méthode `getUserViewsStats()` : Retourne les statistiques
  
- **Fichier:** `app/Config/Routes.php`
  - Nouvelle route: `GET /api/ads/user/(:num)/views-stats`

---

## ⚠️ Notes Importantes

1. **Chaque vue compte:** Le compteur s'incrémente même si le même utilisateur visite plusieurs fois
2. **Vues côté serveur:** Pas de système de deduplication (ce qui est une bonne pratique pour les vrais analytics)
3. **Tri par vues:** Vous pouvez trier toutes les listes par `view_count` (croissant ou décroissant)
4. **Performance:** Le compteur utilisé `view_count + 1` en UPDATE SQL pour éviter les race conditions

---

## 🚀 Exemples cURL

### Obtenir les stats de vues d'un utilisateur
```bash
curl -X GET "http://localhost:8080/api/ads/user/42/views-stats"
```

### Afficher une annonce (incrémente les vues)
```bash
curl -X GET "http://localhost:8080/api/ads/123"
```

### Lister par vues décroissantes
```bash
curl -X GET "http://localhost:8080/api/ads/?sort_by=view_count&sort_order=DESC&per_page=20"
```

### Statistiques + filtrage avancé
```bash
curl -X GET "http://localhost:8080/api/ads/user/42/views-stats"
```

---

## 📝 Changelog

### Version 1.0 (2025-11-22)
- ✅ Implémentation de l'incrémentation automatique des vues
- ✅ Inclusion de `view_count` dans toutes les réponses GET
- ✅ Création de l'endpoint `/api/ads/user/{userId}/views-stats`
- ✅ Support du tri par `view_count` dans tous les listings
