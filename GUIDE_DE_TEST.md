# 🔍 GUIDE COMPLET DE TEST - API CAMBIZZLE

## 🎯 TESTS RAPIDES (Recommandés)

### 1. 📊 Test Automatique Local
```bash
# Dans le dossier api/
php test_local.php
```
**Résultat attendu :** Tous les ✅ verts, sauf systemDirectory qui est normal en local

### 2. 🖥️ Test Serveur Local
```bash
# Démarrer le serveur de développement
php -S localhost:8080 -t public/

# Dans un autre terminal, tester :
curl http://localhost:8080/ads/creation-data
curl http://localhost:8080/check_api.php
```

---

## 🌐 TESTS SUR LE SERVEUR (Après déploiement)

### 1. 📋 Test Diagnostic Complet
**URL :** `http://www.cambizzle.seed-innov.com/api/check_api.php`

**Résultat attendu :**
- ✅ Version PHP 8.1+
- ✅ Extensions PHP (mysqli, json, etc.)
- ✅ Tous les dossiers présents
- ✅ Permissions d'écriture OK
- ✅ Chemins configurés correctement

### 2. 🔗 Test Route Spécifique
**URL :** `http://www.cambizzle.seed-innov.com/api/ads/creation-data`

**Résultat attendu :**
```json
{
    "status": "success",
    "data": {
        "categories": [...],
        "locations": [...],
        "brands": [...]
    }
}
```

### 3. 🧪 Test Simple (sans CodeIgniter)
**URL :** `http://www.cambizzle.seed-innov.com/api/test_routes.php`

**Résultat attendu :** JSON de confirmation que PHP fonctionne

---

## 🔧 TESTS AVANCÉS

### Test avec Postman/Insomnia
```http
GET http://www.cambizzle.seed-innov.com/api/ads/creation-data
Headers:
  Content-Type: application/json
```

### Test avec curl
```bash
# Test simple
curl -X GET "http://www.cambizzle.seed-innov.com/api/ads/creation-data"

# Test avec headers
curl -X GET "http://www.cambizzle.seed-innov.com/api/ads/creation-data" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"
```

---

## 📊 INTERPRÉTATION DES RÉSULTATS

### ✅ TESTS RÉUSSIS
- **check_api.php** : Tous les éléments en vert
- **Route API** : Retourne du JSON valide avec `status: "success"`
- **test_routes.php** : Retourne du JSON (PHP fonctionne)

### ❌ PROBLÈMES COURANTS

#### Erreur 404
```
Vérifier :
□ Tous les dossiers uploadés (system/, vendor/, app/)
□ Fichier .htaccess présent dans www/api/
□ Fichier .env présent
□ Permissions des dossiers
```

#### Erreur 500
```
Vérifier :
□ Version PHP 8.1+
□ Extensions PHP installées
□ Fichier .env avec bonnes valeurs DB
□ Base de données accessible
```

#### Erreur de base de données
```
Vérifier :
□ Identifiants dans .env corrects
□ Base de données existe
□ Table database_setup.sql importée
```

---

## 🎯 ORDRE DE TEST RECOMMANDÉ

### Sur le serveur (après déploiement) :
1. **check_api.php** → Diagnostique complet
2. **test_routes.php** → Vérifie que PHP fonctionne
3. **Route spécifique** → Teste CodeIgniter et les routes

### En local (avant déploiement) :
1. **test_local.php** → Vérification rapide
2. **Serveur local** → Tests fonctionnels complets

---

## 📞 SUPPORT

Si un test échoue :
1. **Notez l'erreur exacte**
2. **Vérifiez check_api.php** pour le diagnostic
3. **Comparez avec les résultats attendus** ci-dessus

**Les logs d'erreur PHP** sont souvent dans `/logs/` ou dans le panel d'administration LWS.













