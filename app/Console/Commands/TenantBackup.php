<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TenantBackup extends Command
{
    protected $signature = 'tenant:backup {tenant_hash}';

    protected $description = 'Backup tenant-scoped data to SQL file';

    public function handle()
    {
        $tenantHash = $this->argument('tenant_hash');
        $tenant = Tenant::where('tenant_hash', $tenantHash)->first();

        if (!$tenant) {
            $this->error("Tenant not found with hash: {$tenantHash}");
            return Command::FAILURE;
        }

        $backupDir = storage_path("backups/{$tenantHash}");
        File::ensureDirectoryExists($backupDir);

        $filename = "backup_" . date('Y-m-d_His') . ".sql";
        $filepath = "{$backupDir}/{$filename}";

        $tables = [
            'users', 'contacts', 'products', 'chart_of_accounts', 'accounts',
            'invoices', 'invoice_line_items', 'payments', 'payment_allocations',
            'journal_entries', 'journal_lines', 'fixed_assets', 'audit_logs',
            'attachments', 'invoice_counters', 'recurring_templates', 'csv_import_jobs',
        ];

        $sql = "-- Tenant Backup: {$tenant->name}\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $records = DB::table($table)
                ->where('tenant_id', $tenant->id)
                ->get();

            if ($records->isEmpty()) {
                continue;
            }

            $sql .= "-- Table: {$table}\n";
            foreach ($records as $record) {
                $columns = implode(', ', array_keys((array) $record));
                $values = implode(', ', array_map(function ($value) {
                    return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                }, array_values((array) $record)));

                $sql .= "INSERT INTO {$table} ({$columns}) VALUES ({$values});\n";
            }
            $sql .= "\n";
        }

        File::put($filepath, $sql);

        $this->info("Backup created: {$filepath}");
        $this->info("Size: " . number_format(File::size($filepath) / 1024, 2) . " KB");

        return Command::SUCCESS;
    }
}
