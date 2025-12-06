# 🚀 Configuration Base de Données Cambizzle

## 📋 Fichiers Disponibles

- `setup_database_simple.php` - Script principal de configuration
- `database_config.php` - Configuration MySQL
- `setup_database_simple.bat` - Lanceur Windows

## ⚡ Utilisation Rapide

### 1. Configuration (si nécessaire)
```php
// Éditez database_config.php
return [
    'host' => 'localhost',      // Votre serveur MySQL
    'database' => 'cambizzle-api',  // Nom de la base
    'username' => 'root',       // Utilisateur MySQL
    'password' => '',           // Mot de passe (vide si aucun)
];
```

### 2. Exécution
```bash
# Double-cliquez sur :
setup_database_simple.bat

# Ou exécutez directement :
php setup_database_simple.php
```

### 3. Vérification
Après exécution, vous devriez voir :
```
✅ Toutes les tables ont été créées avec succès !
🎉 Configuration de la base de données terminée avec succès !
```

## 📊 Que fait le script ?

### ✅ Ajoute à la table `users` :
- `is_suspended` (TINYINT) - Statut de suspension
- `suspended_at` (DATETIME) - Date de suspension
- `suspended_by` (INT) - Admin qui a suspendu
- `suspension_reason` (TEXT) - Raison de suspension
- `unsuspended_at` (DATETIME) - Date de réactivation
- `unsuspended_by` (INT) - Admin qui a réactivé

### ✅ Crée la table `promotion_packs` :
- Packs promotionnels avec prix, durée, fonctionnalités
- **Données de test incluses** :
  - Pack Premium 30 Jours (24.99€)
  - Pack Essentiel 7 Jours (9.99€)

### ✅ Crée la table `moderation_logs` :
- Traçabilité complète de toutes les actions admin
- Logs d'approbation/rejet d'annonces
- Logs de suspension/réactivation d'utilisateurs

### ✅ Ajoute les indexes et contraintes :
- Clés étrangères pour l'intégrité
- Indexes pour les performances

## 🔍 Dépannage

### Erreur "Base de données inconnue"
- Créez la base `cambizzle-api` dans phpMyAdmin/MySQL Workbench

### Erreur "Accès refusé"
- Vérifiez les identifiants dans `database_config.php`
- Assurez-vous que l'utilisateur a les droits CREATE/ALTER

### Erreur "Connexion impossible"
- Vérifiez que MySQL est démarré
- Vérifiez l'adresse IP/port du serveur

### Script indique "Élément déjà existant"
- C'est normal ! Le script vérifie avant de créer
- Vous pouvez le relancer sans risque

## 🧪 Test après installation

### 1. Démarrer le serveur
```bash
php spark serve
```

### 2. Tester avec Postman
- Importez `postman/Cambizzle_API_Complete.postman_collection.json`
- Utilisez l'environnement `Cambizzle_Environment.postman_environment.json`

### 3. Endpoint de test
```bash
GET http://localhost:8080/api/admin/dashboard
```

## 📈 Fonctionnalités activées

Après ce setup, vous pouvez utiliser :

- ✅ **Modération d'annonces** (approuver/rejeter avec logs)
- ✅ **Gestion des utilisateurs** (suspendre/réactiver avec traçabilité)
- ✅ **Vérification d'identité** (manuelle par admin)
- ✅ **Packs promotionnels** (avec données de test)
- ✅ **Dashboard admin** (statistiques complètes)
- ✅ **Reporting** (export de données)

---

**🎉 Prêt à utiliser l'API Cambizzle complète !**










