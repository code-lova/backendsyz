<?php

namespace App\Console\Commands;

use App\Services\PerformanceMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeDatabasePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:analyze-performance {--table=* : Specific tables to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database performance and suggest optimizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Analyzing Database Performance...');
        $this->newLine();

        $tables = $this->option('table') ?: ['users', 'booking_appts', 'personal_access_tokens'];

        foreach ($tables as $table) {
            $this->analyzeTable($table);
        }

        $this->newLine();
        $this->displayOptimizationSuggestions();

        $this->newLine();
        $this->displayConnectionStats();

        return Command::SUCCESS;
    }

    private function analyzeTable(string $table): void
    {
        try {
            $this->info("📊 Analyzing table: {$table}");

            // Check if table exists
            if (!$this->tableExists($table)) {
                $this->warn("  ⚠️  Table '{$table}' does not exist");
                return;
            }

            $analysis = PerformanceMonitoringService::analyzeTableIndexes($table);

            $this->line("  📈 Total indexes: {$analysis['total_indexes']}");
            $this->line("  🔑 Unique indexes: {$analysis['unique_indexes']}");
            $this->line("  📋 Non-unique indexes: {$analysis['non_unique_indexes']}");

            if (!empty($analysis['indexes'])) {
                $this->line("  📋 Indexes:");
                foreach ($analysis['indexes'] as $index) {
                    $type = $index['unique'] ? 'UNIQUE' : 'INDEX';
                    $columns = implode(', ', $index['columns']);
                    $this->line("    - {$index['name']} ({$type}): [{$columns}]");
                }
            }

            // Get table row count
            $rowCount = DB::table($table)->count();
            $this->line("  📊 Total rows: " . number_format($rowCount));

            $this->newLine();

        } catch (\Exception $e) {
            $this->error("  ❌ Error analyzing table '{$table}': " . $e->getMessage());
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displayOptimizationSuggestions(): void
    {
        $this->info('💡 Optimization Suggestions:');
        $suggestions = PerformanceMonitoringService::getOptimizationSuggestions();

        foreach ($suggestions as $category => $categoryData) {
            $this->line("  📝 {$category}:");

            if (isset($categoryData['missing_indexes'])) {
                $this->line("    🔍 Missing Indexes:");
                foreach ($categoryData['missing_indexes'] as $suggestion) {
                    $this->line("      - {$suggestion}");
                }
            }

            if (isset($categoryData['query_optimizations'])) {
                $this->line("    ⚡ Query Optimizations:");
                foreach ($categoryData['query_optimizations'] as $suggestion) {
                    $this->line("      - {$suggestion}");
                }
            }

            if (is_array($categoryData) && !isset($categoryData['missing_indexes']) && !isset($categoryData['query_optimizations'])) {
                foreach ($categoryData as $suggestion) {
                    $this->line("      - {$suggestion}");
                }
            }

            $this->newLine();
        }
    }

    private function displayConnectionStats(): void
    {
        $this->info('📊 Database Connection Statistics:');
        $stats = PerformanceMonitoringService::getConnectionStats();

        foreach ($stats as $key => $value) {
            $this->line("  {$key}: {$value}");
        }
    }
}
