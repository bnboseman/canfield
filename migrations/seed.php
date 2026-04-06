<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../helpers.php");
require_once(__DIR__ . "/../Models/movies.php");
require_once(__DIR__ . "/../Models/votes.php");


/**
 * Recalculate vote totals (source of truth = votes table)
 */
$db = Database::getInstance();
$file = __DIR__ . '/migration.sql';

if (!file_exists($file)) {
    throw new RuntimeException("Migration file not found: {$file}");
}

$sql = file_get_contents($file);

if ($sql === false) {
    throw new RuntimeException("Failed to read migration file.");
}

try {
    // PDO multi-query safe execution
    $db->getConnection()->exec($sql);
    echo "Migration executed successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
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