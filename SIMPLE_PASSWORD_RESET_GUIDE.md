# Password Reset Simplifié - Guide Frontend

## 🎯 Flux unique en UNE SEULE ÉTAPE

```
┌─────────────────────────────────────────┐
│   Réinitialiser mon mot de passe        │
├─────────────────────────────────────────┤
│                                         │
│  Numéro de téléphone                    │
│  ┌───────────────────────────────────┐ │
│  │ 237677123456                      │ │
│  └───────────────────────────────────┘ │
│                                         │
│  Nouveau mot de passe                   │
│  ┌───────────────────────────────────┐ │
│  │ •••••••••••••                     │ │
│  └───────────────────────────────────┘ │
│                                         │
│  Confirmer mot de passe                 │
│  ┌───────────────────────────────────┐ │
│  │ •••••••••••••                     │ │
│  └───────────────────────────────────┘ │
│                                         │
│  [ Réinitialiser ]                      │
│                                         │
└─────────────────────────────────────────┘
```

---

## 🔌 Endpoint API

### POST `/api/auth/reset-password`

**Request:**
```json
{
  "phone": "237677123456",
  "password": "MonNouveauMotDePasse123"
}
```

**Response (Succès):**
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

**Response (Erreur):**
```json
{
  "success": false,
  "message": "Aucun compte trouvé avec ce numéro de téléphone",
  "code": "RESET_FAILED"
}
```

---

## 📱 Code Frontend - React

```jsx
import { useState } from 'react';

export function ResetPasswordForm() {
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');

    // Validation côté client
    if (!phone || !password || !confirmPassword) {
      setError('Tous les champs sont requis');
      return;
    }

    if (password.length < 6) {
      setError('Le mot de passe doit avoir au moins 6 caractères');
      return;
    }

    if (password !== confirmPassword) {
      setError('Les mots de passe ne correspondent pas');
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
          phone: phone,
          password: password
        })
      });

      const data = await response.json();

      if (data.success) {
        setSuccess(true);
        // Rediriger après 2 secondes
        setTimeout(() => {
          window.location.href = '/login';
        }, 2000);
      } else {
        setError(data.message || 'Erreur lors de la réinitialisation');
      }
    } catch (err) {
      setError('Erreur réseau: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="success-message">
        <h2>✅ Mot de passe réinitialisé avec succès!</h2>
        <p>Redirection vers la connexion...</p>
      </div>
    );
  }

  return (
    <div className="reset-password-container">
      <h1>Réinitialiser mon mot de passe</h1>

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="phone">Numéro de téléphone</label>
          <input
            id="phone"
            type="tel"
            placeholder="237677123456"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            disabled={loading}
          />
        </div>

        <div className="form-group">
          <label htmlFor="password">Nouveau mot de passe</label>
          <input
            id="password"
            type="password"
            placeholder="Minimum 6 caractères"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            disabled={loading}
          />
        </div>

        <div className="form-group">
          <label htmlFor="confirmPassword">Confirmer mot de passe</label>
          <input
            id="confirmPassword"
            type="password"
            placeholder="Confirmer"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            disabled={loading}
          />
        </div>

        {error && <div className="error-message">{error}</div>}

        <button 
          type="submit"
          disabled={loading || !phone || !password || !confirmPassword}
        >
          {loading ? 'Réinitialisation...' : 'Réinitialiser'}
        </button>
      </form>

      <a href="/login">Retour à la connexion</a>
    </div>
  );
}
```

---

## 📱 Code Frontend - Vue.js

