# Guide de Test - Système de Polling Automatique des Paiements Boost

## Problème Résolu

Avant, le statut du paiement restait bloqué sur "pending" même après validation dans Campay. Maintenant, le backend vérifie automatiquement le statut auprès de Campay à chaque appel et met à jour la base de données.

## Ce qui a été implémenté

### Backend

1. **BoostService::verifyAndUpdatePaymentStatus()** - Nouvelle méthode qui:
   - Appelle l'API Campay pour récupérer le statut réel
   - Met à jour la table `payments` automatiquement
   - Active le boost si statut = SUCCESSFUL
   - Log tous les changements

2. **BoostController::checkBoostPayment()** - Endpoint amélioré qui:
   - Appelle automatiquement `verifyAndUpdatePaymentStatus()`
   - Retourne les infos Campay complètes
   - Retourne les infos de l'annonce si boost activé

### Frontend (à implémenter)

Le frontend doit maintenant faire du **polling** (vérification répétée) toutes les 5 secondes :

```javascript
// Pseudo-code
1. POST /api/boost/boost-existing-ad/{slug} → Récupère payment_id
2. Toutes les 5s: GET /api/boost/check-payment/{payment_id}
3. Si status = 'paid' → Arrêter polling, afficher succès
4. Si status = 'failed' → Arrêter polling, afficher erreur
5. Si status = 'pending' → Continuer polling
6. Après 5 minutes → Timeout
```

## Tests Disponibles

### 1. Test avec Postman

**Collection:** `postman/Cambizzle_Boost_System.postman_collection.json`

**Étapes:**
```
1. Importer la collection dans Postman
2. Configurer les variables:
   - base_url: http://localhost:8080
   - user_token: <votre token JWT>
   - ad_slug: <slug d'une annonce>
   
3. Exécuter "Booster une annonce existante"
   → Noter le payment_id dans la réponse
   
4. Mettre à jour la variable payment_id

5. Exécuter "Vérifier statut paiement (Check Payment)" 
   → Répéter toutes les 5 secondes manuellement
   → Observer le changement de statut
```

**Requête Check Payment:**
```
GET http://localhost:8080/api/boost/check-payment/1
Authorization: Bearer <token>
```

**Réponse attendue (pending):**
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
    "operator": "MTN"
  }
}
```

**Réponse attendue (paid):**
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

### 2. Test avec Script PHP Automatique

**Fichier:** `test_boost_payment_polling.php`

**Utilisation:**
```bash
# Windows (PowerShell)
php test_boost_payment_polling.php 1 "eyJ0eXAiOiJKV1QiLCJhbGc..."

# Ou avec le batch
test_boost_polling.bat 1 "eyJ0eXAiOiJKV1QiLCJhbGc..."
```

**Arguments:**
- `1` → payment_id
- `"eyJ0eXAi..."` → user_token (JWT)

**Ce que fait le script:**
- ✅ Appelle GET /api/boost/check-payment/{id} toutes les 5 secondes
- ✅ Affiche le statut Campay en temps réel
- ✅ S'arrête automatiquement si statut = paid ou failed
- ✅ Timeout après 60 tentatives (5 minutes)
- ✅ Affiche les détails de l'annonce boostée si succès

**Exemple de sortie:**
```
🚀 Démarrage du polling pour le paiement #1
⏱️  Intervalle: 5s | Max tentatives: 60
------------------------------------------------------------

[Tentative 1/60] 11:00:05
📊 Statut: pending
💬 Message: Aucun changement
📱 Campay:
   - Référence: bcedde9b-62a7-4421-96ac-2e6179552a1a
   - Statut: PENDING
   - Montant: 1000 XAF
   - Opérateur: MTN
⏳ En attente... prochaine vérification dans 5s

[Tentative 2/60] 11:00:10
📊 Statut: pending
💬 Message: Aucun changement
⏳ En attente... prochaine vérification dans 5s

[Tentative 3/60] 11:00:15
📊 Statut: paid (MIS À JOUR)
💬 Message: Statut mis à jour

✅ SUCCÈS: Paiement confirmé et boost activé!

📢 Annonce boostée:
   - ID: 42
   - Slug: iphone-13-pro-douala
   - Titre: iPhone 13 Pro - Douala
   - Boost actif: Oui
   - Début: 2025-10-30 11:00:00
   - Fin: 2025-11-06 11:00:00

✨ Polling terminé avec succès!
```

### 3. Test avec cURL Manuel

```bash
# 1. Booster une annonce
curl -X POST http://localhost:8080/api/boost/boost-existing-ad/mon-annonce-slug \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "pack_id": 1,
    "phone": "237690000000",
    "payment_method": "mobile_money"
  }'

# → Noter le payment_id

