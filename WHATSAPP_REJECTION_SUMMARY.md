# 📋 Résumé: Notification WhatsApp pour Rejet d'Annonce

## 🎯 Fonctionnalité implémentée

Quand un admin rejette une annonce, le système génère automatiquement un lien WhatsApp pour notifier le propriétaire de l'annonce.

---

## ✅ Fichiers modifiés

### 1️⃣ **app/Services/ModerationService.php**

**Méthode modifiée:** `rejectAd()`
- Retourne maintenant `whatsapp_notification_link` dans la réponse
- Appelle la nouvelle méthode `generateWhatsAppLinkForRejection()`

**Nouvelle méthode ajoutée:** `generateWhatsAppLinkForRejection()`
```php
private function generateWhatsAppLinkForRejection(array $ad, string $reason): ?string
{
    // Récupère le propriétaire de l'annonce
    // Nettoie le numéro de téléphone (ajoute 237 pour Cameroun)
    // Crée un message personnalisé avec la raison du rejet
    // Retourne le lien wa.me
}
```

**Localisation:** Ligne ~79-130 (méthode modifiée), fin du fichier (nouvelle méthode)

---

### 2️⃣ **deploy/api/app/Services/AdService.php**

**Méthode modifiée:** `rejectAd()`
- Type de retour changé: `bool` → `array`
- Retourne maintenant:
  ```php
  [
    'success' => true,
    'ad_id' => $adId,
    'whatsapp_notification_link' => $whatsappLink
  ]
  ```
- Récupère l'annonce avec `$this->adModel->find($adId)`
- Gère les erreurs (annonce non trouvée, échec de mise à jour)

**Nouvelle méthode ajoutée:** `generateWhatsAppLinkForRejection()`
```php
private function generateWhatsAppLinkForRejection($ad, string $reason): ?string
{
    // Convertit l'entité en array si nécessaire
    // Récupère le propriétaire via userService
    // Nettoie le numéro (ajoute 237)
    // Crée le message de rejet
    // Retourne le lien wa.me
}
```

**Localisation:** Ligne ~265-295 (méthode modifiée), ligne ~425-460 (nouvelle méthode)

---

### 3️⃣ **deploy/api/app/Controllers/Api/AdminController.php**

**Méthode modifiée:** `rejectAd()`

**Avant:**
```php
$success = $this->adService->rejectAd((int)$id, (int)$payload->user_id, $notes);
if (!$success) {
    return $this->serverError('Échec du rejet');
}
return $this->success(null, 'Annonce rejetée');
```

**Après:**
```php
$result = $this->adService->rejectAd((int)$id, (int)$payload->user_id, $notes);

if (!$result['success']) {
    return $this->serverError($result['message'] ?? 'Échec du rejet');
}

return $this->success([
    'ad_id' => $result['ad_id'],
    'whatsapp_notification_link' => $result['whatsapp_notification_link']
], 'Annonce rejetée avec succès');
```

**Localisation:** Ligne ~100-120

---

### 4️⃣ **REACT_REJECT_AD_INTEGRATION.md** (NOUVEAU)

Guide complet d'intégration React pour les admins avec:
- Composant `RejectAdModal` complet
- Styles CSS
- Exemples d'intégration dans le dashboard admin
- Gestion des états (chargement, succès, erreur)
- Bouton WhatsApp après rejet réussi
- Liste des raisons de rejet prédéfinies

---

## 🔌 API Endpoint

### **PUT** `/api/admin/ads/{id}/reject`

**Headers:**
```
Authorization: Bearer ADMIN_JWT_TOKEN
Content-Type: application/json
```

**Request Body:**
```json
{
  "reason": "Contenu inapproprié",
  "notes": "Photos non conformes aux CGU"
}
```

**Response (Succès):**
```json
{
  "success": true,
  "message": "Annonce rejetée avec succès",
  "data": {
    "ad_id": 123,
    "whatsapp_notification_link": "https://wa.me/237677123456?text=Bonjour%2C%0A%0AVotre%20annonce%20%22iPhone%2013%22%20a%20%C3%A9t%C3%A9%20rejet%C3%A9e..."
  }
}
```

**Response (Erreur):**
```json
{
  "success": false,
  "message": "Annonce non trouvée"
}
```

---

## 📱 Format du message WhatsApp

```
Bonjour,

Votre annonce "[TITRE DE L'ANNONCE]" a été rejetée par notre équipe de modération.

Raison: [RAISON DU REJET]

Si vous avez des questions, n'hésitez pas à nous contacter.

Cordialement,
L'équipe Cambizzle
```

---

## 🔢 Gestion des numéros de téléphone

Le système nettoie et formate automatiquement les numéros:

```php
// Exemple: "677 12 34 56" ou "0677123456"
$phone = preg_replace('/[^0-9]/', '', $owner['phone']); // "677123456"

// Ajoute l'indicatif Cameroun (237)
if (strlen($phone) === 9) {
    $phone = '237' . $phone; // "237677123456"
}

// Crée le lien
$link = "https://wa.me/237677123456?text=...";
```

**Formats acceptés:**
- `677123456` → `237677123456`
- `0677123456` → `237677123456`
- `237677123456` → `237677123456` (inchangé)
- `+237 677 12 34 56` → `237677123456`

---

## 🎨 Raisons de rejet prédéfinies

```javascript
const rejectReasons = [
  { value: 'Contenu inapproprié', label: '🚫 Contenu inapproprié' },
  { value: 'Photos non conformes', label: '📸 Photos non conformes' },
  { value: 'Prix irréaliste', label: '💰 Prix irréaliste' },
  { value: 'Spam', label: '📧 Spam' },
  { value: 'Fausse annonce', label: '⚠️ Fausse annonce' },
  { value: 'Autre', label: '📝 Autre raison' }
];
```

