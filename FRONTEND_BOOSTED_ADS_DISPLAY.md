# 🚀 Affichage des Annonces Boostées - Guide Frontend

## 📌 Vue d'ensemble

Les annonces boostées sont maintenant **automatiquement mises en avant** dans tous les listings d'annonces (page d'accueil, catégorie, sous-catégorie). Le backend les priorise dans les résultats.

---

## 🔍 Comment identifier une annonce boostée ?

Chaque annonce retournée par l'API contient les champs suivants :

```json
{
  "id": 42,
  "slug": "iphone-13-pro-douala",
  "title": "iPhone 13 Pro - Douala",
  "price": 450000,
  "is_boosted": 1,           // ✅ 1 = boostée, 0 = normale
  "boost_start": "2025-10-30 11:00:00",  // Date de début du boost
  "boost_end": "2025-11-06 11:00:00",    // Date de fin du boost
  "created_at": "2025-10-25 14:30:00",
  "photos": [...],
  "filters": [...]
}
```

### Critères pour qu'une annonce soit considérée comme boostée :

1. **`is_boosted` = 1**
2. **`boost_end` >= Date actuelle** (le boost n'est pas expiré)

---

## 📊 Ordre de tri automatique

Le backend applique automatiquement ce tri sur **tous les endpoints** :

### Endpoints concernés :
- ✅ `GET /api/ads` (page d'accueil, toutes les annonces)
- ✅ `GET /api/ads/category/{id}` (annonces par catégorie)
- ✅ `GET /api/ads/subcategory/{id}` (annonces par sous-catégorie)

### Logique de tri :
```
1. Annonces boostées actives (is_boosted = 1 ET boost_end >= NOW())
2. Ensuite tri demandé par l'utilisateur (ex: created_at DESC)
```

### Exemple de requête :
```bash
GET /api/ads?sort_by=created_at&sort_order=DESC
```

**Résultat :**
1. Annonces boostées (triées par created_at DESC)
2. Annonces normales (triées par created_at DESC)

---

## 🎨 Affichage côté Frontend

### 1. Badge "Boosté" / "Sponsorisé"

Afficher un badge distinctif sur les annonces boostées :

```jsx
function AdCard({ ad }) {
  // Vérifier si l'annonce est boostée et non expirée
  const isBoosted = ad.is_boosted === 1 && new Date(ad.boost_end) >= new Date();

  return (
    <div className={`ad-card ${isBoosted ? 'boosted' : ''}`}>
      {isBoosted && (
        <div className="boost-badge">
          🚀 Sponsorisé
        </div>
      )}
      <img src={ad.photos[0]?.url} alt={ad.title} />
      <h3>{ad.title}</h3>
      <p>{ad.price} FCFA</p>
      {isBoosted && (
        <small className="boost-info">
          Jusqu'au {new Date(ad.boost_end).toLocaleDateString()}
        </small>
      )}
    </div>
  );
}
```

### 2. Style CSS pour les annonces boostées

```css
.ad-card.boosted {
  border: 2px solid #ffc107;
  box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
  position: relative;
}

.boost-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: linear-gradient(135deg, #ffc107, #ff9800);
  color: white;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(255, 193, 7, 0.5);
  z-index: 10;
}

.boost-info {
  color: #ff9800;
  font-size: 11px;
  display: block;
  margin-top: 5px;
}
```

### 3. Icône/Badge alternatif

```jsx
function BoostBadge({ boostEnd }) {
  const daysRemaining = Math.ceil((new Date(boostEnd) - new Date()) / (1000 * 60 * 60 * 24));
  
  return (
    <div className="boost-badge-alt">
      <span className="boost-icon">⭐</span>
      <span className="boost-text">
        Mise en avant {daysRemaining > 1 ? `(${daysRemaining} jours)` : '(expire bientôt)'}
      </span>
    </div>
  );
}
```

---

## 📱 Exemples de code frontend

### React / Next.js

```jsx
import { useState, useEffect } from 'react';

function AdsList() {
  const [ads, setAds] = useState([]);

  useEffect(() => {
    fetch('http://localhost:8080/api/ads?per_page=20')
      .then(res => res.json())
      .then(data => setAds(data.ads));
  }, []);

  return (
    <div className="ads-grid">
      {ads.map(ad => {
        const isBoosted = ad.is_boosted === 1 && new Date(ad.boost_end) >= new Date();
        
        return (
          <div key={ad.id} className={`ad-card ${isBoosted ? 'boosted' : ''}`}>
            {isBoosted && <span className="badge badge-boost">🚀 Sponsorisé</span>}
            <img src={ad.photos[0]?.url || '/placeholder.jpg'} alt={ad.title} />
            <h3>{ad.title}</h3>
            <p className="price">{ad.price.toLocaleString()} FCFA</p>
            {isBoosted && (
              <p className="boost-expiry">
                Jusqu'au {new Date(ad.boost_end).toLocaleDateString('fr-FR')}
              </p>
            )}
          </div>
        );
      })}
    </div>
  );
}
```

### Vue.js

```vue
<template>
  <div class="ads-grid">
    <div 
      v-for="ad in ads" 
      :key="ad.id" 
      :class="['ad-card', { boosted: isBoosted(ad) }]"
    >
      <span v-if="isBoosted(ad)" class="badge badge-boost">🚀 Sponsorisé</span>
      <img :src="ad.photos[0]?.url || '/placeholder.jpg'" :alt="ad.title">
      <h3>{{ ad.title }}</h3>
      <p class="price">{{ formatPrice(ad.price) }} FCFA</p>
      <p v-if="isBoosted(ad)" class="boost-expiry">
        Jusqu'au {{ formatDate(ad.boost_end) }}
      </p>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      ads: []
    };
  },
  mounted() {
    fetch('http://localhost:8080/api/ads?per_page=20')
      .then(res => res.json())
      .then(data => this.ads = data.ads);
  },
  methods: {
    isBoosted(ad) {
      return ad.is_boosted === 1 && new Date(ad.boost_end) >= new Date();
    },
    formatPrice(price) {
      return price.toLocaleString('fr-FR');
    },
    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString('fr-FR');
    }
  }
};
</script>
```

### Vanilla JavaScript

```javascript
async function loadAds() {
  const response = await fetch('http://localhost:8080/api/ads?per_page=20');
  const data = await response.json();
  const container = document.getElementById('ads-container');
  
  data.ads.forEach(ad => {
    const isBoosted = ad.is_boosted === 1 && new Date(ad.boost_end) >= new Date();
    
    const adCard = document.createElement('div');
    adCard.className = `ad-card ${isBoosted ? 'boosted' : ''}`;
    
    adCard.innerHTML = `
      ${isBoosted ? '<span class="badge badge-boost">🚀 Sponsorisé</span>' : ''}
      <img src="${ad.photos[0]?.url || '/placeholder.jpg'}" alt="${ad.title}">
      <h3>${ad.title}</h3>
      <p class="price">${ad.price.toLocaleString()} FCFA</p>
      ${isBoosted ? `<p class="boost-expiry">Jusqu'au ${new Date(ad.boost_end).toLocaleDateString('fr-FR')}</p>` : ''}
    `;
    
    container.appendChild(adCard);
  });
}

loadAds();
```

---

## 🎨 Design recommandé

### Option 1 : Badge en coin supérieur droit
```
┌─────────────────────────┐
│            [🚀 Sponsorisé]│
│   ┌───────────────┐     │
│   │               │     │
│   │    IMAGE      │     │
│   │               │     │
│   └───────────────┘     │
│   Titre de l'annonce    │
│   450 000 FCFA          │
│   Jusqu'au 06/11/2025   │
└─────────────────────────┘
```

### Option 2 : Bordure dorée + badge
```
╔═════════════════════════╗ ← Bordure dorée
║   [⭐ Mise en avant]    ║
║   ┌───────────────┐     ║
║   │    IMAGE      │     ║
║   └───────────────┘     ║
║   iPhone 13 Pro         ║
║   450 000 FCFA          ║
╚═════════════════════════╝
```

### Option 3 : Fond coloré
```
┌─────────────────────────┐
│ 🌟 ANNONCE SPONSORISÉE  │ ← Fond jaune/or
├─────────────────────────┤
│   ┌───────────────┐     │
│   │    IMAGE      │     │
│   └───────────────┘     │
│   Titre                 │
│   Prix                  │
└─────────────────────────┘
```

---

## 📝 Variantes de badges

### Français
- 🚀 Sponsorisé
- ⭐ Mise en avant
- 💎 Annonce Premium
- 🔥 À la une
- ⚡ Boost actif

### Anglais
- 🚀 Sponsored
- ⭐ Featured
- 💎 Premium
- 🔥 Hot Deal
- ⚡ Boosted

---

## 🔔 Notifications de fin de boost (optionnel)

Si l'utilisateur est sur sa propre annonce boostée :

```jsx
function MyAdCard({ ad, isOwner }) {
  const isBoosted = ad.is_boosted === 1 && new Date(ad.boost_end) >= new Date();
  const daysRemaining = Math.ceil((new Date(ad.boost_end) - new Date()) / (1000 * 60 * 60 * 24));
  
  return (
    <div className="ad-card boosted">
      {isBoosted && isOwner && (
        <div className={`boost-status ${daysRemaining <= 2 ? 'expiring-soon' : ''}`}>
          {daysRemaining > 2 ? (
            <span>✅ Boost actif ({daysRemaining} jours restants)</span>
          ) : (
            <span>⚠️ Boost expire dans {daysRemaining} jour(s) - Renouvelez maintenant!</span>
          )}
        </div>
      )}
      {/* ...reste du card */}
    </div>
  );
}
```

---

## ✅ Checklist Frontend

- [ ] Vérifier `is_boosted === 1` ET `boost_end >= Date actuelle`
- [ ] Ajouter un badge/icône distinctif sur les annonces boostées
- [ ] Styler les annonces boostées (bordure, ombre, couleur)
- [ ] Afficher la date d'expiration du boost
- [ ] Tester sur mobile et desktop
- [ ] Ajouter une animation subtile (optionnel)
- [ ] Afficher un compteur de jours restants pour le propriétaire
- [ ] Proposer un bouton "Booster" sur les annonces normales

---

## 🚀 Résultat final

Les annonces boostées apparaissent maintenant **en premier** dans tous les listings, avec un visuel distinctif qui attire l'œil. Le système fonctionne automatiquement côté backend, le frontend n'a qu'à afficher le badge approprié.

**Exemple de flux complet :**

1. User boost son annonce → Statut "pending"
2. User valide paiement mobile money
3. Backend détecte le paiement (polling) → Active le boost
4. Annonce passe `is_boosted = 1`
5. Frontend affiche badge "🚀 Sponsorisé"
6. Annonce apparaît en premier dans les listes
7. Après X jours, boost expire automatiquement
8. Annonce redevient normale

---

## 📚 Documentation liée

- `BOOST_PAYMENT_POLLING.md` - Workflow de paiement
- `TEST_BOOST_POLLING_GUIDE.md` - Tests backend
- `FIX_CAMPAY_REFERENCE.md` - Intégration Campay

---

**Tout est prêt côté backend ! Il ne reste qu'à adapter le frontend pour afficher le badge sur les annonces boostées.** 🎉