```vue
<template>
  <div class="reset-password-container">
    <h1>Réinitialiser mon mot de passe</h1>

    <div v-if="success" class="success-message">
      <h2>✅ Mot de passe réinitialisé avec succès!</h2>
      <p>Redirection vers la connexion...</p>
    </div>

    <form v-else @submit.prevent="handleSubmit">
      <div class="form-group">
        <label for="phone">Numéro de téléphone</label>
        <input
          id="phone"
          v-model="phone"
          type="tel"
          placeholder="237677123456"
          :disabled="loading"
        />
      </div>

      <div class="form-group">
        <label for="password">Nouveau mot de passe</label>
        <input
          id="password"
          v-model="password"
          type="password"
          placeholder="Minimum 6 caractères"
          :disabled="loading"
        />
      </div>

      <div class="form-group">
        <label for="confirmPassword">Confirmer mot de passe</label>
        <input
          id="confirmPassword"
          v-model="confirmPassword"
          type="password"
          placeholder="Confirmer"
          :disabled="loading"
        />
      </div>

      <div v-if="error" class="error-message">{{ error }}</div>

      <button 
        type="submit"
        :disabled="loading || !phone || !password || !confirmPassword"
      >
        {{ loading ? 'Réinitialisation...' : 'Réinitialiser' }}
      </button>
    </form>

    <a href="/login">Retour à la connexion</a>
  </div>
</template>

<script>
export default {
  data() {
    return {
      phone: '',
      password: '',
      confirmPassword: '',
      loading: false,
      error: '',
      success: false
    };
  },
  methods: {
    async handleSubmit() {
      this.error = '';

      // Validation
      if (!this.phone || !this.password || !this.confirmPassword) {
        this.error = 'Tous les champs sont requis';
        return;
      }

      if (this.password.length < 6) {
        this.error = 'Le mot de passe doit avoir au moins 6 caractères';
        return;
      }

      if (this.password !== this.confirmPassword) {
        this.error = 'Les mots de passe ne correspondent pas';
        return;
      }

      this.loading = true;

      try {
        const response = await fetch('https://votre-api.com/api/auth/reset-password', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            phone: this.phone,
            password: this.password
          })
        });

        const data = await response.json();

        if (data.success) {
          this.success = true;
          setTimeout(() => {
            window.location.href = '/login';
          }, 2000);
        } else {
          this.error = data.message || 'Erreur lors de la réinitialisation';
        }
      } catch (err) {
        this.error = 'Erreur réseau: ' + err.message;
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.reset-password-container {
  max-width: 400px;
  margin: 50px auto;
  padding: 30px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
}

h1 {
  text-align: center;
  margin-bottom: 30px;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: bold;
  color: #333;
}

.form-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
}

.form-group input:disabled {
  background-color: #f5f5f5;
  cursor: not-allowed;
}

.error-message {
  color: #d32f2f;
  padding: 12px;
  margin-bottom: 20px;
  background-color: #ffebee;
  border-radius: 4px;
}

.success-message {
  padding: 20px;
  background-color: #c8e6c9;
  border-radius: 4px;
  text-align: center;
}

.success-message h2 {
  color: #2e7d32;
  margin: 0;
}

button {
  width: 100%;
  padding: 12px;
  background-color: #1976d2;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: background-color 0.3s;
}

button:hover:not(:disabled) {
  background-color: #1565c0;
}

button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

a {
  display: block;
  text-align: center;
  margin-top: 20px;
  color: #1976d2;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}
</style>
```

---

## 📱 Code Frontend - Flutter/Dart

