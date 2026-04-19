<?php
namespace Migrations;
require_once(__DIR__ . '/../config/bootstrap.php');
require_once(__DIR__ . '/../src/helpers.php');
use Database\ConnectionManager;
use RuntimeException;
use PDOException;
use Models\Movie;


/**
 * Recalculate vote totals (source of truth = votes table)
 */
$db = ConnectionManager::get('default');
$file = __DIR__ . '/migration.sql';

if (!file_exists($file)) {
    throw new RuntimeException("Migration file not found: {$file}");
}

$sql = file_get_contents($file);

if ($sql === false) {
    throw new RuntimeException('Failed to read migration file.');
}

try {
    $db->execute($sql);
    echo "Migration executed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: {$e->getMessage()} \n";
    exit;
}
/**
 * Run Seeder
 */
echo "Generating votes...\n";
generateVotes();

echo "Recalculating totals...\n";
Movie::rebuildVoteCounts();

echo "Done.\n";