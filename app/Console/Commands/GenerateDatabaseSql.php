<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateDatabaseSql extends Command
{
    protected $signature = 'db:generate-sql {--schema-only : Generate only schema SQL} {--seeds-only : Generate only seed data SQL} {--all : Generate both schema and seeds (default)}';

    protected $description = 'Generate complete database setup SQL file from migrations and seeders';

    public function handle()
    {
        $generateSchema = ! $this->option('seeds-only');
        $generateSeeds = ! $this->option('schema-only');

        if ($this->option('all') || (! $this->option('schema-only') && ! $this->option('seeds-only'))) {
            $generateSchema = true;
            $generateSeeds = true;
        }

        if ($generateSchema) {
            $this->info('Generating schema SQL...');
            $this->generateSchemaSql();
        }

        if ($generateSeeds) {
            $this->info('Generating seed data SQL...');
            $this->generateSeedDataSql();
        }

        if ($generateSchema && $generateSeeds) {
            $this->info('Combining SQL files...');
            $this->combineSqlFiles();
        }

        $this->info('SQL generation complete!');

        return Command::SUCCESS;
    }

    protected function generateSchemaSql(): void
    {
        $sql = "-- LegitBooks Database Schema\n";
        $sql .= '-- Generated: '.now()->toDateTimeString()."\n";
        $sql .= "-- This file contains all CREATE TABLE statements for the application\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        try {
            // Try to extract from database if available
            $tables = $this->getTablesFromDatabase();
            if (! empty($tables)) {
                foreach ($tables as $table) {
                    $createTable = $this->getCreateTableSql($table);
                    if ($createTable) {
                        $sql .= $createTable."\n\n";
                    }
                }
            } else {
                // Fallback: Generate from migrations
                $sql .= $this->generateFromMigrations();
            }
        } catch (\Exception $e) {
            $this->warn('Could not extract from database: '.$e->getMessage());
            $this->info('Generating from migrations...');
            $sql .= $this->generateFromMigrations();
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::ensureDirectoryExists(database_path());
        File::put(database_path('schema.sql'), $sql);
        $this->info('Schema SQL written to: database/schema.sql');
    }

    protected function getTablesFromDatabase(): array
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_'.DB::getDatabaseName();

            return array_map(fn ($table) => $table->$tableKey, $tables);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getCreateTableSql(string $table): ?string
    {
        try {
            $result = DB::select("SHOW CREATE TABLE `{$table}`");
            if (! empty($result)) {
                $createTable = $result[0]->{'Create Table'} ?? null;

                return $createTable ? "-- Table: {$table}\n".$createTable.';' : null;
            }
        } catch (\Exception $e) {
            // Table might not exist
        }

        return null;
    }

    protected function generateFromMigrations(): string
    {
        // This is a fallback - in practice, extracting from DB is more reliable
        // For now, return a note that manual extraction is needed
        return "-- Note: Schema extraction from migrations requires database connection.\n";
    }

    protected function generateSeedDataSql(): void
    {
        $sql = "-- LegitBooks Seed Data\n";
        $sql .= '-- Generated: '.now()->toDateTimeString()."\n";
        $sql .= "-- This file contains initial data for the application\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Platform Settings
        $sql .= $this->getPlatformSettingsSql();

        // Spatie Permission Roles
        $sql .= $this->getRolesSql();

        // Super Admin
        $sql .= $this->getSuperAdminSql();

        // Demo Tenant (optional - can be commented out)
        $sql .= $this->getDemoTenantSql();

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::ensureDirectoryExists(database_path());
        File::put(database_path('seed_data.sql'), $sql);
        $this->info('Seed data SQL written to: database/seed_data.sql');
    }

    protected function getPlatformSettingsSql(): string
    {
        $sql = "-- Platform Settings\n";
        $sql .= "INSERT INTO `platform_settings` (`key`, `value`, `type`, `created_at`, `updated_at`) VALUES\n";
        $sql .= "('branding_mode', 'A', 'string', NOW(), NOW()),\n";
        $sql .= "('mpesa_environment', 'sandbox', 'string', NOW(), NOW());\n\n";

        return $sql;
    }

    protected function getRolesSql(): string
    {
        $sql = "-- Spatie Permission Roles\n";
        $sql .= "INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES\n";
        $sql .= "('owner', 'admin', NOW(), NOW()),\n";
        $sql .= "('subadmin', 'admin', NOW(), NOW())\n";
        $sql .= "ON DUPLICATE KEY UPDATE `updated_at` = NOW();\n\n";

        return $sql;
    }

    protected function getSuperAdminSql(): string
    {
        $sql = "-- Super Admin User\n";
        $sql .= "-- Password: password (change after first login!)\n";
        $password = password_hash('password', PASSWORD_BCRYPT);
        $sql .= "INSERT INTO `admins` (`name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES\n";
        $sql .= "('Owner', 'admin@legitbooks.com', '".addslashes($password)."', 'owner', 1, NOW(), NOW())\n";
        $sql .= "ON DUPLICATE KEY UPDATE `updated_at` = NOW();\n\n";

        // Assign role
        $sql .= "-- Assign owner role to admin\n";
        $sql .= "INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) \n";
        $sql .= "SELECT r.id, 'App\\\\Models\\\\Admin', a.id FROM `roles` r, `admins` a \n";
        $sql .= "WHERE r.name = 'owner' AND r.guard_name = 'admin' AND a.email = 'admin@legitbooks.com'\n";
        $sql .= "ON DUPLICATE KEY UPDATE `role_id` = `role_id`;\n\n";

        return $sql;
    }

    protected function getDemoTenantSql(): string
    {
        $sql = "-- Demo Tenant (Optional - uncomment to include)\n";
        $sql .= "/*\n";
        $sql .= "INSERT INTO `tenants` (`name`, `email`, `tenant_hash`, `status`, `settings`, `created_at`, `updated_at`) VALUES\n";
        $sql .= "('Demo Tenant', 'demo@tenant.com', '".base64_encode(\Illuminate\Support\Str::uuid()->toString())."', 'active', '{\"branding_override\":null,\"brand\":{\"name\":\"Demo Tenant\",\"logo_path\":null,\"primary_color\":\"#392a26\",\"text_color\":\"#ffffff\"}}', NOW(), NOW())\n";
        $sql .= "ON DUPLICATE KEY UPDATE `updated_at` = NOW();\n\n";
        $sql .= "-- Demo Tenant Users\n";
        $password = password_hash('password', PASSWORD_BCRYPT);
        $sql .= "INSERT INTO `users` (`tenant_id`, `name`, `email`, `password`, `is_active`, `is_owner`, `role_name`, `permissions`, `created_at`, `updated_at`) \n";
        $sql .= "SELECT t.id, 'Admin', 'admin@demo.com', '".addslashes($password)."', 1, 1, 'Account Owner', '[]', NOW(), NOW() FROM `tenants` t WHERE t.email = 'demo@tenant.com'\n";
        $sql .= "ON DUPLICATE KEY UPDATE `updated_at` = NOW();\n";
        $sql .= "*/\n\n";

        return $sql;
    }

    protected function combineSqlFiles(): void
    {
        $schema = File::exists(database_path('schema.sql')) ? File::get(database_path('schema.sql')) : '';
        $seeds = File::exists(database_path('seed_data.sql')) ? File::get(database_path('seed_data.sql')) : '';

        $combined = "-- LegitBooks Complete Database Setup\n";
        $combined .= '-- Generated: '.now()->toDateTimeString()."\n";
        $combined .= "-- This file contains both schema and seed data\n";
        $combined .= "-- Usage: mysql -u root -p database_name < setup.sql\n\n";
        $combined .= $schema."\n\n".$seeds;

        File::put(database_path('setup.sql'), $combined);
        $this->info('Combined SQL written to: database/setup.sql');
    }
}
