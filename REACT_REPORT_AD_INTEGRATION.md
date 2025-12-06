# Intégration Report Ad + WhatsApp - Guide React

## 🎯 Vue d'ensemble

Quand un utilisateur signale une annonce:
1. Le report est créé en BD
2. Un lien WhatsApp est généré automatiquement
3. L'utilisateur reçoit une notification avec le lien

```
Utilisateur clique sur "Signaler"
        ↓
Formulaire de signalement s'ouvre
        ↓
Utilisateur remplit et soumet
        ↓
API crée le report
        ↓
Retourne lien WhatsApp
        ↓
Affiche bouton "Ouvrir WhatsApp"
```

---

## 🔌 Endpoint API

### POST `/api/reports`

**Headers requis:**
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

**Request:**
```json
{
  "reported_ad_id": 123,
  "report_type": "ad",
  "report_reason": "spam",
  "description": "Cette annonce est spam, envoyée plusieurs fois"
}
```

**Response (Succès):**
```json
{
  "success": true,
  "message": "Signalement créé. Propriétaire notifié.",
  "data": {
    "id": 456,
    "whatsapp_notification_link": "https://wa.me/237677123456?text=Votre%20annonce%20%22Vendre%20iPhone%22%20a%20%C3%A9t%C3%A9%20report%C3%A9e..."
  }
}
```

**Response (Erreur):**
```json
{
  "success": false,
  "message": "Vous ne pouvez pas signaler votre propre annonce",
  "errors": {}
}
```

---

## 📱 Composant React - Report Ad Modal

```jsx
import React, { useState } from 'react';
import './ReportAdModal.css';

export function ReportAdModal({ adId, adTitle, onClose, token }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [whatsappLink, setWhatsappLink] = useState('');
  
  const [formData, setFormData] = useState({
    report_reason: '',
    description: ''
  });

  const reportReasons = [
    { value: 'spam', label: '🚫 Spam / Doublons' },
    { value: 'fraud', label: '⚠️ Fraude / Arnaque' },
    { value: 'abuse', label: '😤 Contenu offensant' },
    { value: 'other', label: '📝 Autre raison' }
  ];

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess(false);

    // Validation
    if (!formData.report_reason) {
      setError('Veuillez sélectionner une raison');
      return;
    }

    setLoading(true);

    try {
      const response = await fetch('https://votre-api.com/api/reports', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          reported_ad_id: adId,
          report_type: 'ad',
          report_reason: formData.report_reason,
          description: formData.description
        })
      });

      const data = await response.json();

      if (data.success) {
        setSuccess(true);
        if (data.data.whatsapp_notification_link) {
          setWhatsappLink(data.data.whatsapp_notification_link);
        }
      } else {
        setError(data.message || 'Erreur lors du signalement');
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
        <div className="modal-content" onClick={(e) => e.stopPropagation()}>
          <div className="modal-header">
            <h2>✅ Signalement créé</h2>
            <button className="close-btn" onClick={onClose}>✕</button>
          </div>

          <div className="modal-body success">
            <p className="success-message">
              Merci pour votre signalement. Le propriétaire de l'annonce a été notifié.
            </p>

            {whatsappLink && (
              <div className="whatsapp-section">
                <p className="section-title">Informer directement le propriétaire:</p>
                <button 
                  className="whatsapp-btn"
                  onClick={handleWhatsAppClick}
                >
                  💬 Ouvrir WhatsApp
                </button>
                <p className="small-text">
                  Vous pouvez aussi copier le lien et le partager manuellement.
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

  // Formulaire normal
  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2>Signaler cette annonce</h2>
          <button className="close-btn" onClick={onClose}>✕</button>
        </div>

        <div className="modal-body">
          <p className="modal-subtitle">
            Annonce: <strong>{adTitle}</strong>
          </p>

          <form onSubmit={handleSubmit}>
            {/* Raison du signalement */}
            <div className="form-group">
              <label htmlFor="reason">Raison du signalement *</label>
              <select
                id="reason"
                value={formData.report_reason}
                onChange={(e) => setFormData({
                  ...formData,
                  report_reason: e.target.value
                })}
                disabled={loading}
                className="form-control"
              >
                <option value="">-- Sélectionner une raison --</option>
                {reportReasons.map(reason => (
                  <option key={reason.value} value={reason.value}>
                    {reason.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Description optionnelle */}
            <div className="form-group">
              <label htmlFor="description">Description (optionnel)</label>
              <textarea
                id="description"
                placeholder="Décrivez le problème en détail..."
                value={formData.description}
                onChange={(e) => setFormData({
                  ...formData,
                  description: e.target.value
                })}
                disabled={loading}
                className="form-control"
                rows="4"
              />
              <small className="help-text">
                Cela nous aide à mieux comprendre le problème
              </small>
            </div>

            {/* Message d'erreur */}
            {error && (
              <div className="alert alert-error">
                ❌ {error}
              </div>
            )}

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
                className="btn btn-primary"
                disabled={loading || !formData.report_reason}
              >
                {loading ? 'Traitement...' : 'Signaler'}
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

## 🎨 Styles CSS pour le Modal

```css
/* ReportAdModal.css */

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  max-width: 500px;
  width: 100%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  color: #333;
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
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  color: #333;
}