```dart
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class ResetPasswordPage extends StatefulWidget {
  @override
  _ResetPasswordPageState createState() => _ResetPasswordPageState();
}

class _ResetPasswordPageState extends State<ResetPasswordPage> {
  final phoneController = TextEditingController();
  final passwordController = TextEditingController();
  final confirmPasswordController = TextEditingController();
  
  bool _loading = false;
  String _error = '';
  bool _showPassword = false;
  bool _showConfirmPassword = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Réinitialiser mot de passe'),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Réinitialiser mon mot de passe',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey[800],
                ),
              ),
              SizedBox(height: 8),
              Text(
                'Entrez votre numéro de téléphone et votre nouveau mot de passe',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey[600],
                ),
              ),
              SizedBox(height: 30),
              
              // Téléphone
              TextField(
                controller: phoneController,
                keyboardType: TextInputType.phone,
                enabled: !_loading,
                decoration: InputDecoration(
                  labelText: 'Numéro de téléphone',
                  hintText: '237677123456',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.phone),
                ),
              ),
              SizedBox(height: 16),

              // Mot de passe
              TextField(
                controller: passwordController,
                obscureText: !_showPassword,
                enabled: !_loading,
                decoration: InputDecoration(
                  labelText: 'Nouveau mot de passe',
                  hintText: 'Minimum 6 caractères',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.lock),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _showPassword ? Icons.visibility : Icons.visibility_off,
                    ),
                    onPressed: () {
                      setState(() => _showPassword = !_showPassword);
                    },
                  ),
                ),
              ),
              SizedBox(height: 16),

              // Confirmer mot de passe
              TextField(
                controller: confirmPasswordController,
                obscureText: !_showConfirmPassword,
                enabled: !_loading,
                decoration: InputDecoration(
                  labelText: 'Confirmer mot de passe',
                  hintText: 'Confirmer',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.lock),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _showConfirmPassword ? Icons.visibility : Icons.visibility_off,
                    ),
                    onPressed: () {
                      setState(() => _showConfirmPassword = !_showConfirmPassword);
                    },
                  ),
                ),
              ),
              SizedBox(height: 20),

              // Message d'erreur
              if (_error.isNotEmpty)
                Container(
                  padding: EdgeInsets.all(12),
                  background: Color(0xffebee),
                  borderRadius: BorderRadius.circular(4),
                  child: Text(
                    _error,
                    style: TextStyle(color: Color(0xffd32f2f)),
                  ),
                ),
              SizedBox(height: 20),

              // Bouton
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _loading ? null : _handleResetPassword,
                  child: Padding(
                    padding: EdgeInsets.symmetric(vertical: 12),
                    child: Text(
                      _loading ? 'Réinitialisation...' : 'Réinitialiser',
                      style: TextStyle(fontSize: 16),
                    ),
                  ),
                ),
              ),

              SizedBox(height: 16),

              // Lien retour connexion
              Center(
                child: TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: Text('Retour à la connexion'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _handleResetPassword() async {
    setState(() => _error = '');

    // Validation
    if (phoneController.text.isEmpty ||
        passwordController.text.isEmpty ||
        confirmPasswordController.text.isEmpty) {
      setState(() => _error = 'Tous les champs sont requis');
      return;
    }

    if (passwordController.text.length < 6) {
      setState(() => _error = 'Le mot de passe doit avoir au moins 6 caractères');
      return;
    }

    if (passwordController.text != confirmPasswordController.text) {
      setState(() => _error = 'Les mots de passe ne correspondent pas');
      return;
    }

    setState(() => _loading = true);

    try {
      final response = await http.post(
        Uri.parse('https://votre-api.com/api/auth/reset-password'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'phone': phoneController.text,
          'password': passwordController.text,
        }),
      );

      final data = jsonDecode(response.body);

      if (data['success']) {
        // Succès
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('✅ Mot de passe réinitialisé!')),
        );

        // Rediriger vers login
        Future.delayed(Duration(seconds: 1), () {
          Navigator.of(context).pushReplacementNamed('/login');
        });
      } else {
        setState(() => _error = data['message'] ?? 'Erreur');
      }
    } catch (e) {
      setState(() => _error = 'Erreur réseau: $e');
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    phoneController.dispose();
    passwordController.dispose();
    confirmPasswordController.dispose();
    super.dispose();
  }
}
```

---

## 🧪 Test avec cURL

```bash
curl -X POST https://votre-api.com/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "237677123456",
    "password": "MonNouveauMotDePasse123"
  }'
```

---

## ✅ Checklist d'intégration

- [ ] Créer la page/écran Reset Password
- [ ] Ajouter les 3 champs (téléphone, mot de passe, confirmation)
- [ ] Implémenter la validation côté client
- [ ] Implémenter l'appel POST à l'API
- [ ] Gérer les erreurs et afficher les messages
- [ ] Ajouter un loader pendant la requête
- [ ] Rediriger vers login après succès
- [ ] Ajouter un bouton "Retour à la connexion"
- [ ] Tester avec des cas réels
- [ ] Ajouter du styling personnalisé
- [ ] Tester sur mobile

---

## 🔒 Sécurité

✅ Validation du mot de passe (min 6 caractères)
✅ Confirmation du mot de passe obligatoire
✅ Utilisation de HTTPS seulement
✅ Pas de logs sensibles côté client
✅ Hash du mot de passe au backend

C'est tout! C'est beaucoup plus simple et direct! 🚀
