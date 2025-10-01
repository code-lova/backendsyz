<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PerformanceMonitoringService
{
    /**
     * Monitor query execution time and log slow queries
     */
    public static function enableQueryLogging(): void
    {
        if (config('app.debug')) {
            DB::listen(function ($query) {
                $time = $query->time;

                // Log queries that take longer than 500ms
                if ($time > 500) {
                    Log::warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $time . 'ms',
                        'request_url' => request()->fullUrl(),
                        'user_id' => Auth::id(),
                    ]);
                }

                // Log queries that take longer than 1000ms as errors
                if ($time > 1000) {
                    Log::error('Very Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $time . 'ms',
                        'request_url' => request()->fullUrl(),
                        'user_id' => Auth::id(),
                        'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                    ]);
                }
            });
        }
    }

    /**
     * Measure execution time of a closure
     */
    public static function measureTime(callable $callback, string $operation = 'Operation'): mixed
    {
        $startTime = microtime(true);

        $result = $callback();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        if ($executionTime > 500) {
            Log::info('Performance Measurement', [
                'operation' => $operation,
                'execution_time' => round($executionTime, 2) . 'ms',
                'request_url' => request()->fullUrl(),
                'user_id' => Auth::id(),
            ]);
        }

        return $result;
    }

    /**
     * Get database connection statistics
     */
    public static function getConnectionStats(): array
    {
        return [
            'active_connections' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_CONNECTION_STATUS),
            'database_name' => DB::connection()->getDatabaseName(),
            'driver' => DB::connection()->getDriverName(),
            'queries_executed' => count(DB::getQueryLog()),
        ];
    }

    /**
     * Analyze table indexes and suggest optimizations
     */
    public static function analyzeTableIndexes(string $tableName): array
    {
        $indexes = collect(DB::select("SHOW INDEX FROM {$tableName}"));

        return [
            'table' => $tableName,
            'total_indexes' => $indexes->count(),
            'unique_indexes' => $indexes->where('Non_unique', 0)->count(),
            'non_unique_indexes' => $indexes->where('Non_unique', 1)->count(),
            'indexes' => $indexes->groupBy('Key_name')->map(function ($group) {
                return [
                    'name' => $group->first()->Key_name,
                    'columns' => $group->pluck('Column_name')->toArray(),
                    'unique' => $group->first()->Non_unique == 0,
                    'type' => $group->first()->Index_type,
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Get slow query suggestions for common patterns
     */
    public static function getOptimizationSuggestions(): array
    {
        return [
            'users_table' => [
                'missing_indexes' => [
                    'Consider adding index on role for role-based queries',
                    'Consider adding index on is_active for account status checks',
                    'Consider adding composite index on (role, email_verified_at) for verified user queries',
                ],
                'query_optimizations' => [
                    'Use select() to limit columns retrieved',
                    'Use whereDate() sparingly - prefer date ranges',
                    'Consider caching frequently accessed user data',
                ],
            ],
            'booking_appts_table' => [
                'missing_indexes' => [
                    'Add index on user_uuid for user-specific queries',
                    'Add index on start_date for date-based filtering',
                    'Add composite index on (user_uuid, start_date) for dashboard queries',
                    'Add index on status for status filtering',
                ],
                'query_optimizations' => [
                    'Use date ranges instead of whereYear/whereMonth',
                    'Select only needed columns',
                    'Consider pagination for large result sets',
                    'Use exists() instead of count() > 0 for existence checks',
                ],
            ],
            'general' => [
                'Enable query logging in development to identify slow queries',
                'Use eager loading to avoid N+1 query problems',
                'Consider using database views for complex recurring queries',
                'Implement query result caching for frequently accessed data',
                'Use database transactions appropriately',
                'Monitor query execution plans in production',
            ],
        ];
    }
}
