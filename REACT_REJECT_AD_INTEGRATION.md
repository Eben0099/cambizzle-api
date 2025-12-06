# Intégration Reject Ad + WhatsApp - Guide React Admin

## 🎯 Vue d'ensemble

Quand un admin rejette une annonce:
1. L'annonce passe en statut `rejected`
2. Un lien WhatsApp est généré automatiquement
3. L'admin peut envoyer une notification au propriétaire

```
Admin rejette l'annonce
        ↓
API met à jour le statut
        ↓
Génère le lien WhatsApp
        ↓
Retourne le lien
        ↓
Admin peut notifier le propriétaire
```

---

## 🔌 Endpoint API

### PUT `/api/admin/ads/{id}/reject`

**Headers requis:**
```
Authorization: Bearer ADMIN_JWT_TOKEN
Content-Type: application/json
```

**Request:**
```json
{
  "reason": "Contenu inapproprié",
  "notes": "Photos non conformes aux conditions d'utilisation"
}
```

**Response (Succès):**
```json
{
  "success": true,
  "message": "Annonce rejetée avec succès",
  "data": {
    "ad_id": 123,
    "whatsapp_notification_link": "https://wa.me/237677123456?text=Votre%20annonce%20%22iPhone%2013%22%20a%20%C3%A9t%C3%A9%20rejet%C3%A9e..."
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

## 📱 Composant React - Reject Ad Modal (Admin)

```jsx
import React, { useState } from 'react';
import './RejectAdModal.css';

