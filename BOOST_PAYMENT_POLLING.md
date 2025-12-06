# Système de Vérification Automatique des Paiements Boost

## Vue d'ensemble

Le système vérifie automatiquement le statut des paiements auprès de Campay et met à jour la base de données dès que le statut change. Le frontend doit appeler l'endpoint de vérification toutes les 5 secondes après l'initiation du paiement.

## Workflow

```
1. User clique "Booster" → POST /api/boost/boost-existing-ad/{slug}
2. Backend crée payment (status: pending) et appelle Campay
3. Backend retourne payment_id et reference
4. Frontend démarre polling GET /api/boost/check-payment/{payment_id} toutes les 5s
5. Backend appelle Campay à chaque requête et met à jour la BD
6. Quand status = SUCCESSFUL → Backend active le boost automatiquement
7. Frontend arrête le polling et affiche succès
```

## API Endpoint

### GET /api/boost/check-payment/{payment_id}

Vérifie le statut du paiement auprès de Campay et met à jour automatiquement la base de données.

**Headers:**
```
Authorization: Bearer {user_token}
Content-Type: application/json
```

**Réponse Exemple - Paiement en attente:**
```json
{
  "payment_id": 1,
  "status": "pending",
  "updated": false,
  "message": "Aucun changement",
  "campay": {
    "reference": "bcedde9b-62a7-4421-96ac-2e6179552a1a",
    "status": "PENDING",
    "amount": 1000,
    "currency": "XAF",
    "operator": "MTN",
    "operator_reference": "1880106956"
  }
}
```

**Réponse Exemple - Paiement réussi:**
```json
{
  "payment_id": 1,
  "status": "paid",
  "updated": true,
  "message": "Statut mis à jour",
  "campay": {
    "reference": "bcedde9b-62a7-4421-96ac-2e6179552a1a",
    "status": "SUCCESSFUL",
    "amount": 1000,
    "currency": "XAF",
    "operator": "MTN",
    "operator_reference": "1880106956"
  },
  "ad": {
    "id": 42,
    "slug": "iphone-13-pro-douala",
    "title": "iPhone 13 Pro - Douala",
    "is_boosted": 1,
    "boost_start": "2025-10-30 11:00:00",
    "boost_end": "2025-11-06 11:00:00"
  }
}
```

**Réponse Exemple - Paiement échoué:**
```json
{
  "payment_id": 1,
  "status": "failed",
  "updated": true,
  "message": "Statut mis à jour",
  "campay": {
    "reference": "bcedde9b-62a7-4421-96ac-2e6179552a1a",
    "status": "FAILED",
    "amount": 1000,
    "currency": "XAF",
    "operator": "MTN",
    "operator_reference": null
  }
}
```

## Implémentation Frontend (JavaScript/React)

### Exemple avec fetch() (Vanilla JS)

```javascript
async function boostAd(adSlug, packId, phone) {
  try {
    // 1. Initier le boost
    const boostResponse = await fetch(`${API_URL}/api/boost/boost-existing-ad/${adSlug}`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        pack_id: packId,
        phone: phone,
        payment_method: 'mobile_money'
      })
    });

    const boostData = await boostResponse.json();
    
    if (!boostResponse.ok) {
      throw new Error(boostData.message || 'Erreur initiation boost');
    }

    const paymentId = boostData.payment.id;
    console.log('Paiement initié:', paymentId);

    // 2. Démarrer le polling toutes les 5 secondes
    const pollInterval = setInterval(async () => {
      try {
        const statusResponse = await fetch(`${API_URL}/api/boost/check-payment/${paymentId}`, {
          headers: {
            'Authorization': `Bearer ${userToken}`,
            'Content-Type': 'application/json'
          }
        });

        const statusData = await statusResponse.json();
        console.log('Statut paiement:', statusData);

        // 3. Gérer les différents statuts
        if (statusData.status === 'paid') {
          clearInterval(pollInterval);
          alert('✅ Boost activé avec succès!');
          // Rediriger ou rafraîchir l'annonce
          window.location.href = `/ads/${statusData.ad.slug}`;
        } else if (statusData.status === 'failed') {
          clearInterval(pollInterval);
          alert('❌ Paiement échoué. Veuillez réessayer.');
        } else {
          // Toujours en attente, continuer le polling
          console.log('En attente de validation...');
        }
      } catch (error) {
        console.error('Erreur vérification statut:', error);
        // Ne pas arrêter le polling en cas d'erreur réseau
      }
    }, 5000); // 5 secondes

    // 4. Timeout après 5 minutes (60 checks)
    setTimeout(() => {
      clearInterval(pollInterval);
      alert('⏱️ Timeout: Le paiement prend trop de temps. Vérifiez manuellement.');
    }, 300000); // 5 minutes

  } catch (error) {
    console.error('Erreur boost:', error);
    alert('Erreur: ' + error.message);
  }
}

// Utilisation
boostAd('iphone-13-pro-douala', 1, '237690000000');
```

