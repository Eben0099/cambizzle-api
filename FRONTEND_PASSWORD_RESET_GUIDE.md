# Guide d'Intégration Password Reset au Frontend

## 📋 Vue d'ensemble

Le système de réinitialisation de mot de passe fonctionne en **2 étapes**:

```
1️⃣ Utilisateur demande reset (numéro téléphone)
   ↓
2️⃣ Reçoit un code de 6 chiffres
   ↓
3️⃣ Entre son code + nouveau mot de passe
   ↓
4️⃣ Mot de passe réinitialisé ✅
```

---

## 🔌 Endpoints API

### 1️⃣ POST `/api/auth/forgot-password`
**Demander la réinitialisation (Étape 1)**

**Request:**
```json
{
  "phone": "237677123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Si ce numéro de téléphone existe dans notre système, vous recevrez un code de réinitialisation",
  "data": {
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "expires_in": 86400
  }
}
```

**Paramètres:**
- `expires_in`: Token valide pendant 24 heures (86400 secondes)
- `token`: Token sécurisé à stocker et à utiliser à l'étape 2

---

### 2️⃣ POST `/api/auth/reset-password`
**Réinitialiser le mot de passe (Étape 2)**

**Request:**
```json
{
  "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
  "password": "MonNouveauMotDePasse123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Mot de passe réinitialisé avec succès",
  "data": {
    "user": {
      "id_user": 1,
      "first_name": "Jean",
      "last_name": "Dupont",
      "phone": "237677123456",
      "email": "jean@example.com"
    }
  }
}
```

---

## 🎯 Flux Frontend

### Vue React/Vue/Flutter

```jsx
// 1️⃣ ÉCRAN 1: Demander le code
function ForgotPasswordScreen() {
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);
  const [step, setStep] = useState('phone'); // 'phone' ou 'reset'
  const [token, setToken] = useState('');
  const [error, setError] = useState('');

  const handleRequestCode = async () => {
    setLoading(true);
    setError('');

    try {
      const response = await fetch('https://votre-api.com/api/auth/forgot-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ phone })
      });

      const data = await response.json();

      if (data.success) {
        // Sauvegarder le token
        setToken(data.data.token);
        // Passer à l'écran de réinitialisation
        setStep('reset');
      } else {
        setError('Erreur: ' + data.message);
      }
    } catch (err) {
      setError('Erreur réseau: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  if (step === 'phone') {
    return (
      <div>
        <h1>Réinitialiser mon mot de passe</h1>
        
        <input
          type="tel"
          placeholder="237677123456"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
        />

        <button 
          onClick={handleRequestCode}
          disabled={loading || phone.length < 9}
        >
          {loading ? 'Chargement...' : 'Envoyer le code'}
        </button>

        {error && <p style={{ color: 'red' }}>{error}</p>}
      </div>
    );
  }

  // Retourner le composant de réinitialisation
  return <ResetPasswordScreen token={token} phone={phone} />;
}

// 2️⃣ ÉCRAN 2: Entrer le code et nouveau mot de passe
function ResetPasswordScreen({ token, phone }) {
  const [code, setCode] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleResetPassword = async () => {
    setError('');

    // Validation côté client
    if (newPassword !== confirmPassword) {
      setError('Les mots de passe ne correspondent pas');
      return;
    }

    if (newPassword.length < 6) {
      setError('Le mot de passe doit avoir au moins 6 caractères');
      return;
    }

    setLoading(true);

    try {
      const response = await fetch('https://votre-api.com/api/auth/reset-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          token: token,
          password: newPassword
        })
      });

      const data = await response.json();

      if (data.success) {
        setSuccess(true);
        // Rediriger vers login après 2 secondes
        setTimeout(() => {
          window.location.href = '/login';
        }, 2000);
      } else {
        setError('Erreur: ' + data.message);
      }
    } catch (err) {
      setError('Erreur réseau: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div style={{ textAlign: 'center', padding: '40px' }}>
        <h2>✅ Mot de passe réinitialisé!</h2>
        <p>Vous allez être redirigé vers la connexion...</p>
      </div>
    );
  }

  return (
    <div>
      <h2>Vérifier votre téléphone et réinitialiser</h2>
      <p>Un code a été envoyé à {phone}</p>

      <div>
        <label>Code de réinitialisation (6 chiffres)</label>
        <input
          type="text"
          maxLength="6"
          placeholder="000000"
          value={code}
          onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))}
        />
      </div>

      <div>
        <label>Nouveau mot de passe</label>
        <input
          type="password"
          placeholder="Minimum 6 caractères"
          value={newPassword}
          onChange={(e) => setNewPassword(e.target.value)}
        />
      </div>

      <div>
        <label>Confirmer mot de passe</label>
        <input
          type="password"
          placeholder="Confirmer"
          value={confirmPassword}
          onChange={(e) => setConfirmPassword(e.target.value)}
        />
      </div>

      <button 
        onClick={handleResetPassword}
        disabled={loading || newPassword.length < 6}
      >
        {loading ? 'Réinitialisation...' : 'Réinitialiser'}
      </button>

      {error && <p style={{ color: 'red' }}>{error}</p>}
    </div>
  );
}
```

