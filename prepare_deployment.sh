#!/bin/bash
# Script de préparation complète pour le déploiement
# À exécuter avant l'upload sur LWS Panel

echo "=== PRÉPARATION DU DÉPLOIEMENT CAMBIZZLE ==="
echo "API + Frontend React sur www.cambizzle.seed-innov.com"
echo ""

# 1. Nettoyage des fichiers de développement
echo "1. Nettoyage des fichiers de développement..."
php clean_for_production.php

# 2. Création de la structure de déploiement
echo "2. Création de la structure de déploiement..."
mkdir -p deploy/api
mkdir -p deploy/root

# 3. Copie des fichiers API vers le dossier de déploiement
echo "3. Préparation des fichiers API..."
cp -r app deploy/api/
cp -r system deploy/api/
cp -r vendor deploy/api/
cp -r writable deploy/api/
cp -r public deploy/api/
cp composer.json deploy/api/
cp composer.lock deploy/api/
cp spark deploy/api/
cp .env.production deploy/api/.env
cp .htaccess.api deploy/api/.htaccess

# 4. Préparation des fichiers pour la racine
echo "4. Préparation des fichiers racine..."
cp .htaccess.root deploy/root/.htaccess
cp generate_production_keys.php deploy/api/
cp deployment_check.php deploy/api/

# 5. Instructions finales
echo ""
echo "=== STRUCTURE DE DÉPLOIEMENT CRÉÉE ==="
echo ""
echo "📁 deploy/"
echo "├── 📁 root/ (à uploader à la racine www/)"
echo "│   └── .htaccess"
echo "└── 📁 api/ (à uploader dans www/api/)"
echo "    ├── app/"
echo "    ├── system/"
echo "    ├── vendor/"
echo "    ├── writable/"
echo "    ├── public/"
echo "    ├── .env"
echo "    ├── .htaccess"
echo "    └── scripts utilitaires"
echo ""
echo "PROCHAINES ÉTAPES :"
echo "1. Compilez votre frontend React (npm run build)"
echo "2. Uploadez le contenu de build/ à la racine www/"
echo "3. Uploadez deploy/root/.htaccess à la racine www/"
echo "4. Uploadez deploy/api/ dans www/api/"
echo "5. Configurez la base de données dans www/api/.env"
echo "6. Exécutez php www/api/generate_production_keys.php"
echo "7. Testez : www.cambizzle.seed-innov.com et www.cambizzle.seed-innov.com/api/"
echo ""
echo "✅ Préparation terminée !"
