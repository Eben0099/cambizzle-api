<?php

// Configuration de la base de données pour le setup
// MODIFIEZ CES VALEURS selon votre configuration MySQL

return [
    'host' => 'localhost',      // Adresse du serveur MySQL (localhost, 127.0.0.1, etc.)
    'database' => 'cambizzle-api',  // Nom de votre base de données
    'username' => 'root',       // Votre nom d'utilisateur MySQL
    'password' => '',           // Votre mot de passe MySQL (vide si aucun)
    'charset' => 'utf8mb4'      // Charset de la base de données
];

/*
INSTRUCTIONS DE CONFIGURATION :

1. host : Adresse de votre serveur MySQL
   - 'localhost' ou '127.0.0.1' pour un serveur local
   - Adresse IP pour un serveur distant

2. database : Nom de votre base de données
   - Assurez-vous que cette base existe déjà
   - Si elle n'existe pas, créez-la dans phpMyAdmin ou MySQL Workbench

3. username : Nom d'utilisateur MySQL
   - 'root' par défaut sur la plupart des installations locales
   - Vérifiez dans votre configuration MySQL

4. password : Mot de passe MySQL
   - Vide ('') par défaut sur de nombreuses installations locales
   - Si vous avez défini un mot de passe, mettez-le entre guillemets

EXEMPLES DE CONFIGURATIONS :

// Configuration locale typique (XAMPP, WAMP)
return [
    'host' => 'localhost',
    'database' => 'cambizzle-api',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

// Configuration avec mot de passe
return [
    'host' => 'localhost',
    'database' => 'cambizzle-api',
    'username' => 'root',
    'password' => 'mon_mot_de_passe',
    'charset' => 'utf8mb4'
];

// Configuration serveur distant
return [
    'host' => 'sql.example.com',
    'database' => 'cambizzle-api',
    'username' => 'cambizzle_user',
    'password' => 'mot_de_passe_complexe',
    'charset' => 'utf8mb4'
];

DEPANNAGE :

Si vous obtenez une erreur de connexion :
- Vérifiez que MySQL est démarré
- Testez la connexion avec phpMyAdmin ou MySQL Workbench
- Vérifiez les permissions de l'utilisateur
- Assurez-vous que le firewall n bloque pas la connexion

*/