export function RejectAdModal({ ad, onClose, onSuccess, adminToken }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [whatsappLink, setWhatsappLink] = useState('');
  
  const [formData, setFormData] = useState({
    reason: '',
    notes: ''
  });

  const rejectReasons = [
    { value: 'Contenu inapproprié', label: '🚫 Contenu inapproprié' },
    { value: 'Photos non conformes', label: '📸 Photos non conformes' },
    { value: 'Prix irréaliste', label: '💰 Prix irréaliste' },
    { value: 'Spam', label: '📧 Spam' },
    { value: 'Fausse annonce', label: '⚠️ Fausse annonce' },
    { value: 'Autre', label: '📝 Autre raison' }
  ];

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess(false);

    // Validation
    if (!formData.reason) {
      setError('Veuillez sélectionner une raison du rejet');
      return;
    }

    setLoading(true);

    try {
      const response = await fetch(`https://votre-api.com/api/admin/ads/${ad.id}/reject`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${adminToken}`
        },
        body: JSON.stringify({
          reason: formData.reason,
          notes: formData.notes
        })
      });

      const data = await response.json();

      if (data.success) {
        setSuccess(true);
        if (data.data.whatsapp_notification_link) {
          setWhatsappLink(data.data.whatsapp_notification_link);
        }
        // Callback de succès
        if (onSuccess) {
          onSuccess(ad.id);
        }
      } else {
        setError(data.message || 'Erreur lors du rejet');
      }
    } catch (err) {
      setError('Erreur réseau: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleWhatsAppClick = () => {
    if (whatsappLink) {
      window.open(whatsappLink, '_blank');
    }
  };

  // État de succès
  if (success) {
    return (
      <div className="modal-overlay" onClick={onClose}>
        <div className="modal-content reject-success" onClick={(e) => e.stopPropagation()}>
          <div className="modal-header">
            <h2>❌ Annonce rejetée</h2>
            <button className="close-btn" onClick={onClose}>✕</button>
          </div>

          <div className="modal-body">
            <p className="success-message">
              L'annonce <strong>"{ad.title}"</strong> a été rejetée avec succès.
            </p>

            <div className="rejection-info">
              <p><strong>Raison:</strong> {formData.reason}</p>
              {formData.notes && (
                <p><strong>Notes:</strong> {formData.notes}</p>
              )}
            </div>

            {whatsappLink && (
              <div className="whatsapp-section">
                <p className="section-title">Informer le propriétaire:</p>
                <button 
                  className="whatsapp-btn"
                  onClick={handleWhatsAppClick}
                >
                  💬 Notifier via WhatsApp
                </button>
                <p className="small-text">
                  Le propriétaire sera informé automatiquement du rejet et de la raison.
                </p>
              </div>
            )}

            <button 
              className="close-btn-primary"
              onClick={onClose}
            >
              Fermer
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Formulaire de rejet
  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2>❌ Rejeter cette annonce</h2>
          <button className="close-btn" onClick={onClose}>✕</button>
        </div>

        <div className="modal-body">
          <div className="ad-preview">
            <img src={ad.image || '/placeholder.png'} alt={ad.title} />
            <div className="ad-info">
              <h3>{ad.title}</h3>
              <p className="ad-price">{ad.price} FCFA</p>
              <p className="ad-location">📍 {ad.location_name}</p>
            </div>
          </div>

          <form onSubmit={handleSubmit}>
            {/* Raison du rejet */}
            <div className="form-group">
              <label htmlFor="reason">Raison du rejet *</label>
              <select
                id="reason"
                value={formData.reason}
                onChange={(e) => setFormData({
                  ...formData,
                  reason: e.target.value
                })}
                disabled={loading}
                className="form-control"
              >
                <option value="">-- Sélectionner une raison --</option>
                {rejectReasons.map(reason => (
                  <option key={reason.value} value={reason.value}>
                    {reason.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Notes pour modération */}
            <div className="form-group">
              <label htmlFor="notes">Notes de modération (optionnel)</label>
              <textarea
                id="notes"
                placeholder="Ajoutez des détails sur le rejet..."
                value={formData.notes}
                onChange={(e) => setFormData({
                  ...formData,
                  notes: e.target.value
                })}
                disabled={loading}
                className="form-control"
                rows="4"
              />
              <small className="help-text">
                Ces notes seront visibles par l'équipe admin uniquement
              </small>
            </div>

            {/* Message d'erreur */}
            {error && (
              <div className="alert alert-error">
                ❌ {error}
              </div>
            )}

            {/* Avertissement */}
            <div className="alert alert-warning">
              ⚠️ Cette action rejettera définitivement l'annonce. Le propriétaire en sera notifié.
            </div>

            {/* Boutons */}
            <div className="modal-footer">
              <button
                type="button"
                className="btn btn-secondary"
                onClick={onClose}
                disabled={loading}
              >
                Annuler
              </button>
              <button
                type="submit"
                className="btn btn-danger"
                disabled={loading || !formData.reason}
              >
                {loading ? 'Rejet en cours...' : 'Rejeter l\'annonce'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
```

---

## 🎨 Styles CSS

```css
/* RejectAdModal.css */

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  max-width: 600px;
  width: 100%;
  max-height: 85vh;
  overflow-y: auto;
}

.modal-content.reject-success {
  max-width: 500px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
  background: #fff5f5;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  color: #d32f2f;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #999;
  padding: 0;
  width: 30px;
  height: 30px;
}

.close-btn:hover {
  color: #333;
}

.modal-body {
  padding: 20px;
}

/* Prévisualisation de l'annonce */
.ad-preview {
  display: flex;
  gap: 15px;
  padding: 15px;
  background: #f9f9f9;
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid #e0e0e0;
}

.ad-preview img {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 4px;
}

.ad-info h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  color: #333;
}

.ad-price {
  font-size: 18px;
  font-weight: bold;
  color: #1976d2;
  margin: 5px 0;
}

.ad-location {
  color: #666;
  font-size: 14px;
  margin: 5px 0;
}

/* Formulaire */
.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  font-family: inherit;
}

.form-control:focus {
  outline: none;
  border-color: #d32f2f;
  box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
}

.form-control:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.help-text {
  display: block;
  margin-top: 6px;
  color: #999;
  font-size: 12px;
}

/* Alertes */
.alert {
  padding: 12px 16px;
  border-radius: 4px;
  margin-bottom: 16px;
  font-size: 14px;
}

.alert-error {
  background-color: #ffebee;
  color: #c62828;
  border: 1px solid #ef5350;
}

.alert-warning {
  background-color: #fff3e0;
  color: #e65100;
  border: 1px solid #ff9800;
}

/* Boutons */
.modal-footer {
  display: flex;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #eee;
}

.btn {
  padding: 10px 16px;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  flex: 1;
}

.btn-danger {
  background-color: #d32f2f;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #c62828;
}

.btn-danger:disabled {
  background-color: #ffcdd2;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #eeeeee;
}

/* État de succès */
.success-message {
  color: #1b5e20;
  margin-bottom: 20px;
  font-size: 16px;
  line-height: 1.6;
}

.rejection-info {
  background-color: #fff3cd;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
  border-left: 4px solid #ff9800;
}

.rejection-info p {
  margin: 8px 0;
  color: #333;
}

.whatsapp-section {
  background-color: #e8f5e9;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid #c8e6c9;
}

.section-title {
  margin-top: 0;
  margin-bottom: 15px;
  color: #333;
  font-weight: 600;
}

.whatsapp-btn {
  width: 100%;
  padding: 12px 16px;
  background-color: #25d366;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 10px;
}

.whatsapp-btn:hover {
  background-color: #20ba5a;
}

.small-text {
  color: #666;
  font-size: 12px;
  margin: 0;
}

.close-btn-primary {
  width: 100%;
  padding: 12px 16px;
  background-color: #1976d2;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.close-btn-primary:hover {
  background-color: #1565c0;
}

/* Responsive */
@media (max-width: 600px) {
  .modal-overlay {
    padding: 0;
  }

  .modal-content {
    border-radius: 0;
    max-height: 100vh;
  }

  .ad-preview {
    flex-direction: column;
  }

  .ad-preview img {
    width: 100%;
    height: 200px;
  }
}
```

---

## 📱 Intégration dans le Dashboard Admin

```jsx
import React, { useState, useEffect } from 'react';
import { RejectAdModal } from './RejectAdModal';

export function AdminModerationPage() {
  const [pendingAds, setPendingAds] = useState([]);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [selectedAd, setSelectedAd] = useState(null);
  const adminToken = localStorage.getItem('adminToken');

  useEffect(() => {
    fetchPendingAds();
  }, []);

  const fetchPendingAds = async () => {
    try {
      const response = await fetch('https://votre-api.com/api/admin/ads/pending', {
        headers: {
          'Authorization': `Bearer ${adminToken}`
        }
      });
      const data = await response.json();
      if (data.success) {
        setPendingAds(data.data.ads || []);
      }
    } catch (error) {
      console.error('Error fetching ads:', error);
    }
  };

  const handleRejectClick = (ad) => {
    setSelectedAd(ad);
    setShowRejectModal(true);
  };

  const handleApprove = async (adId) => {
    try {
      const response = await fetch(`https://votre-api.com/api/admin/ads/${adId}/approve`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${adminToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ notes: 'Annonce conforme' })
      });

      if (response.ok) {
        // Rafraîchir la liste
        fetchPendingAds();
      }
    } catch (error) {
      console.error('Error approving ad:', error);
    }
  };

  const handleRejectSuccess = (adId) => {
    // Supprimer l'annonce de la liste
    setPendingAds(pendingAds.filter(ad => ad.id !== adId));
    setShowRejectModal(false);
  };

  return (
    <div className="admin-moderation">
      <h1>Annonces en attente de modération</h1>
      
      <div className="ads-grid">
        {pendingAds.map(ad => (
          <div key={ad.id} className="ad-card">
            <img src={ad.image} alt={ad.title} />
            <div className="ad-card-body">
              <h3>{ad.title}</h3>
              <p className="price">{ad.price} FCFA</p>
              <p className="location">📍 {ad.location_name}</p>
            </div>
            <div className="ad-card-footer">
              <button 
                className="btn-approve"
                onClick={() => handleApprove(ad.id)}
              >
                ✅ Approuver
              </button>
              <button 
                className="btn-reject"
                onClick={() => handleRejectClick(ad)}
              >
                ❌ Rejeter
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Modal de rejet */}
      {showRejectModal && selectedAd && (
        <RejectAdModal
          ad={selectedAd}
          adminToken={adminToken}
          onClose={() => setShowRejectModal(false)}
          onSuccess={handleRejectSuccess}
        />
      )}
    </div>
  );
}
```

---

## 🧪 Test avec Postman

```
PUT https://votre-api.com/api/admin/ads/123/reject

Headers:
Authorization: Bearer ADMIN_JWT_TOKEN
Content-Type: application/json

Body (JSON):
{
  "reason": "Contenu inapproprié",
  "notes": "Photos non conformes aux CGU"
}
```

**Réponse:**
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

---

## ✅ Checklist d'intégration

### Préparation Admin
- [ ] Créer le composant `RejectAdModal.jsx`
- [ ] Ajouter les styles CSS
- [ ] Configurer l'authentification admin
- [ ] Vérifier que vous avez un token admin valide

### Intégration
- [ ] Importer le composant dans la page de modération
- [ ] Ajouter un bouton "Rejeter" sur chaque annonce
- [ ] Passer l'annonce complète et le token au modal
- [ ] Gérer le callback de succès

### Fonctionnalités
- [ ] Valider la raison du rejet
- [ ] Afficher l'aperçu de l'annonce
- [ ] Gérer l'état de chargement
- [ ] Afficher le message de succès
- [ ] Afficher le bouton WhatsApp après rejet
- [ ] Rafraîchir la liste après rejet

### Sécurité
- [ ] Vérifier que l'utilisateur est admin
- [ ] Vérifier que le token admin est valide
- [ ] Ne pas exposer d'informations sensibles
- [ ] Utiliser HTTPS en production

---

## 🔗 Raisons de rejet disponibles

```
Contenu inapproprié    → 🚫
Photos non conformes   → 📸
Prix irréaliste        → 💰
Spam                   → 📧
Fausse annonce         → ⚠️
Autre                  → 📝
```

---

## 📊 Flux complet

```
1. Admin consulte les annonces en attente
        ↓
2. Admin clique "Rejeter" sur une annonce
        ↓
3. Modal s'ouvre avec aperçu de l'annonce
        ↓
4. Admin sélectionne raison + notes
        ↓
5. Clique sur "Rejeter l'annonce"
        ↓
6. API PUT /admin/ads/{id}/reject
        ↓
7. Backend rejette + génère lien WhatsApp
        ↓
8. Retourne succès + lien WhatsApp
        ↓
9. Modal affiche confirmation + bouton WhatsApp
        ↓
10. Admin peut notifier le propriétaire via WhatsApp
        ↓
11. Liste des annonces est rafraîchie
```

---

## 🚀 Exemple rapide

```jsx
// Dans votre dashboard admin
import { RejectAdModal } from './components/RejectAdModal';

function ModerationPage() {
  const [showModal, setShowModal] = useState(false);
  const [ad, setAd] = useState(null);

  return (
    <div>
      <button onClick={() => {
        setAd({ id: 123, title: 'iPhone 13', price: 250000 });
        setShowModal(true);
      }}>
        ❌ Rejeter l'annonce
      </button>

      {showModal && (
        <RejectAdModal
          ad={ad}
          adminToken={localStorage.getItem('adminToken')}
          onClose={() => setShowModal(false)}
          onSuccess={(adId) => console.log('Rejetée:', adId)}
        />
      )}
    </div>
  );
}
```

C'est prêt! 🎉