.modal-body {
  padding: 20px;
}

.modal-subtitle {
  color: #666;
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 14px;
}

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
  border-color: #1976d2;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
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

.btn-primary {
  background-color: #1976d2;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #1565c0;
}

.btn-primary:disabled {
  background-color: #ccc;
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

/* États de succès */
.modal-body.success {
  text-align: center;
}

.success-message {
  color: #2e7d32;
  margin-bottom: 20px;
  font-size: 16px;
  line-height: 1.5;
}

.whatsapp-section {
  background-color: #f0f8e8;
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
  transition: background-color 0.3s;
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

  .modal-header {
    padding: 16px;
  }

  .modal-body {
    padding: 16px;
  }
}
```

---

## 📱 Intégration dans la page Ad Details

```jsx
import React, { useState } from 'react';
import { ReportAdModal } from './ReportAdModal';

export function AdDetailsPage({ adId, token }) {
  const [showReportModal, setShowReportModal] = useState(false);
  const [ad, setAd] = useState(null);

  // ... récupérer les détails de l'annonce ...

  return (
    <div className="ad-details">
      <div className="ad-header">
        <h1>{ad?.title}</h1>
        <div className="ad-actions">
          <button 
            className="btn-report"
            onClick={() => setShowReportModal(true)}
            title="Signaler cette annonce"
          >
            🚨 Signaler
          </button>
        </div>
      </div>

      {/* ... contenu de l'annonce ... */}

      {/* Modal de signalement */}
      {showReportModal && (
        <ReportAdModal
          adId={adId}
          adTitle={ad?.title}
          token={token}
          onClose={() => setShowReportModal(false)}
        />
      )}
    </div>
  );
}
```

---

## 🎯 Alternative: Bouton Report sur Card

```jsx
// Dans une liste d'annonces
export function AdCard({ ad, token, onReportSuccess }) {
  const [showReportModal, setShowReportModal] = useState(false);

  return (
    <div className="ad-card">
      <img src={ad.image} alt={ad.title} />
      
      <div className="ad-card-content">
        <h3>{ad.title}</h3>
        <p className="price">{ad.price} FCFA</p>
        <p className="location">📍 {ad.location_name}</p>
      </div>

      <div className="ad-card-footer">
        <button className="btn-contact">Contacter</button>
        <button 
          className="btn-report-icon"
          onClick={() => setShowReportModal(true)}
          title="Signaler"
        >
          🚨
        </button>
      </div>

      {showReportModal && (
        <ReportAdModal
          adId={ad.id}
          adTitle={ad.title}
          token={token}
          onClose={() => setShowReportModal(false)}
        />
      )}
    </div>
  );
}
```

---

## 🔐 Gestion du Token JWT

```jsx
// Dans votre service d'authentification
export class AuthService {
  getToken() {
    return localStorage.getItem('authToken');
  }

  setToken(token) {
    localStorage.setItem('authToken', token);
  }

  clearToken() {
    localStorage.removeItem('authToken');
  }

  isAuthenticated() {
    return !!this.getToken();
  }
}

// Utilisation
function App() {
  const [token, setToken] = useState(AuthService.getToken());

  return (
    <div>
      {token ? (
        <AdDetailsPage token={token} />
      ) : (
        <LoginPage onLoginSuccess={(newToken) => setToken(newToken)} />
      )}
    </div>
  );
}
```

---

## 🧪 Test avec Postman

```
POST https://votre-api.com/api/reports

Headers:
Authorization: Bearer eyJhbGc...
Content-Type: application/json

Body (JSON):
{
  "reported_ad_id": 123,
  "report_type": "ad",
  "report_reason": "spam",
  "description": "Cette annonce est dupliquée"
}
```

---

## ✅ Checklist d'intégration

### Préparation
- [ ] Créer le composant `ReportAdModal.jsx`
- [ ] Ajouter les styles CSS
- [ ] Configurer le service d'authentification
- [ ] Vérifier que vous avez un token JWT valide

### Intégration
- [ ] Importer le composant dans la page Ad Details
- [ ] Ajouter un bouton "Signaler"
- [ ] Passer l'`adId`, `adTitle` et `token` au composant
- [ ] Tester le formulaire de signalement

### Fonctionnalités
- [ ] Valider les champs requis
- [ ] Afficher les erreurs correctement
- [ ] Gérer l'état de chargement (loader)
- [ ] Afficher le message de succès
- [ ] Afficher le bouton WhatsApp après succès
- [ ] Tester l'ouverture du lien WhatsApp

### Styling
- [ ] Adapter les couleurs à votre design
- [ ] Ajouter une icône personnalisée
- [ ] Tester sur mobile
- [ ] Tester le responsive

### Sécurité
- [ ] Vérifier que le token est valide
- [ ] Vérifier que l'utilisateur est authentifié
- [ ] Ne pas exposer le token en log
- [ ] Utiliser HTTPS en production

---

## 🔗 Raisons de signalement disponibles

```
spam    → 🚫 Spam / Doublons
fraud   → ⚠️ Fraude / Arnaque
abuse   → 😤 Contenu offensant
other   → 📝 Autre raison
```

---

## 📊 Flux complet

```
1. Utilisateur clique "Signaler"
        ↓
2. Modal s'ouvre
        ↓
3. Utilisateur sélectionne raison + description
        ↓
4. Clique sur "Signaler"
        ↓
5. API POST /reports
        ↓
6. Backend crée report + génère lien WhatsApp
        ↓
7. Retourne succès + lien WhatsApp
        ↓
8. Modal affiche message succès + bouton WhatsApp
        ↓
9. Utilisateur peut cliquer pour ouvrir WhatsApp
        ↓
10. Message pré-rempli s'ouvre dans WhatsApp
```

---

## 🚀 Exemple d'intégration rapide

```jsx
// Dans votre page Ad
import { ReportAdModal } from './components/ReportAdModal';

export default function AdPage() {
  const [showReport, setShowReport] = useState(false);
  const token = localStorage.getItem('authToken');

  return (
    <div>
      <button onClick={() => setShowReport(true)}>🚨 Signaler</button>

      {showReport && (
        <ReportAdModal
          adId={123}
          adTitle="iPhone 13"
          token={token}
          onClose={() => setShowReport(false)}
        />
      )}
    </div>
  );
}
```

C'est tout! Vous êtes prêt à intégrer. 🎉
