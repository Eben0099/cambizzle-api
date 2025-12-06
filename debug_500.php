<?php
/**
 * Script de débogage pour l'erreur 500
 * À placer dans le dossier api/ et appeler directement
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 DÉBOGAGE ERREUR 500</h1>";
echo "<style>body{font-family:monospace;} .error{color:red;} .success{color:green;} .info{color:blue;}</style>";

// 1. PHP Version
echo "<h2>1. Version PHP</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PHP OK: " . (version_compare(phpversion(), '8.1.0', '>=') ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</p>";

// 2. Extensions critiques
echo "<h2>2. Extensions PHP</h2>";
$criticalExtensions = ['mysqli', 'json', 'mbstring'];
foreach ($criticalExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p>$ext: " . ($loaded ? '<span class="success">✅ LOADED</span>' : '<span class="error">❌ MISSING</span>') . "</p>";
}

// 3. Fichiers critiques
echo "<h2>3. Fichiers critiques</h2>";
$criticalFiles = [
    'vendor/autoload.php',
    'app/Config/Paths.php',
    'app/Config/App.php',
    'system/Boot.php'
];

foreach ($criticalFiles as $file) {
    $exists = file_exists($file);
    echo "<p>$file: " . ($exists ? '<span class="success">✅ EXISTS</span>' : '<span class="error">❌ MISSING</span>') . "</p>";
}

// 4. Test des chemins Paths.php
echo "<h2>4. Test des chemins Paths.php</h2>";
try {
    if (!class_exists('Config\Paths')) {
        require 'app/Config/Paths.php';
    }
    $paths = new Config\Paths();

    $pathTests = [
        'systemDirectory' => $paths->systemDirectory,
        'appDirectory' => $paths->appDirectory,
        'writableDirectory' => $paths->writableDirectory,
    ];

    foreach ($pathTests as $name => $path) {
        $exists = is_dir($path);
        $readable = is_readable($path);
        $writable = is_writable($path);
        echo "<p>$name ($path):</p>";
        echo "<ul>";
        echo "<li>Exists: " . ($exists ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</li>";
        echo "<li>Readable: " . ($readable ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</li>";
        echo "<li>Writable: " . ($writable ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . "</li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ ERREUR Paths.php: " . $e->getMessage() . "</p>";
}

// 5. Test de chargement de l'autoloader
echo "<h2>5. Test Autoloader</h2>";
try {
    if (file_exists('vendor/autoload.php')) {
        require 'vendor/autoload.php';
        echo "<p class='success'>✅ Autoloader chargé</p>";
    } else {
        echo "<p class='error'>❌ Autoloader manquant</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ ERREUR Autoloader: " . $e->getMessage() . "</p>";
}

// 6. Test du fichier .env
echo "<h2>6. Test fichier .env</h2>";
if (file_exists('.env')) {
    echo "<p class='success'>✅ .env existe</p>";
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'database.default.database') !== false) {
        echo "<p class='success'>✅ Configuration DB présente</p>";
    } else {
        echo "<p class='warning'>⚠️ Configuration DB absente</p>";
    }
} else {
    echo "<p class='error'>❌ .env manquant</p>";
    echo "<p><strong>À créer à partir de :</strong> env_template.txt</p>";
}

// 7. Test de simulation du bootstrap CodeIgniter
echo "<h2>7. Test Bootstrap CodeIgniter</h2>";
try {
    // Simuler ce que fait index.php
    define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

    if (!is_dir(FCPATH)) {
        throw new Exception("FCPATH n'existe pas: " . FCPATH);
    }

    echo "<p class='success'>✅ FCPATH défini: " . FCPATH . "</p>";

    // Tester le chargement de Paths (éviter le conflit de classe)
    $pathsFile = FCPATH . '../app/Config/Paths.php';
    if (file_exists($pathsFile)) {
        echo "<p class='success'>✅ Fichier Paths.php trouvé</p>";
        // On ne peut pas tester l'instanciation à cause du conflit de classe
        echo "<p class='info'>ℹ️ Test d'instanciation skipped (conflit de classe)</p>";
    } else {
        echo "<p class='error'>❌ Fichier Paths.php manquant</p>";
    }
    // Tester le chargement du système (chemin par défaut)
    $bootFile = FCPATH . '../vendor/codeigniter4/framework/system/Boot.php';
    if (file_exists($bootFile)) {
        echo "<p class='success'>✅ Boot.php trouvé: " . basename(dirname($bootFile)) . "/Boot.php</p>";
    } else {
        echo "<p class='error'>❌ Boot.php manquant (vérifiez vendor/ ou system/)</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ ERREUR Bootstrap: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>📋 RÉSUMÉ DES PROCHAINES ÉTAPES</h2>";
echo "<ol>";
echo "<li><strong>Vérifiez les éléments marqués ❌</strong></li>";
echo "<li><strong>Créez le fichier .env</strong> si manquant</li>";
echo "<li><strong>Vérifiez les permissions</strong> des dossiers writable/</li>";
echo "<li><strong>Consultez les logs PHP</strong> de votre hébergement</li>";
echo "</ol>";

echo "<p><em>Test exécuté le " . date('d/m/Y à H:i:s') . "</em></p>";
?>
