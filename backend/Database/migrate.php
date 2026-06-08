<?php

/**
 * Database Migration Runner
 * 
 * Usage: php database/migrate.php
 * Runs all migration files in order.
 */

echo "=== School CMS Database Migration Runner ===" . PHP_EOL . PHP_EOL;

// Load environment variables
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    echo "ERROR: .env file not found. Copy .env.example to .env and configure." . PHP_EOL;
    exit(1);
}

loadEnv($envPath);

// Database connection
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? 'school_cms';
$user = $_ENV['DB_USER'] ?? 'postgres';
$password = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected to database: {$dbName}" . PHP_EOL;
} catch (PDOException $e) {
    echo "ERROR: Database connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Create migrations tracking table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id SERIAL PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        run_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
    )
");

// Get already run migrations
$stmt = $pdo->query("SELECT migration FROM migrations ORDER BY id");
$runMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get migration files
$migrationDir = __DIR__ . '/migrations/';
$files = glob($migrationDir . '*.sql');
sort($files);

$newMigrations = 0;
foreach ($files as $file) {
    $migrationName = basename($file);
    
    if (in_array($migrationName, $runMigrations)) {
        echo "SKIP: {$migrationName} (already run)" . PHP_EOL;
        continue;
    }

    echo "RUNNING: {$migrationName}" . PHP_EOL;
    
    $sql = file_get_contents($file);
    
    try {
        $pdo->exec($sql);
        
        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationName]);
        
        echo "  ✓ SUCCESS" . PHP_EOL;
        $newMigrations++;
    } catch (PDOException $e) {
        echo "  ✗ FAILED: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo PHP_EOL;
if ($newMigrations > 0) {
    echo "Completed {$newMigrations} new migration(s)." . PHP_EOL;
} else {
    echo "No new migrations to run." . PHP_EOL;
}

echo "Done." . PHP_EOL;

function loadEnv(string $path): void
{
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remove quotes
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
            $value = $matches[1];
        }
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}