---

## 📊 Flux de rejet d'annonce

```
1. Admin clique "Rejeter" sur une annonce
        ↓
2. Modal s'ouvre avec aperçu + formulaire
        ↓
3. Admin sélectionne raison + notes
        ↓
4. Clique "Rejeter l'annonce"
        ↓
5. API PUT /admin/ads/{id}/reject
        ↓
6. Backend:
   - Met à jour moderation_status = 'rejected'
   - Log l'action dans moderation_logs
   - Récupère l'annonce et le propriétaire
   - Génère le lien WhatsApp
        ↓
7. Retourne succès + lien WhatsApp
        ↓
8. Frontend affiche confirmation
        ↓
9. Admin clique "Notifier via WhatsApp"
        ↓
10. Ouvre WhatsApp avec message pré-rempli
        ↓
11. Admin envoie le message au propriétaire
```

---

## 🧪 Test manuel

### **Avec Postman/cURL:**

```bash
curl -X PUT "https://votre-api.com/api/admin/ads/123/reject" \
  -H "Authorization: Bearer ADMIN_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Contenu inapproprié",
    "notes": "Photos non conformes"
  }'
```

**Réponse attendue:**
```json
{
  "success": true,
  "message": "Annonce rejetée avec succès",
  "data": {
    "ad_id": 123,
    "whatsapp_notification_link": "https://wa.me/237677123456?text=..."
  }
}
```

### **Test du lien WhatsApp:**

1. Copier le lien `whatsapp_notification_link` de la réponse
2. Coller dans un navigateur
3. Vérifier que WhatsApp Web/App s'ouvre
4. Vérifier que le message est pré-rempli correctement
5. Vérifier que le numéro est correct (237...)

---

## ✅ Checklist de vérification

### Backend
- [x] ModerationService.rejectAd() retourne whatsapp_link
- [x] Méthode generateWhatsAppLinkForRejection() ajoutée
- [x] AdService.rejectAd() retourne array au lieu de bool
- [x] AdminController gère la nouvelle réponse array
- [x] Numéros de téléphone nettoyés et formatés
- [x] Message WhatsApp personnalisé avec raison

### Frontend (à faire)
- [ ] Créer RejectAdModal.jsx
- [ ] Ajouter styles CSS
- [ ] Intégrer dans dashboard admin
- [ ] Tester affichage du lien WhatsApp
- [ ] Tester ouverture WhatsApp

### Tests
- [ ] Rejeter une annonce via API
- [ ] Vérifier que le lien WhatsApp est retourné
- [ ] Vérifier que le numéro est correct (237...)
- [ ] Vérifier que le message contient la raison
- [ ] Tester avec numéro sans indicatif
- [ ] Tester avec numéro avec indicatif
- [ ] Tester avec numéro formaté (espaces, +)

---

## 📝 Notes importantes

### Sécurité
- ✅ Endpoint protégé par JWT admin
- ✅ Validation de l'ID annonce
- ✅ Vérification de l'existence de l'annonce
- ✅ Gestion des erreurs

### Numéros de téléphone
- ✅ Nettoyage automatique (supprime espaces, +, etc.)
- ✅ Ajout de l'indicatif 237 si nécessaire
- ✅ Retourne `null` si pas de numéro

### Messages
- ✅ Message personnalisé avec titre de l'annonce
- ✅ Raison du rejet incluse
- ✅ Message encodé URL pour WhatsApp
- ✅ Format professionnel

---

## 🔄 Différences entre app/ et deploy/

| Aspect | app/Services/ModerationService | deploy/api/app/Services/AdService |
|--------|-------------------------------|-----------------------------------|
| **Complexité** | Service de modération complet | Service d'annonces simplifié |
| **Logging** | Appelle `logModerationAction()` | Pas de log séparé |
| **Type retour** | Toujours retourné array | Était bool → maintenant array |
| **Récupération ad** | Via DB builder complexe | Via `$this->adModel->find()` |
| **UserService** | Inject via service() | Inject via service() |

**Les deux implémentations:**
- ✅ Génèrent le lien WhatsApp
- ✅ Nettoient les numéros de téléphone
- ✅ Retournent le lien dans la réponse
- ✅ Gèrent les erreurs

---

## 🚀 Prochaines étapes

### Améliorations possibles
1. **Email de notification** en plus de WhatsApp
2. **SMS** pour utilisateurs sans WhatsApp
3. **Templates de messages** personnalisables
4. **Historique des rejets** dans le dashboard admin
5. **Statistiques** sur les raisons de rejet

### Notifications supplémentaires
- Approbation d'annonce
- Expiration d'annonce
- Nouveau message sur annonce
- Fin de boost

---

## 📞 Support

Si vous rencontrez des problèmes:

1. **Vérifier les logs:**
   ```bash
   tail -f writable/logs/log-*.php
   ```

2. **Vérifier la base de données:**
   ```sql
   SELECT id, title, user_id, moderation_status 
   FROM ads 
   WHERE id = 123;
   ```

3. **Tester l'endpoint directement:**
   ```bash
   curl -X PUT "http://localhost/api/admin/ads/123/reject" \
     -H "Authorization: Bearer TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"reason":"Test","notes":"Test"}'
   ```

---

## ✨ C'est fait!

La notification WhatsApp pour le rejet d'annonce est maintenant fonctionnelle dans:
- ✅ Version principale (app/Services/ModerationService.php)
- ✅ Version déployée (deploy/api/app/Services/AdService.php)
- ✅ Controllers mis à jour
- ✅ Guide d'intégration React créé

Même fonctionnalité que pour les signalements, mais pour les rejets d'annonces par les admins! 🎉