### Exemple avec React + useEffect

```jsx
import React, { useState, useEffect } from 'react';
import axios from 'axios';

function BoostAdButton({ adSlug, packId }) {
  const [paymentId, setPaymentId] = useState(null);
  const [status, setStatus] = useState('idle'); // idle, pending, success, failed
  const [message, setMessage] = useState('');

  // Initier le boost
  const initiateBoost = async (phone) => {
    try {
      setStatus('pending');
      setMessage('Initiation du paiement...');

      const response = await axios.post(
        `${process.env.REACT_APP_API_URL}/api/boost/boost-existing-ad/${adSlug}`,
        {
          pack_id: packId,
          phone: phone,
          payment_method: 'mobile_money'
        },
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      setPaymentId(response.data.payment.id);
      setMessage('En attente de validation du paiement...');
    } catch (error) {
      setStatus('failed');
      setMessage(error.response?.data?.message || 'Erreur initiation boost');
    }
  };

  // Polling automatique quand paymentId est défini
  useEffect(() => {
    if (!paymentId) return;

    let pollCount = 0;
    const maxPolls = 60; // 5 minutes max

    const pollInterval = setInterval(async () => {
      pollCount++;

      try {
        const response = await axios.get(
          `${process.env.REACT_APP_API_URL}/api/boost/check-payment/${paymentId}`,
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem('token')}`
            }
          }
        );

        const data = response.data;

        if (data.status === 'paid') {
          clearInterval(pollInterval);
          setStatus('success');
          setMessage('Boost activé avec succès!');
          // Optionnel: rafraîchir l'annonce
          setTimeout(() => window.location.reload(), 2000);
        } else if (data.status === 'failed') {
          clearInterval(pollInterval);
          setStatus('failed');
          setMessage('Paiement échoué');
        } else {
          setMessage(`En attente (${data.campay?.status})...`);
        }

        // Timeout après 5 minutes
        if (pollCount >= maxPolls) {
          clearInterval(pollInterval);
          setStatus('failed');
          setMessage('Timeout: vérifiez manuellement le statut');
        }
      } catch (error) {
        console.error('Erreur polling:', error);
        // Ne pas arrêter le polling pour erreur réseau
      }
    }, 5000);

    return () => clearInterval(pollInterval);
  }, [paymentId]);

  return (
    <div>
      {status === 'idle' && (
        <button onClick={() => {
          const phone = prompt('Entrez votre numéro de téléphone:');
          if (phone) initiateBoost(phone);
        }}>
          Booster cette annonce
        </button>
      )}
      
      {status === 'pending' && (
        <div className="loading">
          <div className="spinner"></div>
          <p>{message}</p>
        </div>
      )}
      
      {status === 'success' && (
        <div className="success">✅ {message}</div>
      )}
      
      {status === 'failed' && (
        <div className="error">❌ {message}</div>
      )}
    </div>
  );
}

