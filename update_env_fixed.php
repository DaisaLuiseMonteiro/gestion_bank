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

echo "Le fichier .env a été mis à jour avec succès !\n\n";

// Afficher les informations de connexion à des fins de débogage
echo "Configuration de la base de données Neon :\n";
echo "Hôte : {$config['NEON_DB_HOST']}\n";
echo "Port : {$config['NEON_DB_PORT']}\n";
echo "Base de données : {$config['NEON_DB_DATABASE']}\n";
echo "Utilisateur : {$config['NEON_DB_USERNAME']}\n\n";

// Tester la connexion avec la commande psql
echo "Test de connexion à la base de données Neon avec psql...\n";
$psqlCommand = "PGPASSWORD='{$config['NEON_DB_PASSWORD']}' psql -h {$config['NEON_DB_HOST']} -p {$config['NEON_DB_PORT']} -U {$config['NEON_DB_USERNAME']} -d {$config['NEON_DB_DATABASE']} -c 'SELECT version()' 2>&1";
$output = [];
$return_var = 0;
exec($psqlCommand, $output, $return_var);

if ($return_var === 0) {
    echo "✅ Connexion réussie à la base de données Neon!\n";
    echo "📊 Réponse du serveur :\n" . implode("\n", $output) . "\n";
} else {
    echo "❌ Échec de la connexion à la base de données Neon\n";
    echo "Commande exécutée : " . str_replace($config['NEON_DB_PASSWORD'], '*****', $psqlCommand) . "\n";
    echo "Sortie d'erreur :\n" . implode("\n", $output) . "\n";
    echo "\nVérifiez que :\n";
    echo "1. Le serveur PostgreSQL est accessible depuis cette machine\n";
    echo "2. Les identifiants sont corrects\n";
    echo "3. Le pare-feu autorise les connexions sur le port 5432\n";
    echo "4. L'utilisateur a les droits de se connecter depuis votre adresse IP\n";
}

// Vérifier si l'extension PDO_PGSQL est installée
echo "\nVérification des extensions PHP nécessaires :\n";
$extensions = ['pdo_pgsql', 'pgsql'];
$allOk = true;

foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext est installé\n";
    } else {
        echo "❌ $ext n'est PAS installé\n";
        $allOk = false;
    }
}

if (!$allOk) {
    echo "\n⚠️  Certaines extensions nécessaires ne sont pas installées.\n";
    echo "Pour les installer sur Ubuntu/Debian, exécutez :\n";
    echo "sudo apt-get update && sudo apt-get install php-pgsql\n";
    echo "Puis redémarrez votre serveur web.\n";
}

echo "\nConfiguration terminée !\n";
