# 🚨 DÉPANNAGE ERREUR 500 - CAMBIZZLE API

## 🔥 PROBLÈME : Internal Server Error (500)

### 🎯 SOLUTIONS RAPIDES

#### 1. **Test Diagnostic Immédiat**
**URL :** `http://www.cambizzle.seed-innov.com/api/debug_500.php`

Ce script va identifier **exactement** le problème :
- ✅ Versions PHP et extensions
- ✅ Fichiers manquants
- ✅ Chemins incorrects
- ✅ Permissions

#### 2. **Corriger les problèmes identifiés**

##### ❌ **Si "system/Boot.php MISSING"**
```
Cause : Chemins incorrects dans Paths.php
Solution : Téléchargez le nouveau dossier deploy/ et uploadez-le entièrement
```

##### ❌ **Si ".env MISSING"**
```
Cause : Fichier de configuration absent
Solution :
1. Ouvrez deploy/api/env_template.txt
2. Remplacez les valeurs DB par les vôtres
3. Sauvegardez sous .env dans www/api/
```

##### ❌ **Si "systemDirectory" inexistant**
```
Cause : Dossier system/ manquant
Solution : Uploadez TOUS les dossiers du deploy/api/
```

---

## 🔧 SOLUTIONS DÉTAILLÉES

### **Étape 1 : Diagnostic complet**
1. Allez sur `debug_500.php`
2. **Notez tous les éléments marqués ❌**
3. Corrigez-les un par un

### **Étape 2 : Vérification des fichiers critiques**

#### Fichiers qui DOIVENT être présents :
```
www/api/
├── .env (créez-le depuis env_template.txt)
├── .htaccess (règles de réécriture)
├── system/ (dossier complet)
├── vendor/ (dossier complet)
├── app/ (dossier complet)
├── writable/ (dossier complet)
└── public/ (dossier complet)
```

### **Étape 3 : Configuration .env**

Créez `.env` avec ce contenu minimum :
```env
CI_ENVIRONMENT = production
app.baseURL = 'http://www.cambizzle.seed-innov.com/api/'
app.indexPage = ''

# Remplacez par VOS vraies valeurs DB
database.default.hostname = localhost
database.default.database = votre_db_name
database.default.username = votre_db_user
database.default.password = votre_db_password
database.default.DBDriver = MySQLi
database.default.port = 3306

# Clés par défaut (non sécurisé)
encryption.key = hex2bin:6137636636613763663661333763666636613366636636636661
JWT_SECRET_KEY = cambizzle_default_jwt_key_for_shared_hosting_2025

# CORS ouvert pour tests
cors.allowedOrigins = *
cors.allowedHeaders = *
cors.allowedMethods = *
cors.allowCredentials = false
```

### **Étape 4 : Permissions des dossiers**

Sur votre serveur LWS, définissez :
```bash
chmod -R 755 www/api/writable/
chmod -R 755 www/api/public/uploads/
```

---

## 🎯 TESTS APRÈS CORRECTION

### 1. **Test PHP simple**
`http://www.cambizzle.seed-innov.com/api/test_routes.php`
- Doit retourner du JSON ✅

### 2. **Test diagnostic**
`http://www.cambizzle.seed-innov.com/api/debug_500.php`
- Tout doit être vert ✅

### 3. **Test route API**
`http://www.cambizzle.seed-innov.com/api/ads/creation-data`
- Doit retourner les données JSON ✅

---

## 🔍 CAUSES COURANTES D'ERREUR 500

### **1. Dossiers manquants**
- Symptôme : "system/Boot.php MISSING"
- Solution : Uploadez le dossier `deploy/api/` complet

### **2. Chemins incorrects**
- Symptôme : "systemDirectory inexistant"
- Solution : Utilisez le Paths.php du dossier deploy/

### **3. Fichier .env manquant**
- Symptôme : Erreur de configuration DB
- Solution : Créez .env depuis env_template.txt

### **4. Permissions insuffisantes**
- Symptôme : Erreur d'écriture dans writable/
- Solution : chmod -R 755 writable/

### **5. Extensions PHP manquantes**
- Symptôme : "mysqli MISSING"
- Solution : Contactez support LWS pour activer l'extension

---

## 📞 SI ÇA NE MARCHE TOUJOURS PAS

1. **Téléchargez** le nouveau dossier `deploy/` complet
2. **Supprimez** l'ancien dossier `www/api/`
3. **Uploadez** le nouveau `deploy/api/` vers `www/api/`
4. **Créez** le fichier `.env` depuis `env_template.txt`
5. **Testez** `debug_500.php`

---

## 🎯 RÉSULTAT ATTENDU APRÈS CORRECTION

```
✅ PHP 8.1+
✅ Extensions PHP (mysqli, json, mbstring)
✅ Tous les fichiers présents
✅ Tous les chemins corrects
✅ Permissions OK
✅ .env configuré
```

**🚀 Ensuite, vos routes API fonctionneront !**