# 2. Vérifier le statut (répéter toutes les 5s)
curl http://localhost:8080/api/boost/check-payment/1 \
  -H "Authorization: Bearer <token>"

# 3. Valider le paiement sur votre téléphone

# 4. Continuer à vérifier le statut
# → Le backend mettra à jour automatiquement dès que Campay retourne SUCCESSFUL
```

## Workflow Complet

```
┌─────────────┐
│   USER      │
│  (Frontend) │
└──────┬──────┘
       │
       │ 1. POST /boost-existing-ad
       ▼
┌─────────────┐     2. collect_paiement()      ┌──────────┐
│  Backend    ├─────────────────────────────►  │  Campay  │
│             │◄─────────────────────────────  │   API    │
└──────┬──────┘     reference + status         └──────────┘
       │
       │ 3. Return payment_id
       ▼
┌─────────────┐
│  Frontend   │───┐
│             │   │ 4. Polling loop (every 5s)
│             │◄──┘    GET /check-payment/{id}
└──────┬──────┘
       │
       │ 5. Backend calls:
       │    - checkStatus(reference) → Campay API
       │    - Update payments table
       │    - confirmBoostPayment() if SUCCESSFUL
       │
       ▼
┌─────────────┐
│  Database   │
│  payments   │ status: pending → paid
│  ads        │ is_boosted: 0 → 1
└─────────────┘
```

## Vérification dans la Base de Données

```sql
-- Voir le statut du paiement
SELECT id, reference, status, amount, phone, created_at, processed_at 
FROM payments 
WHERE id = 1;

-- Voir si l'annonce est boostée
SELECT id, slug, title, is_boosted, boost_start, boost_end 
FROM ads 
WHERE id = 42;

-- Voir les promotions actives
SELECT * FROM ad_promotions 
WHERE payment_reference = 'bcedde9b-62a7-4421-96ac-2e6179552a1a';
```

## Cas d'Usage

### Cas 1: Paiement réussi
```
1. User boost l'annonce → payment_id = 1, status = pending
2. User valide sur son téléphone
3. Frontend poll toutes les 5s
4. Backend appelle Campay → status = SUCCESSFUL
5. Backend met à jour payments.status = paid
6. Backend active le boost (is_boosted = 1)
7. Frontend reçoit status = paid et affiche succès
```

### Cas 2: Paiement échoué
```
1. User boost l'annonce → payment_id = 1, status = pending
2. User annule ou solde insuffisant
3. Frontend poll toutes les 5s
4. Backend appelle Campay → status = FAILED
5. Backend met à jour payments.status = failed
6. Frontend reçoit status = failed et propose de réessayer
```

### Cas 3: Timeout
```
1. User boost l'annonce → payment_id = 1, status = pending
2. User ne fait rien pendant 5 minutes
3. Frontend arrête le polling après 60 tentatives
4. Message: "Vérifiez manuellement le statut"
5. User peut re-checker plus tard avec GET /check-payment/1
```

## Logs Backend

Tous les changements sont loggés dans `writable/logs/`:

```
[2025-10-30 11:00:15] INFO → Paiement #1 mis à jour: pending → paid
[2025-10-30 11:00:15] INFO → Boost activé pour le paiement #1
```

En cas d'erreur:
```
[2025-10-30 11:00:15] ERROR → Erreur cURL check status: Connection timeout
[2025-10-30 11:00:15] ERROR → Réponse invalide check status: {"error": "Invalid reference"}
```

## Sécurité

- ✅ Endpoint protégé par JWT (`auth` filter)
- ✅ User ne peut vérifier que ses propres paiements
- ✅ Pas de double activation (vérifie si déjà payé)
- ✅ Idempotent: plusieurs appels = même résultat
- ✅ Logs complets pour audit

## Documentation Complète

Voir `BOOST_PAYMENT_POLLING.md` pour:
- Exemples de code frontend (React, Vanilla JS)
- Détails techniques du backend
- Architecture complète
- Évolutions possibles (WebSockets, Webhooks)

## Support

Si le statut reste bloqué sur "pending":

1. **Vérifier les logs backend:**
   ```
   writable/logs/log-2025-10-30.log
   ```

2. **Tester l'API Campay directement:**
   ```bash
   curl 'https://demo.campay.net/api/transaction/<reference>/' \
     -H 'Authorization: Token 31d12e057d6586e46a981b5ee64a1bed3d77974b'
   ```

3. **Vérifier la connexion:**
   ```bash
   php check_database.php
   ```

4. **Nettoyer et recommencer:**
   ```sql
   DELETE FROM payments WHERE id = 1;
   DELETE FROM ad_promotions WHERE payment_reference = '<ref>';
   UPDATE ads SET is_boosted = 0, boost_start = NULL, boost_end = NULL WHERE id = 42;
   ```
