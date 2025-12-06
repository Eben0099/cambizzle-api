# GUIDE DE DÉPLOIEMENT - CAMBIZZLE COMPLET
## Déploiement API + Frontend React sur LWS Panel (www.cambizzle.seed-innov.com)

### ARCHITECTURE DE DÉPLOIEMENT :
```
www.cambizzle.seed-innov.com/
├── / (Frontend React - pages principales)
├── /api/ (API CodeIgniter 4)
└── /uploads/ (Fichiers uploadés)
```

### ÉTAPES DE DÉPLOIEMENT :

#### 1. Préparation des fichiers ✅
- ✅ Fichier .env.production créé (à renommer en .env sur le serveur)
- ✅ .htaccess de sécurité créé pour la racine
- ✅ .htaccess public corrigé pour CodeIgniter 4 avec CORS
- ✅ Script de génération de clés sécurisées créé
- ✅ Configuration App.php mise à jour pour la production
- ✅ Script de vérification du déploiement créé
- ✅ Script de nettoyage pour la production créé

#### 2. Structure recommandée sur le serveur LWS :
```
www/
├── .htaccess (routage principal)
├── index.html (page d'accueil React)
├── static/ (assets React compilés)
├── favicon.ico
├── robots.txt
├── api/
│   ├── .htaccess (redirection vers CodeIgniter)
│   ├── .env
│   ├── app/
│   ├── system/
│   ├── vendor/
│   ├── writable/
│   └── public/
│       ├── .htaccess (configuration CodeIgniter + CORS)
│       ├── index.php
│       └── uploads/ (accessible via /api/uploads/)
└── uploads/ -> api/public/uploads/ (lien symbolique)
```

#### 3. Configuration .ENV mise à jour pour API en sous-dossier :
```env
CI_ENVIRONMENT = production
app.baseURL = 'http://www.cambizzle.seed-innov.com/api/'
app.indexPage = ''

# Base de données LWS
database.default.hostname = localhost
database.default.database = VOTRE_DB_NAME_LWS
database.default.username = VOTRE_DB_USER_LWS
database.default.password = VOTRE_DB_PASSWORD_LWS
database.default.DBDriver = MySQLi
database.default.port = 3306

# Clés de sécurité (à générer avec le script)
encryption.key = hex2bin:VOTRE_CLE_ENCRYPTION
JWT_SECRET_KEY = VOTRE_CLE_JWT_SECURISEE
JWT_TIME_TO_LIVE = 3600

# CORS - URLs Frontend et API
cors.allowedOrigins = http://www.cambizzle.seed-innov.com,https://www.cambizzle.seed-innov.com
cors.allowedHeaders = Content-Type,Authorization,X-Requested-With
cors.allowedMethods = GET,POST,PUT,DELETE,OPTIONS
cors.allowCredentials = true
```

#### 4. Upload et organisation des fichiers
1. **Frontend React :**
   - Compilez votre app React (`npm run build`)
   - Uploadez le contenu du dossier `build/` à la racine de `www/`

2. **API CodeIgniter :**
   - Créez un dossier `api/` dans `www/`
   - Uploadez tous les fichiers de l'API dans `www/api/`
   - Déplacez le contenu de `api/public/` vers `www/api/`

#### 5. Configuration de la base de données
1. Créez une base de données MySQL dans votre panel LWS
2. Notez les identifiants et mettez à jour le fichier `.env`
3. Importez votre fichier `database_setup.sql`

#### 6. Génération des clés de sécurité
1. Exécutez : `php api/generate_production_keys.php`
2. Copiez les clés dans `api/.env`
3. Supprimez le script après utilisation

#### 7. Configuration des permissions
```bash
chmod -R 755 api/writable/
chmod -R 755 api/uploads/
```

#### 8. Vérification du déploiement
- Frontend : http://www.cambizzle.seed-innov.com
- API : http://www.cambizzle.seed-innov.com/api/
- Check : http://www.cambizzle.seed-innov.com/api/deployment_check.php

### CONFIGURATION FRONTEND REACT :
Votre frontend doit pointer vers `/api/` pour les appels API :
```javascript
// Dans votre config React
const API_BASE_URL = process.env.NODE_ENV === 'production' 
  ? 'http://www.cambizzle.seed-innov.com/api'
  : 'http://localhost:8080';
```

### SÉCURITÉ IMPORTANTE :
- ✅ API isolée dans `/api/`
- ✅ Fichiers .env protégés
- ✅ CORS configuré entre frontend et API
- ✅ Uploads accessibles mais sécurisés

### PROCHAINES ÉTAPES :
1. Compilez votre frontend React
2. Compressez API et Frontend séparément
3. Uploadez sur LWS Panel selon la structure
4. Configurez la base de données
5. Testez frontend et API séparément
6. Testez l'intégration complète
