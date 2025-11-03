<?php

$envFile = __DIR__ . '/.env';

// Vérifie si le fichier .env existe
if (!file_exists($envFile)) {
    die("Le fichier .env n'existe pas!");
}

// Configuration à ajouter/mettre à jour
$config = [
    'NEON_DB_CONNECTION' => 'pgsql',
    'NEON_DB_HOST' => 'ep-withered-fire-ah109dcp-pooler.c-3.us-east-1.aws.neon.tech',
    'NEON_DB_PORT' => '5432',
    'NEON_DB_DATABASE' => 'neondb',
    'NEON_DB_USERNAME' => 'neondb_owner',
    'NEON_DB_PASSWORD' => 'npg_aGkwY6fq5SXh',
    'NEON_SSLMODE' => 'require'
];

// Lire le contenu actuel du fichier .env
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$newLines = [];
$updated = [];

// Parcourir chaque ligne du fichier
foreach ($lines as $line) {
    // Ignorer les commentaires et les lignes vides
    if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
        $newLines[] = $line;
        continue;
    }
    
    // Vérifier si la ligne contient une configuration à mettre à jour
    $lineKey = explode('=', $line, 2)[0];
    $lineKey = trim($lineKey);
    
    if (array_key_exists($lineKey, $config)) {
        // Mettre à jour la valeur existante
        $newLines[] = "{$lineKey}={$config[$lineKey]}";
        $updated[$lineKey] = true;
    } else {
        // Garder la ligne telle quelle
        $newLines[] = $line;
    }
}

// Ajouter les configurations manquantes
foreach ($config as $key => $value) {
    if (!isset($updated[$key])) {
        $newLines[] = "{$key}={$value}";
    }
}

// Écrire le nouveau contenu dans le fichier .env
file_put_contents($envFile, implode("\n", $newLines));

echo "Le fichier .env a été mis à jour avec succès !\n";

// Test de connexion à la base de données Neon
echo "\nTest de connexion à la base de données Neon...\n";

try {
    $dsn = "pgsql:host={$config['NEON_DB_HOST']};port={$config['NEON_DB_PORT']};dbname={$config['NEON_DB_DATABASE']};";
    $pdo = new PDO(
        $dsn,
        $config['NEON_DB_USERNAME'],
        $config['NEON_DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_SSL_MODE => PDO::SSL_MODE_REQUIRE
        ]
    );
    
    echo "✅ Connexion réussie à la base de données Neon!\n";
    
    // Tester une requête simple
    $version = $pdo->query('SELECT version()')->fetchColumn();
    echo "📊 Version de la base de données : " . $version . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
    echo "Veuillez vérifier vos informations de connexion dans le fichier .env\n";
}