---

## 📱 Exemple avec Flutter/Dart

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class PasswordResetService {
  static const String API_BASE = 'https://votre-api.com/api';

  // Étape 1: Demander le code
  static Future<Map<String, dynamic>> requestPasswordReset(String phone) async {
    try {
      final response = await http.post(
        Uri.parse('$API_BASE/auth/forgot-password'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'phone': phone}),
      );

      final data = jsonDecode(response.body);

      if (data['success']) {
        return {
          'success': true,
          'token': data['data']['token'],
          'expires_in': data['data']['expires_in'],
        };
      } else {
        return {
          'success': false,
          'error': data['message'],
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': 'Erreur réseau: $e',
      };
    }
  }

  // Étape 2: Réinitialiser le mot de passe
  static Future<Map<String, dynamic>> resetPassword(
    String token,
    String newPassword,
  ) async {
    try {
      final response = await http.post(
        Uri.parse('$API_BASE/auth/reset-password'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'token': token,
          'password': newPassword,
        }),
      );

      final data = jsonDecode(response.body);

      if (data['success']) {
        return {
          'success': true,
          'message': data['message'],
          'user': data['data']['user'],
        };
      } else {
        return {
          'success': false,
          'error': data['message'],
        };
      }
    } catch (e) {
      return {
        'success': false,
        'error': 'Erreur réseau: $e',
      };
    }
  }
}

// Utilisation
class ForgotPasswordPage extends StatefulWidget {
  @override
  _ForgotPasswordPageState createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends State<ForgotPasswordPage> {
  final phoneController = TextEditingController();
  final passwordController = TextEditingController();
  String _token = '';
  bool _showPasswordForm = false;
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    if (!_showPasswordForm) {
      return _buildPhoneForm();
    }
    return _buildPasswordForm();
  }

  Widget _buildPhoneForm() {
    return Scaffold(
      appBar: AppBar(title: Text('Réinitialiser mot de passe')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: phoneController,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                labelText: 'Numéro de téléphone',
                hintText: '237677123456',
              ),
            ),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _loading ? null : _requestCode,
              child: Text(_loading ? 'Chargement...' : 'Envoyer le code'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPasswordForm() {
    return Scaffold(
      appBar: AppBar(title: Text('Vérifier et réinitialiser')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Un code a été envoyé à ${phoneController.text}'),
            SizedBox(height: 20),
            TextField(
              controller: passwordController,
              obscureText: true,
              decoration: InputDecoration(
                labelText: 'Nouveau mot de passe',
                hintText: 'Minimum 6 caractères',
              ),
            ),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _loading ? null : _resetPassword,
              child: Text(_loading ? 'Réinitialisation...' : 'Réinitialiser'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _requestCode() async {
    setState(() => _loading = true);

    final result = await PasswordResetService.requestPasswordReset(
      phoneController.text,
    );

    if (result['success']) {
      _token = result['token'];
      setState(() => _showPasswordForm = true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Code envoyé! Vérifiez votre téléphone')),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: ${result['error']}')),
      );
    }

    setState(() => _loading = false);
  }

  Future<void> _resetPassword() async {
    setState(() => _loading = true);

    final result = await PasswordResetService.resetPassword(
      _token,
      passwordController.text,
    );

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('✅ Mot de passe réinitialisé!')),
      );
      // Rediriger vers login
      Navigator.of(context).pushReplacementNamed('/login');
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: ${result['error']}')),
      );
    }

    setState(() => _loading = false);
  }

