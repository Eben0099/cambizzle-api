# Cambizzle API - Collection Postman

Collection Postman complète pour tester tous les endpoints de l'API Cambizzle.

## 📁 Fichiers

- `Cambizzle_API_Complete.postman_collection.json` - Collection complète avec tous les endpoints
- `Cambizzle_Environment.postman_environment.json` - Variables d'environnement
- `README.md` - Ce fichier d'instructions

## 🚀 Installation

1. **Importer la collection** :
   - Ouvrir Postman
   - Cliquer sur "Import" en haut à gauche
   - Sélectionner "File"
   - Importer `Cambizzle_API_Complete.postman_collection.json`

2. **Importer l'environnement** :
   - Dans Postman, cliquer sur "Environments" à gauche
   - Cliquer sur "Import"
   - Importer `Cambizzle_Environment.postman_environment.json`

3. **Configurer l'environnement** :
   - Sélectionner "Cambizzle Local Environment" dans le menu déroulant des environnements
   - Modifier `base_url` si nécessaire (par défaut: `http://localhost:8080`)

## 📋 Organisation des Endpoints

La collection est organisée en 12 dossiers :

### 1. 🔐 Authentification
- Inscription utilisateur
- Connexion utilisateur
- Profil utilisateur connecté

### 2. 👥 Gestion des Utilisateurs
- Lister les utilisateurs (Admin)
- Détails utilisateur
- Mise à jour utilisateur
- Changement de mot de passe
- Vérification d'identité (upload)
- Suspension/Réactivation utilisateur (Admin)
- Vérification identité (Admin)
- Suppression utilisateur (Admin)

### 3. 🏷️ Gestion des Annonces
- Données de création d'annonce
- Créer une annonce
- Lister les annonces
- Détails d'une annonce
- Mise à jour d'une annonce
- Upload de photos
- Suppression d'une annonce
- Annonces en attente (Admin)
- Approuver/Rejeter une annonce (Admin)

### 4. 💬 Messages et Avis
- Messages de l'utilisateur
- Envoyer un message
- Envoyer un avis avec note
- Marquer comme lu
- Nombre de messages non lus

### 5. 🚨 Signalements
- Créer un signalement
- Signalements de l'utilisateur
- Signalements en attente (Admin)
- Résoudre un signalement (Admin)

### 6. 📂 Catégories et Sous-catégories
- Lister les catégories
- Sous-catégories d'une catégorie
- Catégories avec statistiques (Admin)

### 7. 🏢 Marques
- Lister les marques (par sous-catégorie)

### 8. 🎁 Parrainage
- Codes de parrainage
- Créer un code de parrainage
- Utiliser un code de parrainage
- Statistiques de parrainage

### 9. 📊 Administration - Dashboard
- Dashboard admin complet
- Logs de modération

### 10. 🗂️ Administration - Référentiels
- **Catégories** : CRUD complet (Admin)
- **Sous-catégories** : CRUD complet (Admin)
- **Filtres** : CRUD complet (Admin)
- **Marques** : CRUD complet (Admin)

### 11. 💰 Administration - Promotions
- **Packs promotionnels** : CRUD complet (Admin)
- Promotions actives
- Activation de promotion
- Statistiques des promotions

### 12. 📈 Administration - Reporting
- Statistiques globales
- Statistiques détaillées (par période)
- Export de données (JSON/CSV)

## 🔧 Variables d'Environnement

### Variables Globales
- `base_url` : URL de base de l'API (http://localhost:8080)
- `user_token` : Token JWT utilisateur
- `admin_token` : Token JWT administrateur

### Variables Dynamiques (remplies automatiquement)
- `user_id` : ID utilisateur connecté
- `ad_id` : ID annonce courante
- `category_id` : ID catégorie courante
- `subcategory_id` : ID sous-catégorie courante
- `message_id` : ID message courant
- `report_id` : ID signalement courant
- `brand_id` : ID marque courante
- `filter_id` : ID filtre courant

## 📝 Workflow de Test

### 1. Configuration Initiale
1. Importer la collection et l'environnement
2. Démarrer votre serveur API Cambizzle
3. Vérifier que `base_url` pointe vers votre serveur

### 2. Authentification
1. **Inscription Utilisateur** : Créer un compte test
2. **Connexion Utilisateur** : Récupérer le token JWT (sera automatiquement sauvegardé dans `user_token`)
3. **Profil Utilisateur** : Tester que l'authentification fonctionne

### 3. Création de Contenu
1. **Données de Création** : Récupérer les catégories/sous-catégories disponibles
2. **Créer une Annonce** : Publier une annonce test
3. **Upload Photos** : Ajouter des photos à l'annonce (optionnel)

### 4. Tests Admin (nécessite un compte admin)
1. **Connexion Admin** : Se connecter avec un compte administrateur
2. **Dashboard** : Voir les statistiques générales
3. **Modération** : Approuver/rejeter des annonces
4. **Gestion Utilisateurs** : Suspendre/réactiver des comptes

### 5. Tests Avancés
1. **Messages** : Envoyer des messages entre utilisateurs
2. **Signalements** : Créer et gérer des signalements
3. **Parrainage** : Tester le système de parrainage
4. **Reporting** : Consulter les statistiques détaillées

## ⚡ Scripts Automatisés

La collection inclut des scripts de pré-request qui :
- Définissent automatiquement le Content-Type pour les requêtes JSON
- Peuvent extraire automatiquement les IDs des réponses pour les utiliser dans les requêtes suivantes

## 🔍 Tests et Validation

Chaque requête inclut :
- Les headers appropriés (Authorization, Content-Type)
- Des exemples de données JSON valides
- Des paramètres de requête pour la pagination et les filtres
- Des descriptions détaillées dans l'onglet "Description"

## 📊 Codes de Réponse

- **200** : Succès
- **201** : Créé avec succès
- **400** : Requête invalide
- **401** : Non autorisé
- **403** : Interdit
- **404** : Ressource non trouvée
- **422** : Erreur de validation
- **500** : Erreur serveur

## 🆘 Dépannage

### Problème : "Could not get any response"
- Vérifier que le serveur API est démarré
- Vérifier l'URL dans `base_url`

### Problème : "401 Unauthorized"
- Vérifier que le token JWT est valide
- Vérifier que les variables `user_token` ou `admin_token` sont définies

### Problème : "422 Validation Error"
- Vérifier les données envoyées dans le body
- S'assurer que tous les champs requis sont présents

### Problème : "500 Internal Server Error"
- Vérifier les logs du serveur API
- S'assurer que la base de données est accessible

## 📞 Support

Pour toute question concernant l'API ou cette collection Postman :
- Consulter la documentation API complète dans `API_DOCUMENTATION.md`
- Vérifier les logs d'erreur dans `writable/logs/`
- Tester les endpoints un par un pour isoler les problèmes

---

**Version** : 1.0.0
**Date** : Octobre 2025
**API Version** : Cambizzle API v1