export default BoostAdButton;
```

## Backend - Traitement Automatique

### BoostService::verifyAndUpdatePaymentStatus()

Cette méthode fait tout automatiquement :

1. ✅ Vérifie le paiement existe
2. ✅ Ignore les paiements déjà finalisés (paid/cancelled)
3. ✅ Appelle l'API Campay pour le statut réel
4. ✅ Mappe les statuts Campay → statuts internes
   - `SUCCESSFUL` → `paid`
   - `FAILED` → `failed`
   - `PENDING` → `pending`
5. ✅ Met à jour la table `payments` si changement
6. ✅ Active automatiquement le boost si statut = paid
7. ✅ Log tous les changements

### Statuts Campay

| Statut Campay | Statut DB | Action Backend |
|---------------|-----------|----------------|
| PENDING       | pending   | Aucune         |
| SUCCESSFUL    | paid      | Active le boost automatiquement |
| FAILED        | failed    | Aucune         |

## Tables Affectées

### payments
```sql
UPDATE payments 
SET status = 'paid', processed_at = NOW()
WHERE id = ? AND status != 'paid';
```

### ads
```sql
UPDATE ads 
SET is_boosted = 1, boost_start = NOW(), boost_end = DATE_ADD(NOW(), INTERVAL ? DAY)
WHERE id = ?;
```

### ad_promotions
```sql
INSERT INTO ad_promotions (ad_id, promotion_type, starts_at, expires_at, price_paid, payment_reference, is_active)
VALUES (?, 'boost', NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), ?, ?, 1);
```

## Tests avec Postman

### 1. Initier un boost
```
POST {{base_url}}/api/boost/boost-existing-ad/{{ad_slug}}
Authorization: Bearer {{user_token}}

Body:
{
  "pack_id": 1,
  "phone": "237690000000",
  "payment_method": "mobile_money"
}

→ Récupérer payment_id dans la réponse
```

### 2. Vérifier le statut (à répéter toutes les 5s)
```
GET {{base_url}}/api/boost/check-payment/{{payment_id}}
Authorization: Bearer {{user_token}}

→ Observer le changement de status: pending → paid
```

## Logs Backend

Le backend log toutes les étapes :

```
[INFO] Paiement #1 mis à jour: pending → paid
[INFO] Boost activé pour le paiement #1
[ERROR] Erreur cURL check status: Connection timeout
[ERROR] Réponse invalide check status: {"error": "Invalid reference"}
```

Consultez `writable/logs/log-2025-10-30.log` pour debug.

## Sécurité

- ✅ Endpoint protégé par authentification JWT
- ✅ Vérification que l'utilisateur est propriétaire de l'annonce
- ✅ Validation du pack_id existe
- ✅ Normalisation du numéro de téléphone
- ✅ Logs complets pour audit
- ✅ Idempotent: plusieurs appels ne changent rien si déjà finalisé

## Performance

- Chaque appel GET /check-payment fait 1 requête curl vers Campay (~500ms)
- Limite: pas de rate limiting côté backend (à ajouter si nécessaire)
- Recommandation: polling toutes les 5s max 5 minutes (60 checks)

## Erreurs Courantes

### "Paiement introuvable"
→ payment_id invalide ou supprimé

### "Impossible de contacter Campay"
→ Problème réseau ou API Campay down

### "Paiement déjà finalisé"
→ Statut = paid ou cancelled, arrêter le polling

## Evolution Possible

1. **WebSockets**: Remplacer le polling par des notifications push en temps réel
2. **Webhook Campay**: Recevoir les notifications de changement de statut directement
3. **Queue System**: Mettre les vérifications dans une queue Redis/RabbitMQ
4. **Rate Limiting**: Limiter les appels par user (ex: max 1 check/2s)
5. **Cache**: Cache Redis du statut pendant 3-5 secondes

## Support

Pour toute question: voir `API_DOCUMENTATION.md` ou `BOOST_SYSTEM_USERSTORIES_PROMPTS.md`
