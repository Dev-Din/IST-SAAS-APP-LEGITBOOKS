<?php

/**
 * SQL File Validation Script
 *
 * This script validates the setup.sql file structure without requiring a database connection.
 * Run this before importing the SQL file to catch syntax errors early.
 */
$sqlFile = __DIR__.'/setup.sql';

if (! file_exists($sqlFile)) {
    echo "ERROR: setup.sql file not found!\n";
    exit(1);
}

echo "Validating setup.sql file...\n\n";

$content = file_get_contents($sqlFile);
$errors = [];
$warnings = [];

// Check for required sections
$requiredSections = [
    'SET FOREIGN_KEY_CHECKS=0',
    'CREATE TABLE',
    'INSERT INTO',
    'SET FOREIGN_KEY_CHECKS=1',
];

foreach ($requiredSections as $section) {
    if (strpos($content, $section) === false) {
        $errors[] = "Missing required section: $section";
    }
}

// Count tables
preg_match_all('/CREATE TABLE IF NOT EXISTS `([^`]+)`/i', $content, $matches);
$tables = $matches[1] ?? [];
$tableCount = count($tables);

echo "Found $tableCount tables:\n";
foreach ($tables as $table) {
    echo "  - $table\n";
}

// Check for foreign key constraints
preg_match_all('/FOREIGN KEY.*REFERENCES `([^`]+)`/i', $content, $fkMatches);
$referencedTables = array_unique($fkMatches[1] ?? []);
echo "\nForeign key references found: ".count($referencedTables)."\n";

// Check for INSERT statements
preg_match_all('/INSERT INTO `([^`]+)`/i', $content, $insertMatches);
$insertTables = array_unique($insertMatches[1] ?? []);
echo 'Seed data tables: '.count($insertTables)."\n";
foreach ($insertTables as $table) {
    echo "  - $table\n";
}

// Validate foreign key references
foreach ($referencedTables as $refTable) {
    if (! in_array($refTable, $tables)) {
        $warnings[] = "Foreign key references non-existent table: $refTable";
    }
}

// Check for balanced FOREIGN_KEY_CHECKS
$fkChecksOff = substr_count($content, 'SET FOREIGN_KEY_CHECKS=0');
$fkChecksOn = substr_count($content, 'SET FOREIGN_KEY_CHECKS=1');

if ($fkChecksOff !== $fkChecksOn) {
    $errors[] = "FOREIGN_KEY_CHECKS not balanced (OFF: $fkChecksOff, ON: $fkChecksOn)";
}

// Check for SQL syntax issues (basic checks)
if (preg_match('/CREATE TABLE[^;]*\([^)]*\)[^;]/i', $content)) {
    $warnings[] = 'Possible unclosed CREATE TABLE statement';
}

// Report results
echo "\n".str_repeat('=', 50)."\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ SQL file validation PASSED\n";
    echo "\nFile is ready for import.\n";
    exit(0);
}

if (! empty($errors)) {
    echo "❌ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

if (! empty($warnings)) {
    echo "\n⚠️  WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
}

if (! empty($errors)) {
    exit(1);
}

exit(0);