  @override
  void dispose() {
    phoneController.dispose();
    passwordController.dispose();
    super.dispose();
  }
}
```

---

## 🔒 Points de sécurité

### Backend (déjà implémenté):
✅ Token long et sécurisé (hex 64 caractères)
✅ Expiration 24h
✅ Hash du mot de passe avec PASSWORD_DEFAULT
✅ Message générique si utilisateur non trouvé
✅ Nettoyage automatique des tokens expirés

### Frontend (à implémenter):
✅ Valider le mot de passe (min 6 caractères)
✅ Confirmation du mot de passe
✅ HTTPS seulement en production
✅ Ne pas logger le token dans la console
✅ Supprimer le token après utilisation
✅ Timeout d'inactivité

---

## 🧪 Test avec cURL

### Étape 1: Demander le code
```bash
curl -X POST https://votre-api.com/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"phone": "237677123456"}'
```

**Réponse:**
```json
{
  "success": true,
  "message": "Si ce numéro de téléphone existe dans notre système, vous recevrez un code de réinitialisation",
  "data": {
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "expires_in": 86400
  }
}
```

### Étape 2: Réinitialiser le mot de passe
```bash
curl -X POST https://votre-api.com/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "password": "MonNouveauMotDePasse123"
  }'
```

**Réponse:**
```json
{
  "success": true,
  "message": "Mot de passe réinitialisé avec succès",
  "data": {
    "user": {
      "id_user": 1,
      "first_name": "Jean",
      "phone": "237677123456",
      "email": "jean@example.com"
    }
  }
}
```

---

## ⚠️ Erreurs courantes

### Erreur: "Code de réinitialisation invalide ou expiré"
- Le token a expiré (> 24h)
- Demander un nouveau code

### Erreur: "Token et nouveau mot de passe requis"
- Vérifier que vous envoyez les deux champs
- Vérifier la syntaxe JSON

### Erreur: "Le mot de passe doit contenir au moins 6 caractères"
- Augmenter la longueur du mot de passe

---

## 📊 Base de données (colonnes dans `users`)

```sql
ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL;
ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL;
```

Ces colonnes sont **automatiquement créées** par la migration.

---

## 🎨 UI/UX recommandé

```
┌─────────────────────────────────┐
│ Réinitialiser mon mot de passe  │
├─────────────────────────────────┤
│                                 │
│ Entrez votre numéro de         │
│ téléphone associé à votre      │
│ compte Cambizzle               │
│                                 │
│ ┌────────────────────────────┐ │
│ │ 237677123456               │ │
│ └────────────────────────────┘ │
│                                 │
│ [ Envoyer le code ]            │
│                                 │
│ Vous recevrez un code à 6      │
│ chiffres par SMS               │
│                                 │
└─────────────────────────────────┘

         ↓ (succès)

┌─────────────────────────────────┐
│ Vérifier et réinitialiser       │
├─────────────────────────────────┤
│ Un code a été envoyé à          │
│ 237677123456                    │
│                                 │
│ Code (6 chiffres)               │
│ ┌────────────────────────────┐ │
│ │ 000000                     │ │
│ └────────────────────────────┘ │
│                                 │
│ Nouveau mot de passe            │
│ ┌────────────────────────────┐ │
│ │ ••••••••••••••••           │ │
│ └────────────────────────────┘ │
│                                 │
│ Confirmer mot de passe          │
│ ┌────────────────────────────┐ │
│ │ ••••••••••••••••           │ │
│ └────────────────────────────┘ │
│                                 │
│ [ Réinitialiser ]              │
│                                 │
└─────────────────────────────────┘

         ↓ (succès)

┌─────────────────────────────────┐
│ ✅ Mot de passe réinitialisé!  │
├─────────────────────────────────┤
│                                 │
│ Redirection vers connexion...   │
│                                 │
└─────────────────────────────────┘
```

---

## 📝 Checklist d'intégration

- [ ] Créer l'écran "Mot de passe oublié"
- [ ] Implémenter l'appel POST `/forgot-password`
- [ ] Afficher l'écran de vérification
- [ ] Implémenter l'appel POST `/reset-password`
- [ ] Ajouter la validation côté client
- [ ] Ajouter la gestion des erreurs
- [ ] Tester avec des cas réels
- [ ] Ajouter des messages d'erreur clairs
- [ ] Protéger le formulaire (HTTPS, etc.)
- [ ] Ajouter un lien "Retour à la connexion"
- [ ] Tester la gestion du timeout (24h)
- [ ] Ajouter un bouton "Renvoyer le code"

---

## 🔗 Routes associées

```
POST /api/auth/forgot-password    → Demander le code
POST /api/auth/reset-password     → Réinitialiser le mot de passe
POST /api/auth/login              → Se connecter (après reset)
```

C'est tout! Vous êtes prêt à intégrer le password reset au frontend! 🚀
