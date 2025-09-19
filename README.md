-H 'Content-Type: application/json' \
      -d "{\"text\":\"✅ CoruNest database backup completed successfully\\nFile: $(basename $COMPRESSED_FILE)\\nSize: $BACKUP_SIZE\"}"
fi

echo "Backup process completed at $(date)"
```

#### Database Optimization Script
```php
<?php
// app/Console/Commands/OptimizeDatabase.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeDatabase extends Command
{
    protected $signature = 'db:optimize {--analyze} {--repair} {--vacuum}';
    protected $description = 'Optimize database tables and indexes';

    public function handle()
    {
        $this->info('Starting database optimization...');
        
        $tables = $this->getAllTables();
        $totalTables = count($tables);
        
        $this->withProgressBar($tables, function ($table) {
            $this->optimizeTable($table);
        });
        
        $this->newLine(2);
        $this->info("Optimized {$totalTables} tables successfully");
        
        if ($this->option('analyze')) {
            $this->analyzeDatabase();
        }
        
        if ($this->option('repair')) {
            $this->repairTables($tables);
        }
        
        if ($this->option('vacuum')) {
            $this->vacuumDatabase();
        }
        
        $this->displayDatabaseStats();
        
        return 0;
    }
    
    private function getAllTables(): array
    {
        return DB::select('SHOW TABLES');
    }
    
    private function optimizeTable($table): void
    {
        $tableName = array_values((array) $table)[0];
        
        try {
            DB::statement("OPTIMIZE TABLE `{$tableName}`");
        } catch (\Exception $e) {
            $this->warn("Failed to optimize table {$tableName}: " . $e->getMessage());
        }
    }
    
    private function analyzeDatabase(): void
    {
        $this->info('Analyzing database statistics...');
        
        $tables = $this->getAllTables();
        
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            DB::statement("ANALYZE TABLE `{$tableName}`");
        }
        
        $this->info('Database analysis completed');
    }
    
    private function repairTables(array $tables): void
    {
        $this->info('Checking and repairing tables...');
        
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            
            $result = DB::select("CHECK TABLE `{$tableName}`");
            
            if ($result[0]->Msg_text !== 'OK') {
                $this->warn("Table {$tableName} needs repair");
                DB::statement("REPAIR TABLE `{$tableName}`");
                $this->info("Repaired table {$tableName}");
            }
        }
    }
    
    private function vacuumDatabase(): void
    {
        $this->info('Vacuuming database (removing fragmentation)...');
        
        // Clean up old audit logs
        $deletedAudits = DB::table('audit_logs')
            ->where('created_at', '<', now()->subMonths(6))
            ->delete();
            
        $this->info("Cleaned up {$deletedAudits} old audit log entries");
        
        // Clean up expired sessions
        $deletedSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subDays(7)->timestamp)
            ->delete();
            
        $this->info("Cleaned up {$deletedSessions} expired sessions");
        
        // Clean up failed jobs older than 30 days
        $deletedJobs = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subDays(30))
            ->delete();
            
        $this->info("Cleaned up {$deletedJobs} old failed jobs");
    }
    
    private function displayDatabaseStats(): void
    {
        $this->info('Database Statistics:');
        
        // Table sizes
        $tableSizes = DB::select("
            SELECT 
                table_name AS 'Table',
                round(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ");
        
        $this->table(['Table', 'Size (MB)'], $tableSizes);
        
        // Index usage
        $indexStats = DB::select("
            SELECT 
                table_name,
                COUNT(*) as index_count
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE()
            GROUP BY table_name
            ORDER BY index_count DESC
        ");
        
        $this->info('Index Statistics:');
        $this->table(['Table', 'Index Count'], $indexStats);
    }
}
```

### Performance Monitoring Commands

#### Performance Analysis Command
```php
<?php
// app/Console/Commands/AnalyzePerformance.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AnalyzePerformance extends Command
{
    protected $signature = 'performance:analyze {--days=7} {--export}';
    protected $description = 'Analyze application performance metrics';

    public function handle()
    {
        $days = $this->option('days');
        $export = $this->option('export');
        
        $this->info("Analyzing performance metrics for the last {$days} days...");
        
        $metrics = [
            'slow_queries' => $this->getSlowQueries($days),
            'error_rates' => $this->getErrorRates($days),
            'memory_usage' => $this->getMemoryUsage($days),
            'cache_performance' => $this->getCachePerformance(),
            'queue_performance' => $this->getQueuePerformance($days),
            'response_times' => $this->getResponseTimes($days)
        ];
        
        $this->displayMetrics($metrics);
        
        if ($export) {
            $this->exportMetrics($metrics);
        }
        
        $this->generateRecommendations($metrics);
        
        return 0;
    }
    
    private function getSlowQueries(int $days): array
    {
        // This would typically come from your monitoring system or logs
        // For demo purposes, we'll query the slow query log if available
        
        try {
            $slowQueries = DB::select("
                SELECT 
                    sql_text,
                    avg_timer_wait / 1000000000 as avg_time_seconds,
                    count_star as execution_count
                FROM performance_schema.events_statements_summary_by_digest
                WHERE first_seen > DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND avg_timer_wait > 1000000000
                ORDER BY avg_timer_wait DESC
                LIMIT 10
            ", [$days]);
            
            return $slowQueries;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getErrorRates(int $days): array
    {
        $totalRequests = DB::table('performance_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
            
        $errorRequests = DB::table('performance_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('status_code', '>=', 400)
            ->count();
            
        $errorRate = $totalRequests > 0 ? ($errorRequests / $totalRequests) * 100 : 0;
        
        return [
            'total_requests' => $totalRequests,
            'error_requests' => $errorRequests,
            'error_rate_percent' => round($errorRate, 2)
        ];
    }
    
    private function getMemoryUsage(int $days): array
    {
        $memoryStats = DB::table('performance_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                AVG(memory_usage) as avg_memory,
                MAX(memory_usage) as peak_memory,
                AVG(peak_memory) as avg_peak_memory
            ')
            ->first();
            
        return [
            'avg_memory_mb' => round($memoryStats->avg_memory ?? 0, 2),
            'peak_memory_mb' => round($memoryStats->peak_memory ?? 0, 2),
            'avg_peak_memory_mb' => round($memoryStats->avg_peak_memory ?? 0, 2)
        ];
    }
    
    private function getCachePerformance(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            $hitRate = 0;
            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $total = $info['keyspace_hits'] + $info['keyspace_misses'];
                $hitRate = $total > 0 ? ($info['keyspace_hits'] / $total) * 100 : 0;
            }
            
            return [
                'hit_rate_percent' => round($hitRate, 2),
                'total_keys' => $redis->dbSize(),
                'used_memory_mb' => round(($info['used_memory'] ?? 0) / 1024 / 1024, 2),
                'connected_clients' => $info['connected_clients'] ?? 0
            ];
        } catch (\Exception $e) {
            return ['error' => 'Redis connection failed'];
        }
    }
    
    private function getQueuePerformance(int $days): array
    {
        $queueStats = DB::table('jobs')
            ->selectRaw('
                COUNT(*) as pending_jobs,
                AVG(attempts) as avg_attempts
            ')
            ->first();
            
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subDays($days))
            ->count();
            
        $processedJobs = DB::table('performance_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('context', 'queue_job')
            ->count();
            
        return [
            'pending_jobs' => $queueStats->pending_jobs ?? 0,
            'failed_jobs' => $failedJobs,
            'processed_jobs' => $processedJobs,
            'avg_attempts' => round($queueStats->avg_attempts ?? 0, 2)
        ];
    }
    
    private function getResponseTimes(int $days): array
    {
        $responseStats = DB::table('performance_logs')
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                AVG(request_duration) as avg_response_time,
                MIN(request_duration) as min_response_time,
                MAX(request_duration) as max_response_time,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY request_duration) as p95_response_time
            ')
            ->first();
            
        return [
            'avg_response_time_ms' => round($responseStats->avg_response_time ?? 0, 2),
            'min_response_time_ms' => round($responseStats->min_response_time ?? 0, 2),
            'max_response_time_ms' => round($responseStats->max_response_time ?? 0, 2),
            'p95_response_time_ms' => round($responseStats->p95_response_time ?? 0, 2)
        ];
    }
    
    private function displayMetrics(array $metrics): void
    {
        $this->info('=== PERFORMANCE ANALYSIS REPORT ===');
        $this->newLine();
        
        // Error Rates
        $this->info('📊 Error Rates:');
        $errorData = $metrics['error_rates'];
        $this->line("  Total Requests: {$errorData['total_requests']}");
        $this->line("  Error Requests: {$errorData['error_requests']}");
        $this->line("  Error Rate: {$errorData['error_rate_percent']}%");
        $this->newLine();
        
        // Response Times
        $this->info('⏱️  Response Times:');
        $responseData = $metrics['response_times'];
        $this->line("  Average: {$responseData['avg_response_time_ms']}ms");
        $this->line("  95th Percentile: {$responseData['p95_response_time_ms']}ms");
        $this->line("  Maximum: {$responseData['max_response_time_ms']}ms");
        $this->newLine();
        
        // Memory Usage
        $this->info('🧠 Memory Usage:');
        $memoryData = $metrics['memory_usage'];
        $this->line("  Average Memory: {$memoryData['avg_memory_mb']}MB");
        $this->line("  Peak Memory: {$memoryData['peak_memory_mb']}MB");
        $this->newLine();
        
        // Cache Performance
        $this->info('💾 Cache Performance:');
        $cacheData = $metrics['cache_performance'];
        if (isset($cacheData['error'])) {
            $this->warn("  {$cacheData['error']}");
        } else {
            $this->line("  Hit Rate: {$cacheData['hit_rate_percent']}%");
            $this->line("  Total Keys: {$cacheData['total_keys']}");
            $this->line("  Memory Usage: {$cacheData['used_memory_mb']}MB");
        }
        $this->newLine();
        
        // Queue Performance
        $this->info('📦 Queue Performance:');
        $queueData = $metrics['queue_performance'];
        $this->line("  Pending Jobs: {$queueData['pending_jobs']}");
        $this->line("  Failed Jobs: {$queueData['failed_jobs']}");
        $this->line("  Processed Jobs: {$queueData['processed_jobs']}");
        $this->newLine();
        
        // Slow Queries
        if (!empty($metrics['slow_queries'])) {
            $this->info('🐌 Slow Queries:');
            $this->table(
                ['Query', 'Avg Time (s)', 'Executions'],
                array_map(function($query) {
                    return [
                        substr($query->sql_text, 0, 80) . '...',
                        round($query->avg_time_seconds, 3),
                        $query->execution_count
                    ];
                }, $metrics['slow_queries'])
            );
        }
    }
    
    private function exportMetrics(array $metrics): void
    {
        $filename = 'performance_report_' . date('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('reports/' . $filename);
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, json_encode($metrics, JSON_PRETTY_PRINT));
        
        $this->info("Performance report exported to: {$filepath}");
    }
    
    private function generateRecommendations(array $metrics): void
    {
        $this->newLine();
        $this->info('🔧 RECOMMENDATIONS:');
        
        $recommendations = [];
        
        // Error rate recommendations
        $errorRate = $metrics['error_rates']['error_rate_percent'];
        if ($errorRate > 5) {
            $recommendations[] = "High error rate ({$errorRate}%) - Review error logs and fix failing endpoints";
        }
        
        // Response time recommendations
        $avgResponse = $metrics['response_times']['avg_response_time_ms'];
        if ($avgResponse > 500) {
            $recommendations[] = "Slow average response time ({$avgResponse}ms) - Consider caching, query optimization, or scaling";
        }
        
        // Cache recommendations
        if (isset($metrics['cache_performance']['hit_rate_percent'])) {
            $hitRate = $metrics['cache_performance']['hit_rate_percent'];
            if ($hitRate < 80) {
                $recommendations[] = "Low cache hit rate ({$hitRate}%) - Review caching strategy and cache key patterns";
            }
        }
        
        // Queue recommendations
        $pendingJobs = $metrics['queue_performance']['pending_jobs'];
        if ($pendingJobs > 100) {
            $recommendations[] = "High number of pending jobs ({$pendingJobs}) - Consider adding more queue workers";
        }
        
        $failedJobs = $metrics['queue_performance']['failed_jobs'];
        if ($failedJobs > 10) {
            $recommendations[] = "Multiple failed jobs ({$failedJobs}) - Review failed job logs and fix underlying issues";
        }
        
        // Memory recommendations
        $avgMemory = $metrics['memory_usage']['avg_memory_mb'];
        if ($avgMemory > 128) {
            $recommendations[] = "High memory usage ({$avgMemory}MB) - Review memory-intensive operations and consider optimization";
        }
        
        if (empty($recommendations)) {
            $this->info('✅ No critical performance issues detected!');
        } else {
            foreach ($recommendations as $index => $recommendation) {
                $this->warn(($index + 1) . ". {$recommendation}");
            }
        }
    }
}
```

### System Health Monitoring

#### Health Check Service
```php
<?php
// app/Services/HealthCheckService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckService
{
    private array $checks = [];
    private int $totalChecks = 0;
    private int $passedChecks = 0;

    public function runAllChecks(): array
    {
        $this->checks = [];
        $this->totalChecks = 0;
        $this->passedChecks = 0;

        $this->checkDatabase();
        $this->checkRedis();
        $this->checkStorage();
        $this->checkExternalServices();
        $this->checkQueueWorkers();
        $this->checkCachePerformance();
        $this->checkDiskSpace();
        $this->checkMemoryUsage();
        $this->checkSSLCertificate();

        return [
            'status' => $this->getOverallStatus(),
            'timestamp' => now()->toISOString(),
            'checks' => $this->checks,
            'summary' => [
                'total_checks' => $this->totalChecks,
                'passed_checks' => $this->passedChecks,
                'failed_checks' => $this->totalChecks - $this->passedChecks,
                'success_rate' => round(($this->passedChecks / $this->totalChecks) * 100, 2)
            ]
        ];
    }

    private function checkDatabase(): void
    {
        $this->totalChecks++;
        
        try {
            $start = microtime(true);
            
            // Test connection
            DB::connection()->getPdo();
            
            // Test simple query
            $result = DB::selectOne('SELECT COUNT(*) as count FROM users');
            $userCount = $result->count;
            
            // Test write operation
            DB::table('health_checks')->updateOrInsert(
                ['check_name' => 'database'],
                ['last_run' => now(), 'status' => 'healthy']
            );
            
            $responseTime = (microtime(true) - $start) * 1000;
            
            $this->addCheck('database', 'healthy', [
                'response_time_ms' => round($responseTime, 2),
                'user_count' => $userCount,
                'connection_status' => 'connected'
            ]);
            
            $this->passedChecks++;
            
        } catch (\Exception $e) {
            $this->addCheck('database', 'unhealthy', [
                'error' => $e->getMessage(),
                'connection_status' => 'failed'
            ]);
        }
    }

    private function checkRedis(): void
    {
        $this->totalChecks++;
        
        try {
            $start = microtime(true);
            
            $redis = Redis::connection();
            
            // Test ping
            $pong = $redis->ping();
            
            // Test set/get operations
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value_' . time();
            
            $redis->setex($testKey, 10, $testValue);
            $retrievedValue = $redis->get($testKey);
            $redis->del($testKey);
            
            $responseTime = (microtime(true) - $start) * 1000;
            
            if ($retrievedValue !== $testValue) {
                throw new \Exception('Redis set/get test failed');
            }
            
            $info = $redis->info();
            
            $this->addCheck('redis', 'healthy', [
                'response_time_ms' => round($responseTime, 2),
                'ping_result' => $pong,
                'memory_used_mb' => round(($info['used_memory'] ?? 0) / 1024 / 1024, 2),
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_keys' => $redis->dbSize()
            ]);
            
            $this->passedChecks++;
            
        } catch (\Exception $e) {
            $this->addCheck('redis', 'unhealthy', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkStorage(): void
    {
        $this->totalChecks++;
        
        try {
            $start = microtime(true);
            
            // Test file operations
            $testFile = 'health_check_' . uniqid() . '.txt';
            $testContent = 'Health check test content: ' . time();
            
            // Write test
            Storage::put($testFile, $testContent);
            
            // Read test
            $retrievedContent = Storage::get($testFile);
            
            // Delete test
            Storage::delete($testFile);
            
            $responseTime = (microtime(true) - $start) * 1000;
            
            if ($retrievedContent !== $testContent) {
                throw new \Exception('Storage read/write test failed');
            }
            
            // Check available space
            $storagePath = storage_path();
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercentage = (1 - ($freeSpace / $totalSpace)) * 100;
            
            $this->addCheck('storage', 'healthy', [
                'response_time_ms' => round($responseTime, 2),
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_percentage' => round($usedPercentage, 2),
                'writable' => true
            ]);
            
            $this->passedChecks++;
            
        } catch (\Exception $e) {
            $this->addCheck('storage', 'unhealthy', [
                'error' => $e->getMessage(),
                'writable' => false
            ]);
        }
    }

    private function checkExternalServices(): void
    {
        $services = [
            'yoco' => config('payments.yoco.base_url'),
            'ozow' => config('payments.ozow.base_url'),
            'mailgun' => 'https://api.mailgun.net/v3',
        ];

        foreach ($services as $name => $url) {
            $this->totalChecks++;
            
            try {
                $start = microtime(true);
                
                $response = Http::timeout(10)->get($url);
                $responseTime = (microtime(true) - $start) * 1000;
                
                $status = $response->successful() ? 'healthy' : 'degraded';
                
                $this->addCheck("external_service_{$name}", $status, [
                    'response_time_ms' => round($responseTime, 2),
                    'status_code' => $response->status(),
                    'url' => $url
                ]);
                
                if ($response->successful()) {
                    $this->passedChecks++;
                }
                
            } catch (\Exception $e) {
                $this->addCheck("external_service_{$name}", 'unhealthy', [
                    'error' => $e->getMessage(),
                    'url' => $url
                ]);
            }
        }
    }

    private function checkQueueWorkers(): void
    {
        $this->totalChecks++;
        
        try {
            // Check if Horizon is running
            $horizonStatus = Cache::get('horizon:master:0');
            
            // Check queue sizes
            $queueSizes = [];
            $queues = ['default', 'emails', 'payments', 'reports'];
            
            foreach ($queues as $queue) {
                $size = Redis::connection()->llen("queues:{$queue}");
                $queueSizes[$queue] = $size;
            }
            
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            
            // Check recent job processing
            $recentJobs = DB::table('jobs')
                ->where('created_at', '>', now()->subMinutes(5))
                ->count();
            
            $totalQueueSize = array_sum($queueSizes);
            $status = 'healthy';
            
            if ($totalQueueSize > 1000) {
                $status = 'degraded';
            } elseif ($failedJobs > 50) {
                $status = 'degraded';
            } elseif (!$horizonStatus) {
                $status = 'unhealthy';
            }
            
            $this->addCheck('queue_workers', $status, [
                'horizon_status' => $horizonStatus ? 'running' : 'stopped',
                'queue_sizes' => $queueSizes,
                'total_queue_size' => $totalQueueSize,
                'failed_jobs' => $failedJobs,
                'recent_jobs' => $recentJobs
            ]);
            
            if ($status === 'healthy') {
                $this->passedChecks++;
            }
            
        } catch (\Exception $e) {
            $this->addCheck('queue_workers', 'unhealthy', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkCachePerformance(): void
    {
        $this->totalChecks++;
        
        try {
            $start = microtime(true);
            
            // Test cache operations
            $testKey = 'health_check_cache_' . uniqid();
            $testValue = ['test' => true, 'timestamp' => time()];
            
            Cache::put($testKey, $testValue, 60);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);
            
            $responseTime = (microtime(true) - $start) * 1000;
            
            if ($retrievedValue !== $testValue) {
                throw new \Exception('Cache test failed');
            }
            
            // Get cache statistics
            $redis = Redis::connection();
            $info = $redis->info();
            
            $hitRate = 0;
            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $total = $info['keyspace_hits'] + $info['keyspace_misses'];
                $hitRate = $total > 0 ? ($info['keyspace_hits'] / $total) * 100 : 0;
            }
            
            $status = $hitRate > 70 ? 'healthy' : 'degraded';
            
            $this->addCheck('cache_performance', $status, [
                'response_time_ms' => round($responseTime, 2),
                'hit_rate_percentage' => round($hitRate, 2),
                'total_keys' => $redis->dbSize(),
                'memory_used_mb' => round(($info['used_memory'] ?? 0) / 1024 / 1024, 2)
            ]);
            
            if ($status === 'healthy') {
                $this->passedChecks++;
            }
            
        } catch (\Exception $e) {
            $this->addCheck('cache_performance', 'unhealthy', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkDiskSpace(): void
    {
        $this->totalChecks++;
        
        try {
            $paths = [
                'root' => '/',
                'storage' => storage_path(),
                'logs' => storage_path('logs')
            ];
            
            $diskInfo = [];
            $overallStatus = 'healthy';
            
            foreach ($paths as $name => $path) {
                $freeSpace = disk_free_space($path);
                $totalSpace = disk_total_space($path);
                $usedPercentage = (1 - ($freeSpace / $totalSpace)) * 100;
                
                $diskInfo[$name] = [
                    'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                    'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                    'used_percentage' => round($usedPercentage, 2)
                ];
                
                if ($usedPercentage > 90) {
                    $overallStatus = 'unhealthy';
                } elseif ($usedPercentage > 80) {
                    $overallStatus = 'degraded';
                }
            }
            
            $this->addCheck('disk_space', $overallStatus, $diskInfo);
            
            if ($overallStatus === 'healthy') {
                $this->passedChecks++;
            }
            
        } catch (\Exception $e) {
            $this->addCheck('disk_space', 'unhealthy', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkMemoryUsage(): void
    {
        $this->totalChecks++;
        
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
            
            $usagePercentage = ($memoryUsage / $memoryLimit) * 100;
            $peakPercentage = ($memoryPeak / $memoryLimit) * 100;
            
            $status = 'healthy';
            if ($usagePercentage > 90) {
                $status = 'unhealthy';
            } elseif ($usagePercentage > 75) {
                $status = 'degraded';
            }
            
            $this->addCheck('memory_usage', $status, [
                'current_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'peak_usage_mb' => round($memoryPeak / 1024 / 1024, 2),
                'memory_limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                'usage_percentage' => round($usagePercentage, 2),
                'peak_percentage' => round($peakPercentage, 2)
            ]);
            
            if ($status === 'healthy') {
                $this->passedChecks++;
            }
            
        } catch (\Exception $e) {
            $this->addCheck('memory_usage', 'unhealthy', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkSSLCertificate(): void
    {
        $this->totalChecks++;
        
        try {
            $url = config('app.url');
            $host = parse_url($url, PHP_URL_HOST);
            
            if (!$host || !filter_var($host, FILTER_VALIDATE_DOMAIN)) {
                throw new \Exception('Invalid host configuration');
            }
            
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $socket = stream_socket_client(
                "ssl://{$host}:443",
                $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context
            );
            
            if (!$socket) {
                throw new \Exception("SSL connection failed: {$errstr}");
            }
            
            $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'];
            $certInfo = openssl_x509_parse($cert);
            
            fclose($socket);
            
            $validFrom = date('Y-m-d H:i:s', $certInfo['validFrom_time_t']);
            $validTo = date('Y-m-d H:i:s', $certInfo['validTo_time_t']);
            $daysUntilExpiry = ceil(($certInfo['validTo_time_t'] - time()) / 86400);
            
            $status = 'healthy';
            if ($daysUntilExpiry <= 7) {
                $status = 'unhealthy';
            } elseif ($daysUntilExpiry <= 30) {
                $status = 'degraded';
            }
            
            $this->addCheck('ssl_certificate', $status, [
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'days_until_expiry' => $daysUntilExpiry,
                'issuer' => $certInfo['issuer']['CN'] ?? 'Unknown',
                'subject' => $certInfo['subject']['CN'] ?? 'Unknown'
            ]);
            
            if ($status === 'healthy') {
                $this->passedChecks++;
            }
            
        } catch (\Exception $e) {
            $this->addCheck('ssl_certificate', 'unhealthy', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function addCheck(string $name, string $status, array $details = []): void
    {
        $this->checks[$name] = [
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'details' => $details
        ];
    }

    private function getOverallStatus(): string
    {
        $criticalFailures = 0;
        $degradedServices = 0;
        
        foreach ($this->checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $criticalFailures++;
            } elseif ($check['status'] === 'degraded') {
                $degradedServices++;
            }
        }
        
        if ($criticalFailures > 0) {
            return 'unhealthy';
        } elseif ($degradedServices > 0) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $limit = strtolower($limit);
        $bytes = (int) $limit;
        
        if (strpos($limit, 'k') !== false) {
            $bytes *= 1024;
        } elseif (strpos($limit, 'm') !== false) {
            $bytes *= 1024 * 1024;
        } elseif (strpos($limit, 'g') !== false) {
            $bytes *= 1024 * 1024 * 1024;
        }
        
        return $bytes;
    }
}
```

---

## Development Timeline

### Phase 1: Foundation (Weeks 1-4)
**Goals**: Set up core infrastructure and basic functionality

#### Week 1: Project Setup & Infrastructure
- [x] Initialize Laravel 11 project with Breeze scaffolding
- [x] Configure Docker development environment
- [x] Set up database schemas and migrations
- [x] Configure Redis, queues, and basic caching
- [x] Set up CI/CD pipeline with GitHub Actions
- [x] Configure AWS/cloud infrastructure with Terraform

**Deliverables**:
- Working development environment
- Database with core tables (users, organizations, campaigns, donations, events)
- Basic authentication system
- CI/CD pipeline running tests

#### Week 2: Core Models & API Foundation
- [x] Implement core Eloquent models with relationships
- [x] Create API endpoints for campaigns and donations
- [x] Set up payment service interfaces (Yoco/Ozow)
- [x] Implement basic validation and form requests
- [x] Create seeders for development data

**Deliverables**:
- Complete model layer with relationships
- RESTful API for core resources
- Payment service integration framework
- Development database seeded with test data

#### Week 3: Public Frontend (Alpine.js + Laravel Blade)
- [x] Homepage with featured campaigns
- [x] Campaign listing and detail pages
- [x] Donation flow with payment integration
- [x] Event listing and volunteer signup
- [x] Responsive design implementation

**Deliverables**:
- Public-facing website fully functional
- Payment integration working (test mode)
- Mobile-responsive design
- Basic SEO optimization

#### Week 4: Admin Dashboard Foundation (React.js)
- [x] Dashboard overview with statistics
- [x] Campaign management (CRUD operations)
- [x] Donation management and reporting
- [x] User role management
- [x] Basic analytics charts

**Deliverables**:
- Admin dashboard with core functionality
- Campaign and donation management
- User authentication and authorization
- Basic reporting capabilities

### Phase 2: Advanced Features (Weeks 5-8)
**Goals**: Add advanced functionality and optimize performance

#### Week 5: Advanced Admin Features
- [x] Event management system
- [x] Volunteer management and communication
- [x] Bulk email system with templates
- [x] Advanced analytics and reporting
- [x] Audit logging system

**Deliverables**:
- Complete event management system
- Email marketing capabilities
- Advanced reporting and analytics
- Comprehensive audit trail

#### Week 6: Payment & Financial Features
- [x] Multiple payment provider support
- [x] Refund processing system
- [x] Tax receipt generation (PDF)
- [x] Financial reporting and reconciliation
- [x] Fraud detection and security measures

**Deliverables**:
- Production-ready payment processing
- Tax receipt system
- Financial reporting tools
- Security and fraud protection

#### Week 7: PWA & Mobile Features
- [x] Service worker implementation
- [x] Offline functionality for browsing
- [x] Push notification system
- [x] Capacitor integration for native apps
- [x] Mobile-specific features (camera, location)

**Deliverables**:
- Fully functional PWA
- Offline browsing capabilities
- Push notifications working
- Native mobile app builds

#### Week 8: Performance & Optimization
- [x] Database query optimization
- [x] Caching strategy implementation
- [x] CDN setup and asset optimization
- [x] Performance monitoring tools
- [x] Load testing and optimization

**Deliverables**:
- Optimized performance (sub-500ms response times)
- Comprehensive caching system
- Monitoring and alerting setup
- Load testing results and optimizations

### Phase 3: Testing & Production (Weeks 9-12)
**Goals**: Comprehensive testing, security hardening, and production deployment

#### Week 9: Comprehensive Testing
- [x] Unit test coverage >80%
- [x] Integration testing for payment flows
- [x] End-to-end testing with Playwright
- [x] Performance testing with realistic loads
- [x] Security testing and vulnerability scanning

**Deliverables**:
- Complete test suite passing
- Performance benchmarks met
- Security audit completed
- Documentation for all features

#### Week 10: Security & Compliance
- [x] POPIA compliance implementation
- [x] GDPR readiness
- [x] Security headers and SSL configuration
- [x] Data encryption and privacy controls
- [x] Penetration testing

**Deliverables**:
- POPIA/GDPR compliant data handling
- Security best practices implemented
- Penetration testing report
- Privacy policy and terms of service

#### Week 11: Production Deployment
- [x] Production infrastructure setup
- [x] Database migration and data seeding
- [x] SSL certificates and domain configuration
- [x] Monitoring and logging setup
- [x] Backup and recovery procedures

**Deliverables**:
- Production environment live
- Monitoring dashboards active
- Backup systems operational
- DNS and SSL configured

#### Week 12: Go-Live & Support
- [x] User acceptance testing
- [x] Documentation finalization
- [x] Support system setup
- [x] Launch preparation and marketing
- [x] Post-launch monitoring and fixes

**Deliverables**:
- Platform live and stable
- User documentation complete
- Support processes in place
- Launch marketing materials

---

## Revenue Projections

### Year 1 Financial Model

#### Customer Acquisition Strategy
- **Target Market**: 500 small-medium NGOs in South Africa
- **Acquisition Rate**: 10% (50 NGOs in Year 1)
- **Average Customer Lifetime**: 3+ years
- **Customer Acquisition Cost**: R2,500 per NGO

#### Revenue Breakdown (Year 1)

| Quarter | New NGOs | Total NGOs | Monthly Recurring Revenue | Transaction Volume | Total Revenue |
|---------|----------|------------|--------------------------|-------------------|---------------|
| Q1      | 5        | 5          | R1,495                   | R150,000         | R4,485        |
| Q2      | 15       | 20         | R5,980                   | R400,000         | R17,940       |
| Q3      | 20       | 40         | R11,960                  | R750,000         | R35,880       |
| Q4      | 10       | 50         | R14,950                  | R1,000,000       | R44,850       |

**Year 1 Totals**:
- Annual Recurring Revenue: R179,400
- Transaction Fees: R67,500 (2.5% average)
- Setup Fees: R125,000 (50 × R2,500)
- **Total Year 1 Revenue: R371,900**

#### Cost Structure (Year 1)

| Category | Monthly Cost | Annual Cost | Notes |
|----------|--------------|-------------|-------|
| Development Team | R120,000 | R1,440,000 | 3 developers, 1 designer, 1 PM |
| Infrastructure | R8,500 | R102,000 | AWS, monitoring, security |
| Payment Processing | R1,500 | R18,000 | Provider fees |
| Marketing & Sales | R15,000 | R180,000 | Digital marketing, events |
| Legal & Compliance | R5,000 | R60,000 | Legal fees, audits |
| Operations | R8,000 | R96,000 | Support, admin costs |
| **Total** | **R158,000** | **R1,896,000** | |

**Year 1 Net Result**: -R1,524,100 (Investment phase)

### 5-Year Projection Summary

| Metric | Year 1 | Year 2 | Year 3 | Year 4 | Year 5 |
|--------|--------|--------|--------|--------|--------|
| **Customers** | 50 | 200 | 500 | 1,000 | 1,800 |
| **ARR** | R179k | R1,680k | R4,200k | R8,400k | R15,120k |
| **Transaction Revenue** | R67k | R400k | R1,000k | R2,100k | R3,780k |
| **Total Revenue** | R372k | R2,080k | R5,200k | R10,500k | R18,900k |
| **Total Costs** | R1,896k | R1,768k | R3,640k | R6,825k | R11,340k |
| **Net Profit** | -R1,524k | R312k | R1,560k | R3,675k | R7,560k |
| **Profit Margin** | -410% | 15% | 30% | 35% | 40% |

### Break-even Analysis

**Break-even Point**: Month 20 (Q4 Year 2)
- Monthly recurring revenue needed: R158,000
- Number of customers needed: ~160 NGOs
- Transaction volume needed: R1.2M per month

### Key Performance Indicators (KPIs)

#### Financial KPIs
- **Monthly Recurring Revenue (MRR)**: Track growth rate >15% month-over-month
- **Customer Acquisition Cost (CAC)**: Target <R2,500 per NGO
- **Customer Lifetime Value (CLV)**: Target >R75,000 (3x CAC)
- **Churn Rate**: Target <5% annually
- **Revenue per Customer**: Target R10,500 annually by Year 3

#### Operational KPIs
- **Platform Uptime**: >99.9%
- **Average Response Time**: <300ms
- **Customer Support Response**: <2 hours
- **Payment Success Rate**: >99%
- **User Satisfaction Score**: >8.5/10

#### Growth KPIs
- **Monthly Active NGOs**: Target 80% of total customers
- **Donations Processed**: Target R5M+ by end Year 2
- **Average Donation Size**: Target R150+
- **Repeat Donation Rate**: Target 35%
- **Volunteer Sign-up Rate**: Target 1,000+ monthly by Year 3

### Funding Requirements

#### Seed Round (R2M - Pre-Launch)
- **Product Development**: R1.2M
- **Team Building**: R500k
- **Initial Marketing**: R200k
- **Legal & Compliance**: R100k

#### Series A (R8M - Growth Phase)
- **Market Expansion**: R4M
- **Team Scaling**: R2.5M
- **Technology Enhancement**: R1M
- **Working Capital**: R500k

### Exit Strategy Scenarios

#### Scenario 1: Strategic Acquisition (Year 4-5)
- **Potential Acquirers**: Sage, Xero, PayFast, major banks
- **Valuation Multiple**: 8-12x ARR
- **Estimated Valuation**: R80-150M

#### Scenario 2: Private Equity (Year 3-4)
- **Growth Capital**: R20-30M
- **Valuation**: R60-100M
- **Continue as independent platform**

#### Scenario 3: IPO (Year 5+)
- **Public Listing**: JSE AltX or main board
- **Revenue Target**: R100M+ annually
- **Market Cap**: R500M+

---

## Conclusion

CoruNest represents a comprehensive solution to the NGO management challenge in South Africa, combining modern technology with deep understanding of the nonprofit sector's needs. This detailed development guide provides:

### Technical Excellence
- **Modern Architecture**: Laravel 11 backend with hybrid Alpine.js/React.js frontend
- **Mobile-First**: PWA capabilities with native app support via Capacitor
- **Scalable Infrastructure**: Cloud-native deployment with auto-scaling capabilities
- **Security-Focused**: POPIA/GDPR compliance with enterprise-grade security

### Business Viability
- **Clear Revenue Model**: SaaS subscriptions + transaction fees + value-added services
- **Strong Market Need**: Addressing pain points of 2,000+ South African NGOs
- **Competitive Pricing**: 80% cheaper than existing enterprise solutions
- **Sustainable Growth**: Path to profitability by Year 2

### Implementation Roadmap
- **12-Week Development Timeline**: Structured phases from foundation to production
- **Comprehensive Testing**: Unit, integration, and E2E testing strategies
- **DevOps Excellence**: CI/CD pipelines with automated deployment
- **Monitoring & Maintenance**: Production-ready monitoring and health checks

### Success Metrics
- **Financial Targets**: R18.9M revenue by Year 5 with 40% profit margins
- **Technical Goals**: 99.9% uptime, <300ms response times, 80%+ test coverage
- **User Satisfaction**: >8.5/10 satisfaction score with <5% annual churn

This platform will empower South African NGOs to focus on their missions while providing donors and volunteers with transparent, efficient ways to make a difference. The combination of technical innovation, business acumen, and social impact positions CoruNest as a transformative force in the nonprofit technology sector.

**Ready to build the future of NGO management in South Africa. Let's make a difference, one line of code at a time.**        cache: API_CACHE,
        maxAge: 5 * 60 * 1000 // 5 minutes
    },
    {
        pattern: /^https:\/\/api\.corunest\.com\/events/,
        strategy: CACHE_STRATEGIES.STALE_WHILE_REVALIDATE,
        cache: API_CACHE,
        maxAge: 10 * 60 * 1000 // 10 minutes
    },
    {
        pattern: /\/donate|\/admin/,
        strategy: CACHE_STRATEGIES.NETWORK_ONLY,
        cache: null
    }
];

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE),
            caches.open(DYNAMIC_CACHE),
            caches.open(API_CACHE)
        ]).then(([staticCache, dynamicCache, apiCache]) => {
            // Cache essential static assets
            return staticCache.addAll([
                '/',
                '/offline.html',
                '/manifest.json',
                '/css/app.css',
                '/js/app.js',
                '/images/logo-192.png',
                '/images/logo-512.png'
            ]);
        }).then(() => {
            console.log('Service Worker installed and assets cached');
            self.skipWaiting();
        })
    );
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => 
                        cacheName.startsWith('corunest-') && 
                        !cacheName.includes(CACHE_VERSION)
                    )
                    .map((cacheName) => caches.delete(cacheName))
            );
        }).then(() => {
            console.log('Old caches cleaned up');
            clients.claim();
        })
    );
});

// Fetch event with advanced routing
self.addEventListener('fetch', (event) => {
    const request = event.request;
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip external requests
    if (!request.url.startsWith(self.location.origin) && !request.url.includes('api.corunest.com')) {
        return;
    }

    // Find matching route configuration
    const routeConfig = ROUTE_CONFIGS.find(config => 
        config.pattern.test(request.url)
    );

    if (routeConfig) {
        event.respondWith(handleRequest(request, routeConfig));
    } else {
        // Default strategy for unmatched routes
        event.respondWith(handleRequest(request, {
            strategy: CACHE_STRATEGIES.NETWORK_FIRST,
            cache: DYNAMIC_CACHE,
            maxAge: 60 * 1000 // 1 minute
        }));
    }
});

// Request handling based on strategy
async function handleRequest(request, config) {
    switch (config.strategy) {
        case CACHE_STRATEGIES.CACHE_FIRST:
            return cacheFirst(request, config);
        case CACHE_STRATEGIES.NETWORK_FIRST:
            return networkFirst(request, config);
        case CACHE_STRATEGIES.STALE_WHILE_REVALIDATE:
            return staleWhileRevalidate(request, config);
        case CACHE_STRATEGIES.NETWORK_ONLY:
            return networkOnly(request);
        case CACHE_STRATEGIES.CACHE_ONLY:
            return cacheOnly(request, config);
        default:
            return fetch(request);
    }
}

// Cache-first strategy
async function cacheFirst(request, config) {
    const cache = await caches.open(config.cache);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse && !isExpired(cachedResponse, config.maxAge)) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.status === 200) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// Network-first strategy
async function networkFirst(request, config) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.status === 200 && config.cache) {
            const cache = await caches.open(config.cache);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        if (config.cache) {
            const cache = await caches.open(config.cache);
            const cachedResponse = await cache.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
        }
        
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            const cache = await caches.open(STATIC_CACHE);
            return cache.match('/offline.html');
        }
        
        throw error;
    }
}

// Stale-while-revalidate strategy
async function staleWhileRevalidate(request, config) {
    const cache = await caches.open(config.cache);
    const cachedResponse = await cache.match(request);
    
    // Start network request in background
    const networkResponsePromise = fetch(request)
        .then(response => {
            if (response.status === 200) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => null);
    
    // Return cached response immediately if available and not expired
    if (cachedResponse && !isExpired(cachedResponse, config.maxAge)) {
        networkResponsePromise; // Let it update in background
        return cachedResponse;
    }
    
    // Otherwise wait for network response
    return networkResponsePromise || cachedResponse || 
           caches.match('/offline.html');
}

// Network-only strategy
async function networkOnly(request) {
    return fetch(request);
}

// Cache-only strategy
async function cacheOnly(request, config) {
    const cache = await caches.open(config.cache);
    return cache.match(request) || 
           caches.match('/offline.html');
}

// Check if cached response is expired
function isExpired(response, maxAge) {
    if (!maxAge) return false;
    
    const cachedDate = new Date(response.headers.get('date'));
    const expiryDate = new Date(cachedDate.getTime() + maxAge);
    
    return Date.now() > expiryDate.getTime();
}

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    switch (event.tag) {
        case 'sync-donations':
            event.waitUntil(syncOfflineDonations());
            break;
        case 'sync-volunteer-signups':
            event.waitUntil(syncVolunteerSignups());
            break;
        case 'sync-analytics':
            event.waitUntil(syncAnalytics());
            break;
    }
});

// Sync offline donations
async function syncOfflineDonations() {
    try {
        const db = await openDB();
        const donations = await getAllOfflineDonations(db);
        
        for (const donation of donations) {
            try {
                const response = await fetch('/api/donations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': donation.csrfToken
                    },
                    body: JSON.stringify(donation.data)
                });
                
                if (response.ok) {
                    await removeOfflineDonation(db, donation.id);
                    
                    // Notify user of successful sync
                    self.registration.showNotification('Donation Processed', {
                        body: `Your offline donation of R${donation.data.amount} has been processed.`,
                        icon: '/images/logo-192.png',
                        tag: 'donation-sync'
                    });
                }
            } catch (error) {
                console.error('Failed to sync donation:', error);
                // Increment retry count
                await incrementRetryCount(db, donation.id);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Push notification handling
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/images/logo-192.png',
        badge: '/images/badge-72.png',
        image: data.image,
        vibrate: [200, 100, 200],
        requireInteraction: data.requireInteraction || false,
        data: {
            url: data.url,
            action: data.action,
            timestamp: Date.now()
        },
        actions: []
    };
    
    // Add contextual actions based on notification type
    switch (data.type) {
        case 'donation_received':
            options.actions = [
                {
                    action: 'view_campaign',
                    title: 'View Campaign',
                    icon: '/images/actions/view.png'
                },
                {
                    action: 'thank_donor',
                    title: 'Thank Donor',
                    icon: '/images/actions/heart.png'
                }
            ];
            break;
        case 'volunteer_signup':
            options.actions = [
                {
                    action: 'view_event',
                    title: 'View Event',
                    icon: '/images/actions/calendar.png'
                },
                {
                    action: 'contact_volunteer',
                    title: 'Contact',
                    icon: '/images/actions/message.png'
                }
            ];
            break;
        case 'campaign_milestone':
            options.actions = [
                {
                    action: 'share_success',
                    title: 'Share',
                    icon: '/images/actions/share.png'
                },
                {
                    action: 'view_analytics',
                    title: 'Analytics',
                    icon: '/images/actions/chart.png'
                }
            ];
            break;
    }
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    const action = event.action;
    const data = event.notification.data;
    
    let url = '/';
    
    switch (action) {
        case 'view_campaign':
            url = data.url || '/campaigns';
            break;
        case 'view_event':
            url = data.url || '/events';
            break;
        case 'view_analytics':
            url = '/admin/analytics';
            break;
        case 'thank_donor':
            url = `/admin/donations/${data.donation_id}/thank`;
            break;
        case 'contact_volunteer':
            url = `/admin/volunteers/${data.volunteer_id}/contact`;
            break;
        case 'share_success':
            // Handle sharing
            if (navigator.share) {
                navigator.share({
                    title: 'CoruNest Campaign Success',
                    text: event.notification.body,
                    url: data.url
                });
            }
            return;
        default:
            url = data.url || '/';
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            // Check if there's already a window open
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.focus();
                    client.postMessage({ action: 'navigate', url: url });
                    return;
                }
            }
            
            // Open new window if none exists
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// IndexedDB helpers
async function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('CoruNestDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            if (!db.objectStoreNames.contains('offlineDonations')) {
                const store = db.createObjectStore('offlineDonations', {
                    keyPath: 'id',
                    autoIncrement: true
                });
                store.createIndex('timestamp', 'timestamp');
                store.createIndex('retryCount', 'retryCount');
            }
        };
    });
}

// Periodic background cleanup
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'cleanup') {
        event.waitUntil(performCleanup());
    }
});

async function performCleanup() {
    // Clean up old cache entries
    const caches_to_clean = [API_CACHE, DYNAMIC_CACHE];
    
    for (const cacheName of caches_to_clean) {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();
        
        for (const request of requests) {
            const response = await cache.match(request);
            if (isExpired(response, 24 * 60 * 60 * 1000)) { // 24 hours
                cache.delete(request);
            }
        }
    }
    
    // Clean up old IndexedDB entries
    const db = await openDB();
    const transaction = db.transaction(['offlineDonations'], 'readwrite');
    const store = transaction.objectStore('offlineDonations');
    const index = store.index('timestamp');
    
    const cutoffTime = Date.now() - (7 * 24 * 60 * 60 * 1000); // 7 days ago
    const range = IDBKeyRange.upperBound(cutoffTime);
    
    index.openCursor(range).onsuccess = (event) => {
        const cursor = event.target.result;
        if (cursor) {
            store.delete(cursor.primaryKey);
            cursor.continue();
        }
    };
}
```

### Capacitor Native Features Integration

#### Camera and File Upload Integration
```typescript
// resources/js/native/camera.ts
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { Filesystem, Directory, Encoding } from '@capacitor/filesystem';
import { Capacitor } from '@capacitor/core';

export class CameraService {
    async takePicture(options: {
        source?: CameraSource;
        quality?: number;
        allowEditing?: boolean;
    } = {}): Promise<string | null> {
        try {
            if (!Capacitor.isPluginAvailable('Camera')) {
                throw new Error('Camera not available on this platform');
            }

            const image = await Camera.getPhoto({
                quality: options.quality || 90,
                allowEditing: options.allowEditing || true,
                resultType: CameraResultType.DataUrl,
                source: options.source || CameraSource.Prompt,
                width: 1024,
                height: 1024,
                correctOrientation: true
            });

            return image.dataUrl || null;
        } catch (error) {
            console.error('Error taking picture:', error);
            throw error;
        }
    }

    async selectMultipleImages(): Promise<string[]> {
        try {
            const images: string[] = [];
            
            // For multiple images, we need to call the camera multiple times
            // or use a native plugin for gallery selection
            const image = await this.takePicture({
                source: CameraSource.Photos
            });
            
            if (image) {
                images.push(image);
            }
            
            return images;
        } catch (error) {
            console.error('Error selecting images:', error);
            return [];
        }
    }

    async compressAndUpload(dataUrl: string, campaignId: number): Promise<string> {
        try {
            // Convert data URL to blob
            const blob = this.dataUrlToBlob(dataUrl);
            
            // Compress image
            const compressedBlob = await this.compressImage(blob);
            
            // Create form data
            const formData = new FormData();
            formData.append('image', compressedBlob, 'campaign-image.jpg');
            formData.append('campaign_id', campaignId.toString());
            
            // Upload to server
            const response = await fetch('/api/upload/campaign-image', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Upload failed');
            }
            
            const result = await response.json();
            return result.url;
        } catch (error) {
            console.error('Error uploading image:', error);
            throw error;
        }
    }

    private dataUrlToBlob(dataUrl: string): Blob {
        const arr = dataUrl.split(',');
        const mime = arr[0].match(/:(.*?);/)?.[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        
        return new Blob([u8arr], { type: mime });
    }

    private async compressImage(blob: Blob, maxWidth = 1024, quality = 0.8): Promise<Blob> {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d')!;
            const img = new Image();
            
            img.onload = () => {
                const { width, height } = img;
                const ratio = Math.min(maxWidth / width, maxWidth / height);
                
                canvas.width = width * ratio;
                canvas.height = height * ratio;
                
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                canvas.toBlob(resolve as BlobCallback, 'image/jpeg', quality);
            };
            
            img.src = URL.createObjectURL(blob);
        });
    }
}
```

#### Geolocation for Event Check-in
```typescript
// resources/js/native/geolocation.ts
import { Geolocation, Position } from '@capacitor/geolocation';
import { Capacitor } from '@capacitor/core';

export class LocationService {
    async getCurrentLocation(): Promise<Position | null> {
        try {
            if (!Capacitor.isPluginAvailable('Geolocation')) {
                throw new Error('Geolocation not available on this platform');
            }

            const coordinates = await Geolocation.getCurrentPosition({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000 // 5 minutes
            });

            return coordinates;
        } catch (error) {
            console.error('Error getting location:', error);
            return null;
        }
    }

    async watchLocation(callback: (position: Position) => void): Promise<string> {
        const watchId = await Geolocation.watchPosition({
            enableHighAccuracy: true,
            timeout: 10000
        }, callback);

        return watchId;
    }

    async clearWatch(watchId: string): Promise<void> {
        await Geolocation.clearWatch({ id: watchId });
    }

    calculateDistance(
        lat1: number, 
        lon1: number, 
        lat2: number, 
        lon2: number
    ): number {
        const R = 6371; // Earth's radius in kilometers
        const dLat = this.toRad(lat2 - lat1);
        const dLon = this.toRad(lon2 - lon1);
        
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    private toRad(deg: number): number {
        return deg * (Math.PI / 180);
    }

    async isWithinEventRadius(
        eventLat: number, 
        eventLon: number, 
        radiusKm: number = 0.1
    ): Promise<boolean> {
        const position = await this.getCurrentLocation();
        
        if (!position) {
            return false;
        }
        
        const distance = this.calculateDistance(
            position.coords.latitude,
            position.coords.longitude,
            eventLat,
            eventLon
        );
        
        return distance <= radiusKm;
    }

    async checkInToEvent(eventId: number): Promise<boolean> {
        try {
            const position = await this.getCurrentLocation();
            
            if (!position) {
                throw new Error('Could not get current location');
            }
            
            const response = await fetch(`/api/events/${eventId}/checkin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: Date.now()
                })
            });
            
            return response.ok;
        } catch (error) {
            console.error('Error checking in to event:', error);
            return false;
        }
    }
}
```

#### Native Sharing Integration
```typescript
// resources/js/native/sharing.ts
import { Share } from '@capacitor/share';
import { Capacitor } from '@capacitor/core';

export class SharingService {
    async shareCampaign(campaign: {
        title: string;
        description: string;
        url: string;
        image?: string;
    }): Promise<boolean> {
        try {
            if (Capacitor.isPluginAvailable('Share')) {
                await Share.share({
                    title: `Support: ${campaign.title}`,
                    text: campaign.description,
                    url: campaign.url,
                    dialogTitle: 'Share Campaign'
                });
                return true;
            } else if (navigator.share) {
                // Web Share API fallback
                await navigator.share({
                    title: `Support: ${campaign.title}`,
                    text: campaign.description,
                    url: campaign.url
                });
                return true;
            } else {
                // Manual sharing fallback
                this.fallbackShare(campaign);
                return true;
            }
        } catch (error) {
            console.error('Error sharing campaign:', error);
            return false;
        }
    }

    async shareEventInvitation(event: {
        title: string;
        description: string;
        date: string;
        location: string;
        url: string;
    }): Promise<boolean> {
        const shareText = `Join me at "${event.title}" on ${event.date} at ${event.location}. ${event.description}`;
        
        try {
            if (Capacitor.isPluginAvailable('Share')) {
                await Share.share({
                    title: `Event Invitation: ${event.title}`,
                    text: shareText,
                    url: event.url,
                    dialogTitle: 'Share Event'
                });
                return true;
            } else if (navigator.share) {
                await navigator.share({
                    title: `Event Invitation: ${event.title}`,
                    text: shareText,
                    url: event.url
                });
                return true;
            } else {
                this.fallbackShare({ 
                    title: event.title, 
                    description: shareText, 
                    url: event.url 
                });
                return true;
            }
        } catch (error) {
            console.error('Error sharing event:', error);
            return false;
        }
    }

    private fallbackShare(content: { title: string; description: string; url: string }): void {
        // Create sharing modal or copy to clipboard
        const shareModal = document.createElement('div');
        shareModal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
        shareModal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">Share</h3>
                <div class="space-y-3">
                    <button onclick="this.shareVia('whatsapp')" class="w-full flex items-center p-3 bg-green-500 text-white rounded-lg hover:bg-green-600">
                        <span class="mr-3">📱</span> WhatsApp
                    </button>
                    <button onclick="this.shareVia('facebook')" class="w-full flex items-center p-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <span class="mr-3">📘</span> Facebook
                    </button>
                    <button onclick="this.shareVia('twitter')" class="w-full flex items-center p-3 bg-blue-400 text-white rounded-lg hover:bg-blue-500">
                        <span class="mr-3">🐦</span> Twitter
                    </button>
                    <button onclick="this.copyLink()" class="w-full flex items-center p-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <span class="mr-3">📋</span> Copy Link
                    </button>
                </div>
                <button onclick="this.remove()" class="w-full mt-4 p-2 text-gray-600 hover:text-gray-800">Cancel</button>
            </div>
        `;

        // Add methods to the modal
        Object.assign(shareModal, {
            shareVia: (platform: string) => {
                const encodedTitle = encodeURIComponent(content.title);
                const encodedText = encodeURIComponent(content.description);
                const encodedUrl = encodeURIComponent(content.url);

                let shareUrl = '';
                switch (platform) {
                    case 'whatsapp':
                        shareUrl = `https://wa.me/?text=${encodedText}%20${encodedUrl}`;
                        break;
                    case 'facebook':
                        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}&quote=${encodedText}`;
                        break;
                    case 'twitter':
                        shareUrl = `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`;
                        break;
                }

                if (shareUrl) {
                    window.open(shareUrl, '_blank');
                }
                shareModal.remove();
            },
            copyLink: async () => {
                try {
                    await navigator.clipboard.writeText(content.url);
                    alert('Link copied to clipboard!');
                } catch (error) {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = content.url;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('Link copied to clipboard!');
                }
                shareModal.remove();
            }
        });

        document.body.appendChild(shareModal);
    }

    async shareSuccess(donation: {
        amount: number;
        campaignTitle: string;
        receiptUrl: string;
    }): Promise<boolean> {
        const shareText = `I just donated R${donation.amount} to "${donation.campaignTitle}" through CoruNest! Every contribution makes a difference. 🙏`;
        
        return this.shareCampaign({
            title: 'I made a difference!',
            description: shareText,
            url: donation.receiptUrl
        });
    }
}
```

---

## Maintenance & Operations

### Database Maintenance Scripts

#### Automated Backup System
```bash
#!/bin/bash
# scripts/backup.sh

set -e

# Configuration
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_DATABASE:-corunest}"
DB_USER="${DB_USERNAME:-corunest}"
DB_PASS="${DB_PASSWORD}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/corunest}"
S3_BUCKET="${BACKUP_S3_BUCKET:-corunest-backups}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Generate backup filename
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/corunest_${TIMESTAMP}.sql"
COMPRESSED_FILE="$BACKUP_FILE.gz"

echo "Starting database backup at $(date)"

# Create database dump
mysqldump \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --add-drop-table \
  --extended-insert \
  --quick \
  --lock-tables=false \
  "$DB_NAME" > "$BACKUP_FILE"

# Compress backup
gzip "$BACKUP_FILE"

echo "Database backup completed: $COMPRESSED_FILE"

# Upload to S3 if configured
if [ -n "$S3_BUCKET" ] && [ -n "$AWS_ACCESS_KEY_ID" ]; then
    echo "Uploading backup to S3..."
    aws s3 cp "$COMPRESSED_FILE" "s3://$S3_BUCKET/database/$(basename $COMPRESSED_FILE)"
    echo "S3 upload completed"
fi

# Clean up local backups older than retention period
find "$BACKUP_DIR" -name "corunest_*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Create backup report
BACKUP_SIZE=$(du -h "$COMPRESSED_FILE" | cut -f1)
echo "Backup Summary:"
echo "  File: $(basename $COMPRESSED_FILE)"
echo "  Size: $BACKUP_SIZE"
echo "  Location: $COMPRESSED_FILE"

# Send notification
if [ -n "$SLACK_WEBHOOK_URL" ]; then
    curl -X POST "$SLACK_WEBHOOK_URL" \
      -H 'Content-Type: application/json        webhook_url: ${{ secrets.SLACK_WEBHOOK_URL }}
        fields: repo,message,commit,author,action,eventName,ref,workflow

  performance-test:
    name: Performance Testing
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Run Lighthouse CI
      uses: treosh/lighthouse-ci-action@v9
      with:
        configPath: '.lighthouserc.json'
        uploadArtifacts: true
        temporaryPublicStorage: true

    - name: Run k6 load tests
      uses: grafana/k6-action@v0.3.0
      with:
        filename: tests/performance/load-test.js
      env:
        K6_CLOUD_TOKEN: ${{ secrets.K6_CLOUD_TOKEN }}
```

### Deployment Scripts

#### Production Deployment Script
```bash
#!/bin/bash
# scripts/deploy.sh

set -e

echo "🚀 Starting CoruNest deployment..."

# Configuration
DEPLOY_PATH="/var/www/corunest"
BACKUP_PATH="/var/backups/corunest"
RELEASE_PATH="$DEPLOY_PATH/releases/$(date +%Y%m%d_%H%M%S)"
SHARED_PATH="$DEPLOY_PATH/shared"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as correct user
if [ "$USER" != "deploy" ]; then
    print_error "This script must be run as the 'deploy' user"
    exit 1
fi

# Create necessary directories
print_status "Creating deployment directories..."
mkdir -p "$RELEASE_PATH"
mkdir -p "$SHARED_PATH"
mkdir -p "$BACKUP_PATH"

# Download and extract release
print_status "Downloading release artifact..."
VERSION=${1:-latest}
aws s3 cp "s3://$S3_BUCKET/releases/corunest-$VERSION.tar.gz" /tmp/
tar -xzf "/tmp/corunest-$VERSION.tar.gz" -C "$RELEASE_PATH"

# Create shared directories if they don't exist
print_status "Setting up shared directories..."
mkdir -p "$SHARED_PATH/storage/app"
mkdir -p "$SHARED_PATH/storage/framework/cache"
mkdir -p "$SHARED_PATH/storage/framework/sessions"
mkdir -p "$SHARED_PATH/storage/framework/views"
mkdir -p "$SHARED_PATH/storage/logs"
mkdir -p "$SHARED_PATH/bootstrap/cache"

# Set permissions for shared storage
chmod -R 775 "$SHARED_PATH/storage"
chmod -R 775 "$SHARED_PATH/bootstrap/cache"

# Link shared directories
print_status "Linking shared directories..."
rm -rf "$RELEASE_PATH/storage"
rm -rf "$RELEASE_PATH/bootstrap/cache"
ln -nfs "$SHARED_PATH/storage" "$RELEASE_PATH/storage"
ln -nfs "$SHARED_PATH/bootstrap/cache" "$RELEASE_PATH/bootstrap/cache"

# Link environment file
ln -nfs "$SHARED_PATH/.env" "$RELEASE_PATH/.env"

# Install/update Composer dependencies for production
print_status "Installing Composer dependencies..."
cd "$RELEASE_PATH"
composer install --no-dev --optimize-autoloader --no-interaction

# Create database backup before migration
print_status "Creating database backup..."
BACKUP_FILE="$BACKUP_PATH/db_backup_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_FILE"

# Run database migrations
print_status "Running database migrations..."
php artisan migrate --force --no-interaction

# Clear and cache configurations
print_status "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Queue restart
print_status "Restarting queue workers..."
php artisan queue:restart

# Update symlink to new release
print_status "Activating new release..."
CURRENT_PATH="$DEPLOY_PATH/current"
PREVIOUS_PATH="$DEPLOY_PATH/previous"

# Backup current symlink
if [ -L "$CURRENT_PATH" ]; then
    rm -f "$PREVIOUS_PATH"
    mv "$CURRENT_PATH" "$PREVIOUS_PATH"
fi

# Create new symlink
ln -nfs "$RELEASE_PATH" "$CURRENT_PATH"

# Reload PHP-FPM
print_status "Reloading PHP-FPM..."
sudo systemctl reload php8.2-fpm

# Reload Nginx
print_status "Reloading Nginx..."
sudo systemctl reload nginx

# Run post-deployment tests
print_status "Running post-deployment health checks..."
if curl -f -s "$APP_URL/health" > /dev/null; then
    print_status "✅ Health check passed"
else
    print_error "❌ Health check failed"
    print_warning "Rolling back to previous release..."
    
    # Rollback
    if [ -L "$PREVIOUS_PATH" ]; then
        rm -f "$CURRENT_PATH"
        mv "$PREVIOUS_PATH" "$CURRENT_PATH"
        sudo systemctl reload php8.2-fpm
        sudo systemctl reload nginx
        print_warning "Rollback completed"
    fi
    exit 1
fi

# Clean up old releases (keep last 5)
print_status "Cleaning up old releases..."
cd "$DEPLOY_PATH/releases"
ls -t | tail -n +6 | xargs rm -rf

# Clean up old backups (keep last 10)
print_status "Cleaning up old backups..."
cd "$BACKUP_PATH"
ls -t *.sql | tail -n +11 | xargs rm -f

print_status "🎉 Deployment completed successfully!"

# Send notification
curl -X POST "$SLACK_WEBHOOK_URL" \
  -H 'Content-Type: application/json' \
  -d "{\"text\":\"✅ CoruNest deployed successfully to production (version: $VERSION)\"}"
```

#### Zero-Downtime Deployment with Health Checks
```bash
#!/bin/bash
# scripts/zero-downtime-deploy.sh

set -e

# Configuration
DEPLOY_USER="deploy"
SERVERS=("server1.corunest.com" "server2.corunest.com")
HEALTH_CHECK_URL="/api/health"
LOAD_BALANCER_API="https://api.cloudflare.com/client/v4"
ZONE_ID="$CLOUDFLARE_ZONE_ID"

# Function to remove server from load balancer
remove_from_lb() {
    local server_ip=$1
    print_status "Removing $server_ip from load balancer..."
    
    curl -X PATCH "$LOAD_BALANCER_API/zones/$ZONE_ID/load_balancers/$LB_ID/pools/$POOL_ID" \
      -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
      -H "Content-Type: application/json" \
      -d "{\"origins\":[{\"name\":\"backup\",\"address\":\"backup.corunest.com\",\"enabled\":true}]}"
}

# Function to add server back to load balancer
add_to_lb() {
    local server_ip=$1
    print_status "Adding $server_ip back to load balancer..."
    
    curl -X PATCH "$LOAD_BALANCER_API/zones/$ZONE_ID/load_balancers/$LB_ID/pools/$POOL_ID" \
      -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
      -H "Content-Type: application/json" \
      -d "{\"origins\":[{\"name\":\"server1\",\"address\":\"$server_ip\",\"enabled\":true},{\"name\":\"backup\",\"address\":\"backup.corunest.com\",\"enabled\":true}]}"
}

# Function to check server health
check_health() {
    local server=$1
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s "https://$server$HEALTH_CHECK_URL" > /dev/null; then
            print_status "✅ $server is healthy"
            return 0
        fi
        
        print_status "⏳ Waiting for $server to become healthy (attempt $attempt/$max_attempts)..."
        sleep 10
        ((attempt++))
    done
    
    print_error "❌ $server failed health check"
    return 1
}

# Deploy to each server one by one
for server in "${SERVERS[@]}"; do
    print_status "🚀 Deploying to $server..."
    
    # Remove from load balancer
    remove_from_lb "$server"
    
    # Wait for connections to drain
    sleep 30
    
    # Deploy to server
    ssh "$DEPLOY_USER@$server" "bash -s" < scripts/deploy.sh "$VERSION"
    
    # Check health
    if check_health "$server"; then
        # Add back to load balancer
        add_to_lb "$server"
        print_status "✅ Successfully deployed to $server"
    else
        print_error "❌ Deployment to $server failed"
        # Add back to load balancer anyway to prevent total outage
        add_to_lb "$server"
        exit 1
    fi
    
    # Wait before deploying to next server
    sleep 60
done

print_status "🎉 Zero-downtime deployment completed successfully!"
```

---

## Deployment & Infrastructure

### Docker Configuration

#### Production Dockerfile
```dockerfile
# Dockerfile
FROM php:8.2-fpm-alpine as base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    mysql-client \
    redis \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    bcmath \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Configure Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Configure Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies and build assets
RUN npm ci && npm run build && rm -rf node_modules

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

#### Docker Compose for Production
```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    volumes:
      - storage_data:/var/www/html/storage
      - ./docker/nginx/ssl:/etc/nginx/ssl
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    networks:
      - corunest

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/prod.conf:/etc/nginx/conf.d/default.conf
      - ./docker/nginx/ssl:/etc/nginx/ssl
      - storage_data:/var/www/html/storage
    depends_on:
      - app
    networks:
      - corunest

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/conf.d/my.cnf
    networks:
      - corunest

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
      - ./docker/redis/redis.conf:/etc/redis/redis.conf
    command: redis-server /etc/redis/redis.conf
    networks:
      - corunest

  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.8.0
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data
    networks:
      - corunest

  queue:
    build:
      context: .
      dockerfile: Dockerfile
    command: php artisan horizon
    volumes:
      - storage_data:/var/www/html/storage
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    networks:
      - corunest

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    command: php artisan schedule:work
    volumes:
      - storage_data:/var/www/html/storage
    environment:
      - APP_ENV=production
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis
    networks:
      - corunest

volumes:
  mysql_data:
  redis_data:
  elasticsearch_data:
  storage_data:

networks:
  corunest:
    driver: bridge
```

### Infrastructure as Code (Terraform)

#### AWS Infrastructure
```hcl
# infrastructure/main.tf
terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
  
  backend "s3" {
    bucket = "corunest-terraform-state"
    key    = "production/terraform.tfstate"
    region = "af-south-1"
  }
}

provider "aws" {
  region = var.aws_region
}

# VPC Configuration
resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name        = "corunest-vpc"
    Environment = var.environment
  }
}

# Internet Gateway
resource "aws_internet_gateway" "main" {
  vpc_id = aws_vpc.main.id

  tags = {
    Name = "corunest-igw"
  }
}

# Public Subnets
resource "aws_subnet" "public" {
  count = 2

  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.${count.index + 1}.0/24"
  availability_zone       = data.aws_availability_zones.available.names[count.index]
  map_public_ip_on_launch = true

  tags = {
    Name = "corunest-public-subnet-${count.index + 1}"
    Type = "public"
  }
}

# Private Subnets
resource "aws_subnet" "private" {
  count = 2

  vpc_id            = aws_vpc.main.id
  cidr_block        = "10.0.${count.index + 10}.0/24"
  availability_zone = data.aws_availability_zones.available.names[count.index]

  tags = {
    Name = "corunest-private-subnet-${count.index + 1}"
    Type = "private"
  }
}

# Application Load Balancer
resource "aws_lb" "main" {
  name               = "corunest-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = aws_subnet.public[*].id

  enable_deletion_protection = true

  tags = {
    Environment = var.environment
  }
}

# Auto Scaling Group
resource "aws_autoscaling_group" "app" {
  name                = "corunest-asg"
  vpc_zone_identifier = aws_subnet.private[*].id
  target_group_arns   = [aws_lb_target_group.app.arn]
  health_check_type   = "ELB"
  health_check_grace_period = 300

  min_size         = 2
  max_size         = 10
  desired_capacity = 2

  launch_template {
    id      = aws_launch_template.app.id
    version = "$Latest"
  }

  tag {
    key                 = "Name"
    value               = "corunest-app-server"
    propagate_at_launch = true
  }
}

# RDS Database
resource "aws_db_instance" "main" {
  identifier = "corunest-db"

  engine         = "mysql"
  engine_version = "8.0"
  instance_class = "db.t3.medium"

  allocated_storage     = 100
  max_allocated_storage = 1000
  storage_type          = "gp2"
  storage_encrypted     = true

  db_name  = var.db_name
  username = var.db_username
  password = var.db_password

  vpc_security_group_ids = [aws_security_group.rds.id]
  db_subnet_group_name   = aws_db_subnet_group.main.name

  backup_retention_period = 7
  backup_window          = "03:00-04:00"
  maintenance_window     = "sun:04:00-sun:05:00"

  skip_final_snapshot = false
  final_snapshot_identifier = "corunest-final-snapshot-${formatdate("YYYY-MM-DD-hhmm", timestamp())}"

  tags = {
    Name        = "corunest-database"
    Environment = var.environment
  }
}

# ElastiCache Redis
resource "aws_elasticache_subnet_group" "main" {
  name       = "corunest-cache-subnet"
  subnet_ids = aws_subnet.private[*].id
}

resource "aws_elasticache_cluster" "redis" {
  cluster_id           = "corunest-redis"
  engine               = "redis"
  node_type            = "cache.t3.micro"
  num_cache_nodes      = 1
  parameter_group_name = "default.redis7"
  port                 = 6379
  subnet_group_name    = aws_elasticache_subnet_group.main.name
  security_group_ids   = [aws_security_group.redis.id]

  tags = {
    Name        = "corunest-redis"
    Environment = var.environment
  }
}

# S3 Buckets
resource "aws_s3_bucket" "storage" {
  bucket = "corunest-storage-${var.environment}"

  tags = {
    Name        = "corunest-storage"
    Environment = var.environment
  }
}

resource "aws_s3_bucket" "backups" {
  bucket = "corunest-backups-${var.environment}"

  tags = {
    Name        = "corunest-backups"
    Environment = var.environment
  }
}

# CloudFront Distribution
resource "aws_cloudfront_distribution" "main" {
  origin {
    domain_name = aws_lb.main.dns_name
    origin_id   = "ALB-${aws_lb.main.name}"

    custom_origin_config {
      http_port              = 80
      https_port             = 443
      origin_protocol_policy = "https-only"
      origin_ssl_protocols   = ["TLSv1.2"]
    }
  }

  enabled             = true
  is_ipv6_enabled     = true
  default_root_object = "index.php"

  aliases = [var.domain_name, "www.${var.domain_name}"]

  default_cache_behavior {
    allowed_methods        = ["DELETE", "GET", "HEAD", "OPTIONS", "PATCH", "POST", "PUT"]
    cached_methods         = ["GET", "HEAD"]
    target_origin_id       = "ALB-${aws_lb.main.name}"
    compress               = true
    viewer_protocol_policy = "redirect-to-https"

    forwarded_values {
      query_string = true
      headers      = ["*"]

      cookies {
        forward = "all"
      }
    }

    min_ttl     = 0
    default_ttl = 0
    max_ttl     = 31536000
  }

  price_class = "PriceClass_100"

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    acm_certificate_arn = aws_acm_certificate_validation.main.certificate_arn
    ssl_support_method  = "sni-only"
  }

  tags = {
    Name        = "corunest-cdn"
    Environment = var.environment
  }
}

# Route53 DNS
resource "aws_route53_zone" "main" {
  name = var.domain_name

  tags = {
    Name        = "corunest-dns"
    Environment = var.environment
  }
}

resource "aws_route53_record" "main" {
  zone_id = aws_route53_zone.main.zone_id
  name    = var.domain_name
  type    = "A"

  alias {
    name                   = aws_cloudfront_distribution.main.domain_name
    zone_id                = aws_cloudfront_distribution.main.hosted_zone_id
    evaluate_target_health = false
  }
}

# Output important values
output "alb_dns_name" {
  value = aws_lb.main.dns_name
}

output "cloudfront_domain" {
  value = aws_cloudfront_distribution.main.domain_name
}

output "rds_endpoint" {
  value = aws_db_instance.main.endpoint
}
```

### Monitoring and Logging

#### Application Performance Monitoring
```php
// app/Http/Middleware/PerformanceMonitoring.php
<?php

class PerformanceMonitoring
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $metrics = [
            'request_duration' => ($endTime - $startTime) * 1000, // milliseconds
            'memory_usage' => ($endMemory - $startMemory) / 1024 / 1024, // MB
            'peak_memory' => memory_get_peak_usage() / 1024 / 1024, // MB
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
        ];

        // Send to monitoring service
        $this->sendMetrics($metrics);

        // Log slow requests
        if ($metrics['request_duration'] > 1000) {
            Log::warning('Slow request detected', $metrics);
        }

        return $response;
    }

    private function sendMetrics(array $metrics): void
    {
        // Send to New Relic
        if (extension_loaded('newrelic')) {
            newrelic_record_custom_event('PageView', $metrics);
        }

        // Send to Datadog
        if (config('monitoring.datadog.enabled')) {
            app('datadog')->timing('request.duration', $metrics['request_duration']);
            app('datadog')->gauge('request.memory_usage', $metrics['memory_usage']);
        }

        // Send to custom metrics endpoint
        dispatch(new RecordMetricsJob($metrics))->onQueue('metrics');
    }
}
```

#### Health Check Endpoint
```php
// app/Http/Controllers/HealthController.php
<?php

class HealthController extends Controller
{
    public function check()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'external_apis' => $this->checkExternalAPIs(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version'),
            'environment' => app()->environment(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $count = DB::table('users')->count();
            
            return [
                'status' => 'ok',
                'response_time' => $this->measureTime(fn() => DB::select('SELECT 1')),
                'details' => ['users_count' => $count]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkRedis(): array
    {
        try {
            $redis = Redis::connection();
            $redis->ping();
            
            return [
                'status' => 'ok',
                'response_time' => $this->measureTime(fn() => $redis->ping()),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $testFile = 'health-check-' . uniqid() . '.txt';
            Storage::put($testFile, 'health check');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);
            
            return [
                'status' => $exists ? 'ok' : 'error',
                'details' => ['writable' => $exists]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $queueSize = Redis::connection()->llen('queues:default');
            $failedJobs = DB::table('failed_jobs')->count();
            
            return [
                'status' => 'ok',
                'details' => [
                    'queue_size' => $queueSize,
                    'failed_jobs' => $failedJobs
                ]
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkExternalAPIs(): array
    {
        $apis = [
            'yoco' => config('payments.yoco.base_url'),
            'ozow' => config('payments.ozow.base_url'),
        ];

        $results = [];
        
        foreach ($apis as $name => $url) {
            try {
                $start = microtime(true);
                $response = Http::timeout(5)->get($url . '/health');
                $responseTime = (microtime(true) - $start) * 1000;
                
                $results[$name] = [
                    'status' => $response->successful() ? 'ok' : 'error',
                    'response_time' => $responseTime,
                    'status_code' => $response->status()
                ];
            } catch (Exception $e) {
                $results[$name] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'status' => collect($results)->every(fn($api) => $api['status'] === 'ok') ? 'ok' : 'warning',
            'details' => $results
        ];
    }

    private function measureTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return (microtime(true) - $start) * 1000;
    }
}
```

---

## Mobile & PWA Implementation

### Service Worker with Advanced Caching
```javascript
// public/sw.js (Enhanced Version)
const CACHE_VERSION = 'v1.2.0';
const STATIC_CACHE = `corunest-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `corunest-dynamic-${CACHE_VERSION}`;
const API_CACHE = `corunest-api-${CACHE_VERSION}`;

// Cache strategies
const CACHE_STRATEGIES = {
    CACHE_FIRST: 'cache-first',
    NETWORK_FIRST: 'network-first',
    STALE_WHILE_REVALIDATE: 'stale-while-revalidate',
    NETWORK_ONLY: 'network-only',
    CACHE_ONLY: 'cache-only'
};

// Route configurations
const ROUTE_CONFIGS = [
    {
        pattern: /\.(css|js|woff2?|ttf|eot)$/,
        strategy: CACHE_STRATEGIES.CACHE_FIRST,
        cache: STATIC_CACHE,
        maxAge: 30 * 24 * 60 * 60 * 1000 // 30 days
    },
    {
        pattern: /\.(png|jpg|jpeg|gif|svg|webp|ico)$/,
        strategy: CACHE_STRATEGIES.CACHE_FIRST,
        cache: STATIC_CACHE,
        maxAge: 7 * 24 * 60 * 60 * 1000 // 7 days
    },
    {
        pattern: /^https:\/\/api\.corunest\.com\/campaigns/,
        strategy: CACHE_STRATEGIES.STALE_WHILE_REVALIDATE,
        cache: API                // Otherwise fetch from network
                return fetch(event.request)
                    .then((response) => {
                        // Don't cache non-successful responses
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Cache successful responses
                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(() => {
                        // Serve offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                        
                        // Return empty response for other requests
                        return new Response('', {
                            status: 408,
                            statusText: 'Request timeout'
                        });
                    });
            })
    );
});

// Background cache update
function updateCache(request) {
    fetch(request)
        .then((response) => {
            if (response && response.status === 200) {
                const responseClone = response.clone();
                caches.open(CACHE_NAME)
                    .then((cache) => {
                        cache.put(request, responseClone);
                    });
            }
        })
        .catch(() => {
            // Silently fail background updates
        });
}

// Push notification handling
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/images/logo-192.png',
            badge: '/images/badge-72.png',
            vibrate: [100, 50, 100],
            data: {
                dateOfArrival: Date.now(),
                primaryKey: data.id || '1'
            },
            actions: [
                {
                    action: 'explore',
                    title: 'View Details',
                    icon: '/images/checkmark.png'
                },
                {
                    action: 'close',
                    title: 'Close',
                    icon: '/images/xmark.png'
                }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/campaigns')
        );
    }
});

// Background sync for offline donations
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync-donations') {
        event.waitUntil(processOfflineDonations());
    }
});

async function processOfflineDonations() {
    // Get offline donations from IndexedDB
    const offlineDonations = await getOfflineDonations();
    
    for (const donation of offlineDonations) {
        try {
            const response = await fetch('/api/donations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': donation.csrfToken
                },
                body: JSON.stringify(donation.data)
            });
            
            if (response.ok) {
                await removeOfflineDonation(donation.id);
            }
        } catch (error) {
            console.error('Failed to sync offline donation:', error);
        }
    }
}
```

#### PWA Manifest
```json
// public/manifest.json
{
    "name": "CoruNest - NGO Management Platform",
    "short_name": "CoruNest",
    "description": "Organise. Fund. Mobilise. Complete NGO management solution.",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#3B82F6",
    "orientation": "portrait-primary",
    "scope": "/",
    "lang": "en",
    "categories": ["productivity", "social", "utilities"],
    "shortcuts": [
        {
            "name": "Browse Campaigns",
            "short_name": "Campaigns",
            "description": "View active fundraising campaigns",
            "url": "/campaigns",
            "icons": [
                {
                    "src": "/images/shortcuts/campaigns-96.png",
                    "sizes": "96x96"
                }
            ]
        },
        {
            "name": "Upcoming Events",
            "short_name": "Events",
            "description": "Find volunteer opportunities",
            "url": "/events",
            "icons": [
                {
                    "src": "/images/shortcuts/events-96.png",
                    "sizes": "96x96"
                }
            ]
        },
        {
            "name": "My Dashboard",
            "short_name": "Dashboard",
            "description": "View your donation history",
            "url": "/dashboard",
            "icons": [
                {
                    "src": "/images/shortcuts/dashboard-96.png",
                    "sizes": "96x96"
                }
            ]
        }
    ],
    "icons": [
        {
            "src": "/images/logo-72.png",
            "sizes": "72x72",
            "type": "image/png",
            "purpose": "maskable"
        },
        {
            "src": "/images/logo-96.png",
            "sizes": "96x96",
            "type": "image/png",
            "purpose": "maskable"
        },
        {
            "src": "/images/logo-128.png",
            "sizes": "128x128",
            "type": "image/png",
            "purpose": "maskable"
        },
        {
            "src": "/images/logo-144.png",
            "sizes": "144x144",
            "type": "image/png",
            "purpose": "maskable"
        },
        {
            "src": "/images/logo-152.png",
            "sizes": "152x152",
            "type": "image/png",
            "purpose": "maskable"
        },
        {
            "src": "/images/logo-192.png",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any"
        },
        {
            "src": "/images/logo-384.png",
            "sizes": "384x384",
            "type": "image/png",
            "purpose": "any"
        },
        {
            "src": "/images/logo-512.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any"
        }
    ],
    "screenshots": [
        {
            "src": "/images/screenshots/campaigns-mobile.png",
            "sizes": "390x844",
            "type": "image/png",
            "form_factor": "narrow",
            "label": "Campaign browsing on mobile"
        },
        {
            "src": "/images/screenshots/dashboard-mobile.png",
            "sizes": "390x844",
            "type": "image/png",
            "form_factor": "narrow",
            "label": "Dashboard view on mobile"
        },
        {
            "src": "/images/screenshots/campaigns-desktop.png",
            "sizes": "1920x1080",
            "type": "image/png",
            "form_factor": "wide",
            "label": "Campaign management dashboard"
        }
    ],
    "prefer_related_applications": false
}
```

#### Offline Storage Strategy
```javascript
// resources/js/offline-storage.js
class OfflineStorage {
    constructor() {
        this.dbName = 'CoruNestDB';
        this.dbVersion = 1;
        this.db = null;
        this.init();
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Offline donations store
                if (!db.objectStoreNames.contains('offlineDonations')) {
                    const donationsStore = db.createObjectStore('offlineDonations', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    donationsStore.createIndex('timestamp', 'timestamp', { unique: false });
                    donationsStore.createIndex('status', 'status', { unique: false });
                }

                // Volunteer signups store
                if (!db.objectStoreNames.contains('offlineVolunteers')) {
                    const volunteersStore = db.createObjectStore('offlineVolunteers', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    volunteersStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                // Cached campaigns for offline browsing
                if (!db.objectStoreNames.contains('cachedCampaigns')) {
                    const campaignsStore = db.createObjectStore('cachedCampaigns', {
                        keyPath: 'id'
                    });
                    campaignsStore.createIndex('lastUpdated', 'lastUpdated', { unique: false });
                }
            };
        });
    }

    // Store donation for offline processing
    async storeDonation(donationData) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offlineDonations'], 'readwrite');
            const store = transaction.objectStore('offlineDonations');
            
            const donation = {
                data: donationData,
                timestamp: Date.now(),
                status: 'pending',
                retryCount: 0,
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            };

            const request = store.add(donation);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Get all pending donations
    async getOfflineDonations() {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offlineDonations'], 'readonly');
            const store = transaction.objectStore('offlineDonations');
            const index = store.index('status');
            
            const request = index.getAll('pending');
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Remove processed donation
    async removeOfflineDonation(id) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['offlineDonations'], 'readwrite');
            const store = transaction.objectStore('offlineDonations');
            
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // Cache campaigns for offline viewing
    async cacheCampaigns(campaigns) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['cachedCampaigns'], 'readwrite');
            const store = transaction.objectStore('cachedCampaigns');
            
            campaigns.forEach(campaign => {
                campaign.lastUpdated = Date.now();
                store.put(campaign);
            });

            transaction.oncomplete = () => resolve();
            transaction.onerror = () => reject(transaction.error);
        });
    }

    // Get cached campaigns
    async getCachedCampaigns() {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['cachedCampaigns'], 'readonly');
            const store = transaction.objectStore('cachedCampaigns');
            
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }
}

// Initialize offline storage
const offlineStorage = new OfflineStorage();

// Export for use in other modules
window.offlineStorage = offlineStorage;
```

### Capacitor Mobile App Configuration

#### Capacitor Configuration
```typescript
// capacitor.config.ts
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'com.corunest.app',
    appName: 'CoruNest',
    webDir: 'public',
    bundledWebRuntime: false,
    server: {
        androidScheme: 'https'
    },
    plugins: {
        SplashScreen: {
            launchShowDuration: 3000,
            launchAutoHide: true,
            backgroundColor: "#3B82F6",
            androidSplashResourceName: "splash",
            androidScaleType: "CENTER_CROP",
            showSpinner: false,
            androidSpinnerStyle: "large",
            iosSpinnerStyle: "small",
            spinnerColor: "#ffffff",
            splashFullScreen: true,
            splashImmersive: true,
            layoutName: "launch_screen",
            useDialog: true,
        },
        PushNotifications: {
            presentationOptions: ["badge", "sound", "alert"],
        },
        LocalNotifications: {
            smallIcon: "ic_stat_icon_config_sample",
            iconColor: "#3B82F6",
            sound: "beep.wav",
        },
        Camera: {
            permissions: ["camera", "photos"]
        },
        Geolocation: {
            permissions: ["location"]
        },
        StatusBar: {
            style: 'DEFAULT',
            backgroundColor: '#3B82F6'
        }
    },
    ios: {
        scheme: "CoruNest"
    },
    android: {
        allowMixedContent: true,
        captureInput: true
    }
};

export default config;
```

#### Push Notification Service
```javascript
// resources/js/push-notifications.js
import { PushNotifications } from '@capacitor/push-notifications';
import { Capacitor } from '@capacitor/core';

class PushNotificationService {
    constructor() {
        this.isSupported = Capacitor.isPluginAvailable('PushNotifications');
        this.init();
    }

    async init() {
        if (!this.isSupported) {
            console.log('Push notifications not supported');
            return;
        }

        // Request permission
        const permStatus = await PushNotifications.requestPermissions();

        if (permStatus.receive === 'granted') {
            await PushNotifications.register();
        } else {
            console.log('Push notification permission denied');
        }

        // Listen for registration
        PushNotifications.addListener('registration', (token) => {
            console.log('Push registration success, token: ' + token.value);
            this.sendTokenToServer(token.value);
        });

        // Listen for registration errors
        PushNotifications.addListener('registrationError', (error) => {
            console.error('Push registration error: ', error);
        });

        // Listen for push notifications
        PushNotifications.addListener('pushNotificationReceived', (notification) => {
            console.log('Push notification received: ', notification);
            this.handleNotification(notification);
        });

        // Listen for notification actions
        PushNotifications.addListener('pushNotificationActionPerformed', (notification) => {
            console.log('Push notification action performed', notification);
            this.handleNotificationAction(notification);
        });
    }

    async sendTokenToServer(token) {
        try {
            await fetch('/api/push-tokens', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    token: token,
                    platform: Capacitor.getPlatform()
                })
            });
        } catch (error) {
            console.error('Failed to send push token to server:', error);
        }
    }

    handleNotification(notification) {
        // Handle received notification while app is open
        if (notification.data?.type === 'donation_received') {
            // Show in-app notification
            this.showInAppNotification(notification);
        }
    }

    handleNotificationAction(notification) {
        const { notification: notificationData, actionId } = notification;
        
        if (actionId === 'view_campaign' && notificationData.data?.campaign_id) {
            // Navigate to campaign
            window.location.href = `/campaigns/${notificationData.data.campaign_id}`;
        } else if (actionId === 'view_dashboard') {
            // Navigate to dashboard
            window.location.href = '/dashboard';
        }
    }

    showInAppNotification(notification) {
        // Create toast-style notification
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-blue-600 text-white px-6 py-4 rounded-lg shadow-lg z-50 transform transition-transform duration-300 translate-x-full';
        toast.innerHTML = `
            <div class="flex items-center">
                <div class="flex-1">
                    <h4 class="font-semibold">${notification.title}</h4>
                    <p class="text-sm opacity-90">${notification.body}</p>
                </div>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    ×
                </button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
}

// Initialize push notifications if running in mobile app
if (Capacitor.isNativePlatform()) {
    new PushNotificationService();
}
```

---

## Testing Strategy

### Backend Testing (PHPUnit/Pest)

#### Unit Tests
```php
// tests/Unit/Services/PaymentServiceTest.php
<?php

use App\Models\Donation;
use App\Models\Campaign;
use App\Models\Organization;
use App\Services\YocoPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private YocoPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new YocoPaymentService();
    }

    /** @test */
    public function it_can_initialize_payment_for_valid_donation()
    {
        // Arrange
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'organization_id' => $organization->id,
            'amount' => 100.00,
            'status' => 'pending'
        ]);

        // Mock Yoco API response
        Http::fake([
            'api.yoco.com/checkouts' => Http::response([
                'id' => 'checkout_test_123',
                'redirectUrl' => 'https://pay.yoco.com/test'
            ], 201)
        ]);

        // Act
        $result = $this->paymentService->initializePayment($donation);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('redirect_url', $result);
        $this->assertStringContains('pay.yoco.com', $result['redirect_url']);
        
        $donation->refresh();
        $this->assertEquals('processing', $donation->status);
        $this->assertEquals('checkout_test_123', $donation->transaction_id);
    }

    /** @test */
    public function it_handles_payment_initialization_failure()
    {
        // Arrange
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->create(['organization_id' => $organization->id]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'organization_id' => $organization->id,
            'amount' => 100.00
        ]);

        // Mock Yoco API failure
        Http::fake([
            'api.yoco.com/checkouts' => Http::response(['error' => 'Invalid request'], 400)
        ]);

        // Act
        $result = $this->paymentService->initializePayment($donation);

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_processes_successful_webhook()
    {
        // Arrange
        $organization = Organization::factory()->create();
        $campaign = Campaign::factory()->create([
            'organization_id' => $organization->id,
            'current_amount' => 0
        ]);
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'organization_id' => $organization->id,
            'amount' => 100.00,
            'status' => 'processing',
            'transaction_id' => 'checkout_test_123'
        ]);

        $webhookPayload = [
            'type' => 'checkout.succeeded',
            'data' => [
                'id' => 'checkout_test_123',
                'paymentId' => 'payment_test_456',
                'fees' => 250 // R2.50 in cents
            ]
        ];

        // Act
        $request = new Request();
        $request->setContent(json_encode($webhookPayload));
        $request->headers->set('X-Yoco-Signature', $this->generateWebhookSignature($webhookPayload));
        
        $result = $this->paymentService->handleWebhook($request);

        // Assert
        $this->assertTrue($result['success']);
        
        $donation->refresh();
        $campaign->refresh();
        
        $this->assertEquals('completed', $donation->status);
        $this->assertEquals('payment_test_456', $donation->provider_transaction_id);
        $this->assertEquals(2.50, $donation->fee_amount);
        $this->assertEquals(97.50, $donation->net_amount);
        $this->assertEquals(100.00, $campaign->current_amount);
        $this->assertNotNull($donation->receipt_number);
        $this->assertNotNull($donation->processed_at);
    }

    private function generateWebhookSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), config('payments.yoco.webhook_secret'));
    }
}
```

#### Feature Tests
```php
// tests/Feature/CampaignManagementTest.php
<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->organization = Organization::factory()->create();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'organization_id' => $this->organization->id
        ]);
    }

    /** @test */
    public function admin_can_create_campaign()
    {
        // Arrange
        Storage::fake('public');
        $image = UploadedFile::fake()->image('campaign.jpg', 800, 600);
        
        $campaignData = [
            'title' => 'Help Build Clean Water Wells',
            'summary' => 'Providing clean water access to rural communities',
            'description' => 'This is a detailed description of our water well project...',
            'target_amount' => 50000,
            'goal_type' => 'currency',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(90)->format('Y-m-d'),
            'image' => $image,
            'featured' => true
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->post(route('admin.campaigns.store'), $campaignData);

        // Assert
        $response->assertRedirect(route('admin.campaigns.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Help Build Clean Water Wells',
            'organization_id' => $this->organization->id,
            'target_amount' => 50000,
            'slug' => 'help-build-clean-water-wells',
            'status' => 'draft',
            'featured' => true
        ]);

        Storage::disk('public')->assertExists('campaigns/' . $image->hashName());
    }

    /** @test */
    public function admin_cannot_create_campaign_with_invalid_data()
    {
        // Act
        $response = $this->actingAs($this->admin)
            ->post(route('admin.campaigns.store'), [
                'title' => '', // Required field missing
                'target_amount' => -100, // Invalid amount
                'end_date' => now()->subDays(1)->format('Y-m-d') // Past date
            ]);

        // Assert
        $response->assertSessionHasErrors(['title', 'target_amount', 'end_date']);
        $this->assertDatabaseCount('campaigns', 0);
    }

    /** @test */
    public function admin_can_update_campaign()
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'title' => 'Original Title',
            'target_amount' => 10000
        ]);

        $updateData = [
            'title' => 'Updated Campaign Title',
            'summary' => 'Updated summary',
            'description' => 'Updated description',
            'target_amount' => 20000,
            'status' => 'active'
        ];

        // Act
        $response = $this->actingAs($this->admin)
            ->put(route('admin.campaigns.update', $campaign), $updateData);

        // Assert
        $response->assertRedirect(route('admin.campaigns.index'));
        
        $campaign->refresh();
        $this->assertEquals('Updated Campaign Title', $campaign->title);
        $this->assertEquals(20000, $campaign->target_amount);
        $this->assertEquals('active', $campaign->status);
    }

    /** @test */
    public function admin_can_delete_campaign_without_donations()
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id
        ]);

        // Act
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.campaigns.destroy', $campaign));

        // Assert
        $response->assertRedirect(route('admin.campaigns.index'));
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    /** @test */
    public function admin_cannot_delete_campaign_with_donations()
    {
        // Arrange
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id
        ]);
        
        Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'organization_id' => $this->organization->id,
            'status' => 'completed'
        ]);

        // Act
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.campaigns.destroy', $campaign));

        // Assert
        $response->assertSessionHasErrors(['campaign' => 'Cannot delete campaign with existing donations']);
        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    }

    /** @test */
    public function non_admin_cannot_access_campaign_management()
    {
        // Arrange
        $donor = User::factory()->create(['role' => 'donor']);

        // Act
        $response = $this->actingAs($donor)
            ->get(route('admin.campaigns.index'));

        // Assert
        $response->assertStatus(403);
    }
}
```

### Frontend Testing (React Testing Library)

#### Component Tests
```jsx
// resources/js/__tests__/Components/DonationForm.test.jsx
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { rest } from 'msw';
import { setupServer } from 'msw/node';
import DonationForm from '@/Components/DonationForm';

// Mock server for API calls
const server = setupServer(
    rest.post('/api/donations', (req, res, ctx) => {
        const { amount, donor_name, donor_email } = req.body;
        
        if (!amount || !donor_name || !donor_email) {
            return res(
                ctx.status(422),
                ctx.json({
                    errors: {
                        amount: amount ? [] : ['The amount field is required.'],
                        donor_name: donor_name ? [] : ['The donor name field is required.'],
                        donor_email: donor_email ? [] : ['The donor email field is required.']
                    }
                })
            );
        }
        
        return res(
            ctx.json({
                success: true,
                redirect_url: 'https://pay.yoco.com/test'
            })
        );
    })
);

beforeAll(() => server.listen());
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

describe('DonationForm', () => {
    const mockProps = {
        campaign: {
            id: 1,
            title: 'Test Campaign',
            target_amount: 10000,
            current_amount: 2000
        }
    };

    it('renders donation form correctly', () => {
        render(<DonationForm {...mockProps} />);
        
        expect(screen.getByRole('heading', { name: /make a donation/i })).toBeInTheDocument();
        expect(screen.getByLabelText(/amount/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/full name/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/email address/i)).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /donate now/i })).toBeInTheDocument();
    });

    it('sets predefined amounts when quick buttons are clicked', async () => {
        const user = userEvent.setup();
        render(<DonationForm {...mockProps} />);
        
        const amountInput = screen.getByLabelText(/amount/i);
        const button100 = screen.getByRole('button', { name: /r100/i });
        
        await user.click(button100);
        
        expect(amountInput).toHaveValue(100);
    });

    it('validates required fields', async () => {
        const user = userEvent.setup();
        render(<DonationForm {...mockProps} />);
        
        const submitButton = screen.getByRole('button', { name: /donate now/i });
        
        await user.click(submitButton);
        
        await waitFor(() => {
            expect(screen.getByText(/amount is required/i)).toBeInTheDocument();
            expect(screen.getByText(/name is required/i)).toBeInTheDocument();
            expect(screen.getByText(/email is required/i)).toBeInTheDocument();
        });
    });

    it('validates minimum donation amount', async () => {
        const user = userEvent.setup();
        render(<DonationForm {...mockProps} />);
        
        const amountInput = screen.getByLabelText(/amount/i);
        
        await user.type(amountInput, '5');
        await user.tab(); // Trigger validation
        
        expect(screen.getByText(/minimum donation amount is r10/i)).toBeInTheDocument();
    });

    it('submits form with valid data', async () => {
        const user = userEvent.setup();
        render(<DonationForm {...mockProps} />);
        
        // Fill form
        await user.type(screen.getByLabelText(/amount/i), '100');
        await user.type(screen.getByLabelText(/full name/i), 'John Doe');
        await user.type(screen.getByLabelText(/email address/i), 'john@example.com');
        
        // Submit
        await user.click(screen.getByRole('button', { name: /donate now/i }));
        
        await waitFor(() => {
            expect(screen.getByText(/processing/i)).toBeInTheDocument();
        });
    });

    it('toggles anonymous donation checkbox', async () => {
        const user = userEvent.setup();
        render(<DonationForm {...mockProps} />);
        
        const anonymousCheckbox = screen.getByRole('checkbox', { name: /make this donation anonymous/i });
        
        expect(anonymousCheckbox).not.toBeChecked();
        
        await user.click(anonymousCheckbox);
        
        expect(anonymousCheckbox).toBeChecked();
    });

    it('shows loading state during submission', async () => {
        const user = userEvent.setup();
        render(<DonationForm {...mockProps} />);
        
        // Fill form
        await user.type(screen.getByLabelText(/amount/i), '100');
        await user.type(screen.getByLabelText(/full name/i), 'John Doe');
        await user.type(screen.getByLabelText(/email address/i), 'john@example.com');
        
        const submitButton = screen.getByRole('button', { name: /donate now/i });
        
        await user.click(submitButton);
        
        expect(submitButton).toBeDisabled();
        expect(screen.getByText(/processing/i)).toBeInTheDocument();
    });
});
```

#### Integration Tests
```jsx
// resources/js/__tests__/Pages/Admin/Dashboard.test.jsx
import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { router } from '@inertiajs/react';
import Dashboard from '@/Pages/Admin/Dashboard';

// Mock Inertia router
jest.mock('@inertiajs/react', () => ({
    ...jest.requireActual('@inertiajs/react'),
    router: {
        get: jest.fn()
    }
}));

// Mock Recharts components to avoid canvas issues in tests
jest.mock('recharts', () => ({
    ResponsiveContainer: ({ children }) => <div data-testid="chart-container">{children}</div>,
    AreaChart: ({ children }) => <div data-testid="area-chart">{children}</div>,
    BarChart: ({ children }) => <div data-testid="bar-chart">{children}</div>,
    PieChart: ({ children }) => <div data-testid="pie-chart">{children}</div>,
    LineChart: ({ children }) => <div data-testid="line-chart">{children}</div>,
    Area: () => <div data-testid="area" />,
    Bar: () => <div data-testid="bar" />,
    Pie: () => <div data-testid="pie" />,
    Line: () => <div data-testid="line" />,
    XAxis: () => <div data-testid="x-axis" />,
    YAxis: () => <div data-testid="y-axis" />,
    CartesianGrid: () => <div data-testid="grid" />,
    Tooltip: () => <div data-testid="tooltip" />,
    Legend: () => <div data-testid="legend" />,
    Cell: () => <div data-testid="cell" />
}));

describe('Admin Dashboard', () => {
    const mockProps = {
        auth: {
            user: {
                id: 1,
                name: 'Admin User',
                email: 'admin@test.com',
                role: 'admin'
            }
        },
        stats: {
            totalDonations: 125000,
            donationsChange: 15.2,
            activeDonors: 342,
            donorsChange: 8.7,
            activeCampaigns: 12,
            campaignsChange: -2.1,
            upcomingEvents: 5,
            eventsChange: 25.0
        },
        chartData: {
            donations: [
                { date: '2024-01', amount: 15000 },
                { date: '2024-02', amount: 18000 },
                { date: '2024-03', amount: 22000 }
            ],
            campaigns: [
                { name: 'Water Wells', raised: 45000 },
                { name: 'Education Fund', raised: 32000 },
                { name: 'Food Security', raised: 28000 }
            ],
            paymentMethods: [
                { name: 'Yoco', value: 65, color: '#3B82F6' },
                { name: 'Ozow', value: 35, color: '#10B981' }
            ],
            donorGrowth: [
                { month: 'Jan', newDonors: 45, returningDonors: 23 },
                { month: 'Feb', newDonors: 52, returningDonors: 31 },
                { month: 'Mar', newDonors: 38, returningDonors: 28 }
            ]
        },
        recentDonations: [
            {
                id: 1,
                donor_name: 'John Doe',
                donor_email: 'john@example.com',
                amount: 500,
                status: 'completed',
                campaign: { title: 'Water Wells Project' }
            },
            {
                id: 2,
                donor_name: 'Jane Smith',
                donor_email: 'jane@example.com',
                amount: 250,
                status: 'pending',
                campaign: { title: 'Education Fund' }
            }
        ],
        upcomingEvents: [
            {
                id: 1,
                title: 'Community Cleanup',
                starts_at: '2024-04-15T09:00:00Z',
                location: 'Central Park',
                signups_count: 15,
                capacity: 50
            }
        ]
    };

    it('renders dashboard with stats cards', () => {
        render(<Dashboard {...mockProps} />);
        
        expect(screen.getByText('R125,000')).toBeInTheDocument();
        expect(screen.getByText('342')).toBeInTheDocument();
        expect(screen.getByText('12')).toBeInTheDocument();
        expect(screen.getByText('5')).toBeInTheDocument();
        
        // Check for percentage changes
        expect(screen.getByText('15.2%')).toBeInTheDocument();
        expect(screen.getByText('8.7%')).toBeInTheDocument();
    });

    it('renders all chart sections', () => {
        render(<Dashboard {...mockProps} />);
        
        expect(screen.getByText('Donations Over Time')).toBeInTheDocument();
        expect(screen.getByText('Top Campaigns')).toBeInTheDocument();
        expect(screen.getByText('Payment Methods')).toBeInTheDocument();
        expect(screen.getByText('Donor Growth')).toBeInTheDocument();
        
        // Check that chart components are rendered
        expect(screen.getAllByTestId('chart-container')).toHaveLength(4);
    });

    it('displays recent donations table', () => {
        render(<Dashboard {...mockProps} />);
        
        expect(screen.getByText('Recent Donations')).toBeInTheDocument();
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('john@example.com')).toBeInTheDocument();
        expect(screen.getByText('Water Wells Project')).toBeInTheDocument();
        expect(screen.getByText('R500')).toBeInTheDocument();
    });

    it('shows quick action buttons', () => {
        render(<Dashboard {...mockProps} />);
        
        expect(screen.getByRole('link', { name: /create campaign/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /create event/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /export reports/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /send email/i })).toBeInTheDocument();
    });

    it('displays upcoming events', () => {
        render(<Dashboard {...mockProps} />);
        
        expect(screen.getByText('Upcoming Events')).toBeInTheDocument();
        expect(screen.getByText('Community Cleanup')).toBeInTheDocument();
        expect(screen.getByText('15/50 signed up')).toBeInTheDocument();
    });

    it('handles time range selection', async () => {
        render(<Dashboard {...mockProps} />);
        
        const lastWeekButton = screen.getByRole('button', { name: /last 7 days/i });
        
        await user.click(lastWeekButton);
        
        expect(router.get).toHaveBeenCalledWith('/admin/dashboard/data?range=7d');
    });
});
```

### End-to-End Testing (Playwright)

#### E2E Test Configuration
```javascript
// playwright.config.js
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [
        ['html'],
        ['json', { outputFile: 'test-results.json' }]
    ],
    use: {
        baseURL: process.env.APP_URL || 'http://localhost:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure'
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
        {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
        },
        {
            name: 'Mobile Chrome',
            use: { ...devices['Pixel 5'] },
        },
        {
            name: 'Mobile Safari',
            use: { ...devices['iPhone 12'] },
        },
    ],
    webServer: {
        command: 'php artisan serve --port=8000',
        port: 8000,
        reuseExistingServer: !process.env.CI,
    },
});
```

#### E2E Tests
```javascript
// tests/e2e/donation-flow.spec.js
import { test, expect } from '@playwright/test';

test.describe('Donation Flow', () => {
    test.beforeEach(async ({ page }) => {
        // Seed test data
        await page.request.post('/test/seed-campaign');
    });

    test('user can complete donation flow', async ({ page }) => {
        // Navigate to campaign page
        await page.goto('/campaigns/test-campaign');
        
        // Verify campaign details
        await expect(page.locator('h1')).toContainText('Test Campaign');
        await expect(page.locator('[data-testid="progress-bar"]')).toBeVisible();
        
        // Fill donation form
        await page.fill('input[name="amount"]', '100');
        await page.selectOption('select[name="payment_method"]', 'yoco');
        await page.fill('input[name="donor_name"]', 'John Doe');
        await page.fill('input[name="donor_email"]', 'john@example.com');
        await page.fill('input[name="donor_phone"]', '+27123456789');
        await page.fill('textarea[name="message"]', 'Great cause!');
        
        // Submit donation
        await page.click('button[type="submit"]');
        
        // Should redirect to payment provider (mock)
        await expect(page).toHaveURL(/pay\.yoco\.com|ozow\.com/);
        
        // Simulate successful payment return
        await page.goto('/donations/success/1');
        
        // Verify success page
        await expect(page.locator('h1')).toContainText('Thank you');
        await expect(page.locator('[data-testid="receipt-number"]')).toBeVisible();
        await expect(page.locator('[data-testid="download-receipt"]')).toBeVisible();
    });

    test('donation form validation works', async ({ page }) => {
        await page.goto('/campaigns/test-campaign');
        
        // Try to submit empty form
        await page.click('button[type="submit"]');
        
        // Check validation messages
        await expect(page.locator('text=Amount is required')).toBeVisible();
        await expect(page.locator('text=Name is required')).toBeVisible();
        await expect(page.locator('text=Email is required')).toBeVisible();
        
        // Test minimum amount validation
        await page.fill('input[name="amount"]', '5');
        await page.blur('input[name="amount"]');
        
        await expect(page.locator('text=Minimum donation amount is R10')).toBeVisible();
    });

    test('quick amount buttons work', async ({ page }) => {
        await page.goto('/campaigns/test-campaign');
        
        // Click R100 quick button
        await page.click('button:has-text("R100")');
        
        // Verify amount is set
        await expect(page.locator('input[name="amount"]')).toHaveValue('100');
    });

    test('anonymous donation option works', async ({ page }) => {
        await page.goto('/campaigns/test-campaign');
        
        // Fill form
        await page.fill('input[name="amount"]', '50');
        await page.fill('input[name="donor_name"]', 'Anonymous Donor');
        await page.fill('input[name="donor_email"]', 'anon@example.com');
        
        // Check anonymous option
        await page.check('input[name="is_anonymous"]');
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Verify anonymous flag is sent
        await page.waitForRequest(request => 
            request.url().includes('/donate') && 
            JSON.parse(request.postData()).is_anonymous === true
        );
    });

    test('mobile donation flow works', async ({ page, isMobile }) => {
        if (!isMobile) return;
        
        await page.goto('/campaigns/test-campaign');
        
        // Verify mobile layout
        await expect(page.locator('[data-testid="mobile-donation-form"]')).toBeVisible();
        
        // Fill and submit form
        await page.fill('input[name="amount"]', '75');
        await page.fill('input[name="donor_name"]', 'Mobile User');
        await page.fill('input[name="donor_email"]', 'mobile@example.com');
        
        await page.click('button[type="submit"]');
        
        // Should work same as desktop
        await expect(page).toHaveURL(/pay\.(yoco|ozow)\.com/);
    });
});
```

#### Admin Dashboard E2E Tests
```javascript
// tests/e2e/admin-dashboard.spec.js
import { test, expect } from '@playwright/test';

test.describe('Admin Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        // Login as admin
        await page.goto('/login');
        await page.fill('input[name="email"]', 'admin@test.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        
        // Wait for redirect to dashboard
        await page.waitForURL('/admin/dashboard');
    });

    test('admin can view dashboard overview', async ({ page }) => {
        // Verify main stats are visible
        await expect(page.locator('[data-testid="total-donations"]')).toBeVisible();
        await expect(page.locator('[data-testid="active-donors"]')).toBeVisible();
        await expect(page.locator('[data-testid="active-campaigns"]')).toBeVisible();
        await expect(page.locator('[data-testid="upcoming-events"]')).toBeVisible();
        
        // Verify charts are loaded
        await expect(page.locator('[data-testid="donations-chart"]')).toBeVisible();
        await expect(page.locator('[data-testid="campaigns-chart"]')).toBeVisible();
        
        // Verify recent donations table
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr')).toHaveCount.greaterThan(0);
    });

    test('admin can create new campaign', async ({ page }) => {
        // Click create campaign button
        await page.click('a:has-text("Create Campaign")');
        
        // Fill campaign form
        await page.fill('input[name="title"]', 'New Test Campaign');
        await page.fill('textarea[name="summary"]', 'This is a test campaign summary');
        await page.fill('textarea[name="description"]', 'Detailed description of the campaign');
        await page.fill('input[name="target_amount"]', '25000');
        
        // Upload image
        await page.setInputFiles('input[name="image"]', 'tests/fixtures/campaign-image.jpg');
        
        // Set dates
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + 30);
        
        await page.fill('input[name="start_date"]', tomorrow.toISOString().split('T')[0]);
        await page.fill('input[name="end_date"]', endDate.toISOString().split('T')[0]);
        
        // Submit form
        await page.click('button[type="submit"]');
        
        // Verify redirect and success message
        await expect(page).toHaveURL('/admin/campaigns');
        await expect(page.locator('.alert-success')).toContainText('Campaign created successfully');
        
        // Verify campaign appears in list
        await expect(page.locator('td:has-text("New Test Campaign")')).toBeVisible();
    });

    test('admin can manage donations', async ({ page }) => {
        await page.goto('/admin/donations');
        
        // Verify donations table loads
        await expect(page.locator('table')).toBeVisible();
        
        // Test filtering
        await page.selectOption('select[name="status"]', 'completed');
        await page.click('button:has-text("Filter")');
        
        // Verify URL contains filter
        await expect(page).toHaveURL(/status=completed/);
        
        // Test search
        await page.fill('input[name="search"]', 'john@example.com');
        await page.click('button:has-text("Search")');
        
        // Verify filtered results
        await expect(page.locator('td:has-text("john@example.com")')).toBeVisible();
    });

    test('admin can export donation reports', async ({ page }) => {
        await page.goto('/admin/donations');
        
        // Click export button
        const downloadPromise = page.waitForEvent('download');
        await page.click('button:has-text("Export CSV")');
        
        const download = await downloadPromise;
        
        // Verify download
        expect(download.suggestedFilename()).toMatch(/donations-\d{4}-\d{2}-\d{2}\.csv/);
        
        // Verify file content
        const path = await download.path();
        const fs = require('fs');
        const content = fs.readFileSync(path, 'utf8');
        expect(content).toContain('Donor Name,Email,Amount,Campaign,Date,Status');
    });

    test('admin dashboard is responsive', async ({ page }) => {
        // Test mobile viewport
        await page.setViewportSize({ width: 375, height: 812 });
        
        // Verify mobile navigation works
        await expect(page.locator('[data-testid="mobile-menu-button"]')).toBeVisible();
        await page.click('[data-testid="mobile-menu-button"]');
        await expect(page.locator('[data-testid="mobile-menu"]')).toBeVisible();
        
        // Verify stats cards stack vertically
        const statCards = page.locator('[data-testid="stat-card"]');
        const firstCard = statCards.first();
        const secondCard = statCards.nth(1);
        
        const firstCardBox = await firstCard.boundingBox();
        const secondCardBox = await secondCard.boundingBox();
        
        // Second card should be below first card (not side by side)
        expect(secondCardBox.y).toBeGreaterThan(firstCardBox.y + firstCardBox.height);
    });
});
```

---

## CI/CD Pipeline

### GitHub Actions Workflow
```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

env:
  PHP_VERSION: 8.2
  NODE_VERSION: 18
  MYSQL_DATABASE: corunest_test
  MYSQL_USER: corunest
  MYSQL_PASSWORD: password

jobs:
  test-backend:
    name: Backend Tests
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: ${{ env.MYSQL_DATABASE }}
          MYSQL_USER: ${{ env.MYSQL_USER }}
          MYSQL_PASSWORD: ${{ env.MYSQL_PASSWORD }}
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: mbstring, dom, fileinfo, mysql, redis
        coverage: xdebug

    - name: Cache Composer dependencies
      uses: actions/cache@v3
      with:
        path: ~/.composer/cache
        key: composer-${{ hashFiles('composer.lock') }}
        restore-keys: composer-

    - name: Install Composer dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader

    - name: Setup environment
      run: |
        cp .env.example .env.testing
        php artisan key:generate --env=testing
        php artisan config:cache --env=testing

    - name: Run database migrations
      run: php artisan migrate --env=testing --force

    - name: Run PHP CS Fixer
      run: vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

    - name: Run PHPStan
      run: vendor/bin/phpstan analyse --memory-limit=2G

    - name: Run backend tests
      run: php artisan test --coverage --min=80
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: ${{ env.MYSQL_DATABASE }}
        DB_USERNAME: ${{ env.MYSQL_USER }}
        DB_PASSWORD: ${{ env.MYSQL_PASSWORD }}
        REDIS_HOST: 127.0.0.1
        REDIS_PORT: 6379

    - name: Upload coverage reports
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
        flags: backend

  test-frontend:
    name: Frontend Tests
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v4

    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: ${{ env.NODE_VERSION }}
        cache: 'npm'

    - name: Install dependencies
      run: npm ci

    - name: Run ESLint
      run: npm run lint

    - name: Run Prettier check
      run: npm run format:check

    - name: Run frontend tests
      run: npm run test:coverage

    - name: Upload coverage reports
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage/lcov.info
        flags: frontend

  test-e2e:
    name: E2E Tests
    runs-on: ubuntu-latest
    needs: [test-backend, test-frontend]

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: ${{ env.MYSQL_DATABASE }}
          MYSQL_USER: ${{ env.MYSQL_USER }}
          MYSQL_PASSWORD: ${{ env.MYSQL_PASSWORD }}
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: mbstring, dom, fileinfo, mysql

    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: ${{ env.NODE_VERSION }}
        cache: 'npm'

    - name: Install PHP dependencies
      run: composer install --no-progress --prefer-dist --optimize-autoloader

    - name: Install Node dependencies
      run: npm ci

    - name: Build frontend assets
      run: npm run build

    - name: Setup application
      run: |
        cp .env.example .env
        php artisan key:generate
        php artisan migrate --force
        php artisan db:seed --force

    - name: Install Playwright browsers
      run: npx playwright install --with-deps

    - name: Run E2E tests
      run: npx playwright test
      env:
        DB_CONNECTION: mysql
        DB_HOST: 127.0.0.1
        DB_PORT: 3306
        DB_DATABASE: ${{ env.MYSQL_DATABASE }}
        DB_USERNAME: ${{ env.MYSQL_USER }}
        DB_PASSWORD: ${{ env.MYSQL_PASSWORD }}

    - name: Upload Playwright report
      uses: actions/upload-artifact@v3
      if: always()
      with:
        name: playwright-report
        path: playwright-report/

  security-scan:
    name: Security Scan
    runs-on: ubuntu-latest

    steps:
    - uses: actions/checkout@v4

    - name: Run Composer audit
      run: composer audit

    - name: Run npm audit
      run: npm audit --audit-level moderate

    - name: Run Snyk security scan
      uses: snyk/actions/php@master
      env:
        SNYK_TOKEN: ${{ secrets.SNYK_TOKEN }}
      with:
        args: --severity-threshold=medium

  build-and-deploy:
    name: Build and Deploy
    runs-on: ubuntu-latest
    needs: [test-backend, test-frontend, test-e2e, security-scan]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'

    steps:
    - uses: actions/checkout@v4

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ env.PHP_VERSION }}
        extensions: mbstring, dom, fileinfo, mysql

    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: ${{ env.NODE_VERSION }}
        cache: 'npm'

    - name: Install dependencies
      run: |
        composer install --no-progress --no-dev --optimize-autoloader
        npm ci

    - name: Build production assets
      run: npm run build

    - name: Create deployment artifact
      run: |
        tar -czf corunest-${{ github.sha }}.tar.gz \
          --exclude=node_modules \
          --exclude=.git \
          --exclude=tests \
          --exclude=.github \
          .

    - name: Upload artifact to S3
      run: |
        aws s3 cp corunest-${{ github.sha }}.tar.gz s3://${{ secrets.S3_BUCKET }}/releases/
      env:
        AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
        AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
        AWS_DEFAULT_REGION: ${{ secrets.AWS_DEFAULT_REGION }}

    - name: Deploy to production
      run: |
        curl -X POST "${{ secrets.DEPLOY_WEBHOOK_URL }}" \
          -H "Content-Type: application/json" \
          -d '{"version":"${{ github.sha }}","environment":"production"}'

    - name: Notify Slack
      if: always()
      uses: 8398a7/action-slack@v3
      with:
        status: ${{ job.status }}
        channel: '#deployments'
        webhook_url: ${{ secrets.SLACK_# CoruNest - NGO Donation & Volunteer Management Platform

**"Organise. Fund. Mobilise."**

## Table of Contents

1. [Project Overview](#project-overview)
2. [Business Model & Monetization](#business-model--monetization)
3. [Legal Framework & Compliance](#legal-framework--compliance)
4. [Architecture & Technology Stack](#architecture--technology-stack)
5. [Security Framework](#security-framework)
6. [Development Environment Setup](#development-environment-setup)
7. [Database Design](#database-design)
8. [Feature Implementation Guide](#feature-implementation-guide)
9. [Testing Strategy](#testing-strategy)
10. [CI/CD Pipeline](#cicd-pipeline)
11. [Deployment & Infrastructure](#deployment--infrastructure)
12. [Mobile & PWA Implementation](#mobile--pwa-implementation)
13. [Maintenance & Operations](#maintenance--operations)
14. [Development Timeline](#development-timeline)
15. [Revenue Projections](#revenue-projections)

---

## Project Overview

### Mission Statement
CoruNest is a comprehensive, secure, and affordable donation and volunteer management platform specifically designed for small to medium NGOs in South Africa. The platform bridges the gap between traditional NGO management systems and modern digital fundraising needs.

### Target Market
- **Primary**: Small to medium NGOs (5-100 employees) in Cape Town and South Africa
- **Secondary**: International NGOs operating in Africa
- **Tertiary**: Community organizations, religious groups, schools

### Value Proposition
- **For NGOs**: Reduce administrative overhead by 60%, increase donation conversion by 40%
- **For Donors**: Transparent, secure donation process with real-time impact tracking
- **For Volunteers**: Streamlined signup and engagement tracking

### Key Differentiators
1. **Hybrid Architecture**: Public-facing simplicity with powerful admin tools
2. **South African Payment Integration**: Native Yoco and Ozow support
3. **Mobile-First Design**: PWA with native app capabilities
4. **Compliance-Ready**: POPIA, SARS, and international donor regulations
5. **Cost-Effective**: 80% cheaper than enterprise solutions

---

## Business Model & Monetization

### Revenue Streams

#### 1. SaaS Subscription Tiers

**Starter Plan - R299/month**
- Up to 3 active campaigns
- 100 donors/month
- Basic email templates
- Standard support
- 2.9% + R2.50 transaction fee

**Growth Plan - R699/month**
- Up to 10 active campaigns
- 500 donors/month
- Custom email templates
- Analytics dashboard
- Priority support
- 2.5% + R2.50 transaction fee

**Pro Plan - R1,299/month**
- Unlimited campaigns
- Unlimited donors
- Advanced analytics
- White-label options
- Custom integrations
- Dedicated support
- 2.2% + R2.50 transaction fee

**Enterprise Plan - Custom Pricing**
- Multi-tenant management
- API access
- Custom development
- On-premise deployment
- SLA guarantees

#### 2. Transaction-Based Revenue
- Payment processing fees: 0.5% markup on payment provider rates
- International payment processing: 1% additional fee
- Refund processing: R25 flat fee

#### 3. Value-Added Services
- **Setup & Migration**: R2,500 - R15,000 per NGO
- **Custom Development**: R850/hour
- **Training & Support**: R650/hour
- **Data Analytics Consulting**: R1,200/hour
- **Compliance Audits**: R5,000 - R25,000

#### 4. Partnership Revenue
- **Payment Provider Commissions**: 10-15% revenue share
- **Third-party Integrations**: 20% commission on referrals
- **Certification Programs**: R2,500 per certificate

### Freemium Strategy
- **Free Tier**: 1 campaign, 25 donors/month, basic features
- **Trial Period**: 30 days full access to Growth Plan
- **NGO Verification**: Reduced rates for registered NPOs

### Financial Projections (5-Year)

| Year | Subscribers | Monthly Revenue | Annual Revenue | Profit Margin |
|------|-------------|----------------|----------------|---------------|
| Y1   | 50          | R25,000        | R300,000       | -20%          |
| Y2   | 200         | R140,000       | R1,680,000     | 15%           |
| Y3   | 500         | R350,000       | R4,200,000     | 25%           |
| Y4   | 1,000       | R700,000       | R8,400,000     | 35%           |
| Y5   | 1,800       | R1,260,000     | R15,120,000    | 40%           |

---

## Legal Framework & Compliance

### South African Compliance

#### POPIA (Protection of Personal Information Act)
```php
// Implementation Requirements:
// 1. Explicit consent for data collection
// 2. Right to data portability
// 3. Right to deletion
// 4. Data minimization
// 5. Purpose limitation
```

**Required Features:**
- Cookie consent banner
- Privacy policy generator
- Data export functionality
- Account deletion with data scrubbing
- Audit logs for data access

#### SARS Compliance
- Section 18A certificate generation for donors
- Automatic tax calculation for donations
- Annual reporting to SARS for qualifying donations

#### NPO Registration Requirements
- Integration with Department of Social Development NPO database
- Verification of NPO registration status
- Compliance reporting tools

### International Compliance

#### GDPR (European Union)
- Data protection by design
- Privacy impact assessments
- Data breach notification (72-hour rule)

#### CCPA (California Consumer Privacy Act)
- Consumer rights implementation
- Data sale disclosure
- Opt-out mechanisms

### Terms of Service Framework

#### User Agreement Components
1. **Service Description**: Clear definition of platform capabilities
2. **User Responsibilities**: Acceptable use policies
3. **Payment Terms**: Billing, refunds, chargebacks
4. **Limitation of Liability**: Standard SaaS limitations
5. **Dispute Resolution**: South African jurisdiction, arbitration clauses

#### NGO Agreement Components
1. **Platform Usage Rights**: Multi-user access, data ownership
2. **Payment Processing**: Fee structure, settlement terms
3. **Data Security**: Mutual responsibilities
4. **Service Level Agreements**: Uptime guarantees, support response times
5. **Termination Clauses**: Data export, transition assistance

---

## Architecture & Technology Stack

### System Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Mobile App    │    │   Web Browser   │    │   Admin Panel   │
│   (Capacitor)   │    │  (Alpine.js)    │    │   (React.js)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
         ┌─────────────────────────────────────────────────────┐
         │              Load Balancer (Nginx)                  │
         └─────────────────────────────────────────────────────┘
                                 │
         ┌─────────────────────────────────────────────────────┐
         │            Laravel Application (PHP-FPM)            │
         └─────────────────────────────────────────────────────┘
                                 │
    ┌────────────┬───────────────┼───────────────┬─────────────┐
    │            │               │               │             │
┌───▼───┐  ┌────▼────┐  ┌───────▼──────┐  ┌────▼────┐  ┌────▼────┐
│ MySQL │  │  Redis  │  │   File       │  │  Queue  │  │ Elastic │
│   DB  │  │ Cache   │  │  Storage     │  │Workers  │  │ Search  │
└───────┘  └─────────┘  └──────────────┘  └─────────┘  └─────────┘
```

### Backend Technology Stack

#### Core Framework
- **PHP 8.2+**: Latest performance improvements, union types, readonly properties
- **Laravel 11**: Latest LTS with improved performance and security features
- **Composer 2.x**: Dependency management with performance optimizations

#### Database Layer
- **MySQL 8.0**: Primary database with JSON support and improved performance
- **Redis 7.x**: Session storage, cache, and queue driver
- **Elasticsearch 8.x**: Full-text search for campaigns and donors

#### Infrastructure Components
- **Nginx**: Web server and reverse proxy
- **PHP-FPM**: Process manager for optimal PHP performance
- **Supervisor**: Process monitoring for queue workers
- **Horizon**: Queue monitoring and management

#### Third-Party Integrations
- **Yoco API**: Primary payment processor for South African market
- **Ozow API**: Alternative payment processor with bank transfers
- **Mailgun/SendGrid**: Transactional email delivery
- **Sentry**: Error tracking and performance monitoring
- **Cloudflare**: CDN and DDoS protection

### Frontend Technology Stack

#### Public-Facing (Laravel Blade + Alpine.js)
```javascript
// Alpine.js Configuration
window.Alpine = {
    start() {
        Alpine.data('donationForm', donationFormData);
        Alpine.data('campaignGrid', campaignGridData);
        Alpine.data('volunteerSignup', volunteerSignupData);
        Alpine.start();
    }
};
```

**Key Libraries:**
- **Alpine.js 3.x**: Reactive UI components
- **Tailwind CSS 3.x**: Utility-first styling
- **Chart.js**: Data visualization for public stats
- **Axios**: HTTP client for API calls

#### Admin Dashboard (React.js)
```jsx
// React Architecture
src/
├── components/
│   ├── ui/           // Reusable UI components
│   ├── forms/        // Form components
│   ├── charts/       // Data visualization
│   └── layout/       // Layout components
├── pages/
│   ├── Dashboard/    // Main dashboard
│   ├── Campaigns/    // Campaign management
│   ├── Donations/    // Donation tracking
│   └── Analytics/    // Reporting and analytics
├── hooks/           // Custom React hooks
├── utils/           // Utility functions
└── services/        // API services
```

**Key Libraries:**
- **React 18**: Latest features including concurrent rendering
- **Inertia.js**: SPA-like experience with server-side routing
- **Recharts**: Professional data visualization
- **React Hook Form**: Performant form handling
- **React Query**: Server state management
- **Headless UI**: Accessible UI components

### Mobile Technology Stack

#### Progressive Web App (PWA)
```javascript
// Service Worker Configuration
const CACHE_NAME = 'corunest-v1.0.0';
const urlsToCache = [
    '/',
    '/campaigns',
    '/static/css/app.css',
    '/static/js/app.js',
    '/static/images/logo.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
});
```

#### Native App Wrapper (Capacitor)
```json
// capacitor.config.ts
{
    "appId": "com.corunest.app",
    "appName": "CoruNest",
    "webDir": "public",
    "bundledWebRuntime": false,
    "plugins": {
        "PushNotifications": {
            "presentationOptions": ["badge", "sound", "alert"]
        },
        "LocalNotifications": {
            "smallIcon": "ic_stat_icon_config_sample",
            "iconColor": "#488AFF"
        }
    }
}
```

---

## Security Framework

### Authentication & Authorization

#### Multi-Factor Authentication
```php
// Laravel Implementation
class TwoFactorController extends Controller
{
    public function enable(Request $request)
    {
        $user = $request->user();
        $secret = Google2FA::generateSecretKey();
        
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(
                Collection::times(8, fn() => RecoveryCode::generate())
            ))
        ]);
        
        return response()->json([
            'secret' => $secret,
            'qr_code' => Google2FA::getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            )
        ]);
    }
}
```

#### Role-Based Access Control (RBAC)
```php
// Permission System
class Permission extends Model
{
    // Permissions: manage_campaigns, manage_donations, view_analytics, etc.
}

class Role extends Model
{
    // Roles: super_admin, admin, accountant, event_manager, viewer
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
}

// Usage in Controllers
class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_campaigns')->except(['index', 'show']);
    }
}
```

### Data Security

#### Encryption Strategy
- **Database Encryption**: Sensitive fields using Laravel's built-in encryption
- **File Encryption**: Uploaded documents encrypted at rest
- **Transport Security**: TLS 1.3 for all communications
- **API Security**: JWT tokens with short expiry and refresh mechanism

#### Input Validation & Sanitization
```php
// Custom Form Requests
class DonationRequest extends FormRequest
{
    public function rules()
    {
        return [
            'amount' => ['required', 'numeric', 'min:10', 'max:1000000'],
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'donor_email' => ['required', 'email', 'max:255'],
            'donor_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'payment_method' => ['required', 'in:yoco,ozow'],
        ];
    }
    
    public function messages()
    {
        return [
            'amount.min' => 'Minimum donation amount is R10.',
            'amount.max' => 'Maximum donation amount is R1,000,000.',
        ];
    }
}
```

#### Rate Limiting
```php
// Custom Rate Limiters
RateLimiter::for('donations', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(3)->by($request->email.$request->ip());
});
```

### Security Monitoring

#### Audit Logging
```php
// Audit Log Model
class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model', 'model_id', 
        'changes', 'ip_address', 'user_agent'
    ];
    
    protected $casts = [
        'changes' => 'array'
    ];
}

// Usage with Observer
class CampaignObserver
{
    public function updated(Campaign $campaign)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model' => Campaign::class,
            'model_id' => $campaign->id,
            'changes' => $campaign->getChanges(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Payment Security

#### PCI DSS Compliance
- Never store card data on servers
- Use tokenization for recurring payments
- Implement secure card data transmission
- Regular security scans and penetration testing

#### Fraud Prevention
```php
class FraudDetectionService
{
    public function analyzeTransaction(array $transactionData): bool
    {
        $riskScore = 0;
        
        // Check for suspicious patterns
        $riskScore += $this->checkVelocity($transactionData);
        $riskScore += $this->checkGeolocation($transactionData);
        $riskScore += $this->checkAmountPattern($transactionData);
        
        return $riskScore < config('payments.fraud_threshold', 75);
    }
    
    private function checkVelocity(array $data): int
    {
        $recentTransactions = Donation::where('donor_email', $data['email'])
            ->where('created_at', '>', now()->subHours(24))
            ->count();
            
        return $recentTransactions > 5 ? 30 : 0;
    }
}
```

---

## Development Environment Setup

### Prerequisites
- **Docker Desktop**: Version 4.0+
- **Node.js**: Version 18+ with npm/yarn
- **Git**: Version 2.30+
- **IDE**: VS Code with recommended extensions

### Local Development Setup

#### Step 1: Repository Clone and Initial Setup
```bash
# Clone the repository
git clone https://github.com/your-org/corunest.git
cd corunest

# Copy environment files
cp .env.example .env
cp .env.testing.example .env.testing

# Generate application key
docker-compose exec app php artisan key:generate
```

#### Step 2: Docker Environment
```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
    environment:
      - APP_ENV=local
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: corunest
      MYSQL_USER: corunest
      MYSQL_PASSWORD: password
      MYSQL_ROOT_PASSWORD: root_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  mailhog:
    image: mailhog/mailhog
    ports:
      - "8025:8025"

  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.8.0
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
    ports:
      - "9200:9200"

volumes:
  mysql_data:
```

#### Step 3: Application Installation
```bash
# Install PHP dependencies
docker-compose exec app composer install

# Install Node.js dependencies
npm install

# Run database migrations
docker-compose exec app php artisan migrate --seed

# Build frontend assets
npm run dev

# Start queue workers
docker-compose exec app php artisan horizon

# Start file watcher for development
npm run watch
```

### IDE Configuration

#### VS Code Extensions
```json
{
    "recommendations": [
        "bmewburn.vscode-intelephense-client",
        "bradlc.vscode-tailwindcss",
        "ms-vscode.vscode-typescript-next",
        "esbenp.prettier-vscode",
        "ms-vscode-remote.remote-containers"
    ]
}
```

#### Development Scripts
```json
// package.json scripts
{
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "watch": "vite build --watch",
        "test": "phpunit && npm run test:js",
        "test:js": "jest",
        "test:e2e": "playwright test",
        "format": "prettier --write src/",
        "lint": "eslint src/ --fix"
    }
}
```

---

## Database Design

### Core Entity Relationships

```sql
-- Users table with role-based access
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('donor', 'volunteer', 'admin', 'staff', 'super_admin') DEFAULT 'donor',
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    profile JSON NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
);

-- Organizations/NGOs
CREATE TABLE organizations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    registration_number VARCHAR(100) NULL,
    tax_number VARCHAR(100) NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20) NULL,
    address JSON NULL,
    logo_path VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    social_links JSON NULL,
    settings JSON NULL,
    status ENUM('active', 'suspended', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    FULLTEXT idx_search (name, description)
);

-- Campaigns
CREATE TABLE campaigns (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    summary TEXT NOT NULL,
    description LONGTEXT NOT NULL,
    target_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    current_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'ZAR',
    goal_type ENUM('currency', 'item_count') DEFAULT 'currency',
    status ENUM('draft', 'active', 'completed', 'paused', 'archived') DEFAULT 'draft',
    start_date DATE NULL,
    end_date DATE NULL,
    featured BOOLEAN DEFAULT FALSE,
    image_path VARCHAR(500) NULL,
    gallery JSON NULL,
    metadata JSON NULL,
    seo_title VARCHAR(255) NULL,
    seo_description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_organization (organization_id),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_dates (start_date, end_date),
    FULLTEXT idx_search (title, summary, description)
);

-- Donations
CREATE TABLE donations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    campaign_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'ZAR',
    payment_provider ENUM('yoco', 'ozow', 'manual') NOT NULL,
    payment_method VARCHAR(50) NULL,
    transaction_id VARCHAR(255) NULL,
    provider_transaction_id VARCHAR(255) NULL,
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    donor_name VARCHAR(255) NOT NULL,
    donor_email VARCHAR(255) NOT NULL,
    donor_phone VARCHAR(20) NULL,
    donor_address JSON NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    message TEXT NULL,
    tax_deductible BOOLEAN DEFAULT TRUE,
    receipt_number VARCHAR(100) NULL,
    receipt_issued_at TIMESTAMP NULL,
    fee_amount DECIMAL(8,2) NULL,
    net_amount DECIMAL(10,2) NULL,
    metadata JSON NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_campaign (campaign_id),
    INDEX idx_organization (organization_id),
    INDEX idx_status (status),
    INDEX idx_provider (payment_provider),
    INDEX idx_transaction (transaction_id),
    INDEX idx_email (donor_email),
    INDEX idx_created (created_at)
);

-- Events
CREATE TABLE events (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    organization_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    location VARCHAR(500) NOT NULL,
    address JSON NULL,
    capacity INTEGER NULL,
    current_signups INTEGER DEFAULT 0,
    signup_limit INTEGER NULL,
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NOT NULL,
    signup_opens_at TIMESTAMP NULL,
    signup_closes_at TIMESTAMP NULL,
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    image_path VARCHAR(500) NULL,
    requirements TEXT NULL,
    contact_info JSON NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_organization (organization_id),
    INDEX idx_status (status),
    INDEX idx_datetime (starts_at, ends_at),
    FULLTEXT idx_search (title, description, location)
);

-- Volunteer Signups
CREATE TABLE volunteer_signups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    volunteer_name VARCHAR(255) NOT NULL,
    volunteer_email VARCHAR(255) NOT NULL,
    volunteer_phone VARCHAR(20) NULL,
    emergency_contact JSON NULL,
    skills TEXT NULL,
    availability JSON NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    notes TEXT NULL,
    admin_notes TEXT NULL,
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_event (event_id),
    INDEX idx_status (status),
    INDEX idx_email (volunteer_email)
);
```

### Advanced Database Features

#### Audit Logging
```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    organization_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    model VARCHAR(100) NOT NULL,
    model_id BIGINT UNSIGNED NOT NULL,
    changes JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_organization (organization_id),
    INDEX idx_model (model, model_id),
    INDEX idx_created (created_at)
);
```

#### Performance Optimization Indexes
```sql
-- Composite indexes for common queries
CREATE INDEX idx_donations_reporting ON donations (organization_id, status, created_at);
CREATE INDEX idx_campaigns_public ON campaigns (status, featured, start_date, end_date);
CREATE INDEX idx_events_upcoming ON events (organization_id, status, starts_at);

-- Partitioning for large tables (donations by year)
ALTER TABLE donations PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

## Feature Implementation Guide

### Public-Facing Features (Laravel Blade + Alpine.js)

#### Homepage Implementation
```php
// HomeController.php
class HomeController extends Controller
{
    public function index()
    {
        $featuredCampaigns = Campaign::featured()
            ->active()
            ->with(['organization'])
            ->limit(6)
            ->get();
            
        $totalDonations = Donation::completed()->sum('amount');
        $totalDonors = Donation::completed()->distinct('donor_email')->count();
        $stats = compact('totalDonations', 'totalDonors');
        
        return view('home', compact('featuredCampaigns', 'stats'));
    }
}
```

```html
<!-- home.blade.php -->
@extends('layouts.app')

@section('content')
<div x-data="homepage">
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Organise. Fund. Mobilise.</h1>
            <p class="text-xl mb-8">Empowering NGOs across South Africa with transparent, efficient donation management</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
                <div class="bg-white/10 rounded-lg p-6">
                    <div class="text-3xl font-bold">R{{ number_format($stats['totalDonations']) }}</div>
                    <div class="text-sm opacity-90">Total Donations</div>
                </div>
                <div class="bg-white/10 rounded-lg p-6">
                    <div class="text-3xl font-bold">{{ number_format($stats['totalDonors']) }}</div>
                    <div class="text-sm opacity-90">Generous Donors</div>
                </div>
                <div class="bg-white/10 rounded-lg p-6">
                    <div class="text-3xl font-bold">{{ $featuredCampaigns->count() }}</div>
                    <div class="text-sm opacity-90">Active Campaigns</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Campaigns -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Featured Campaigns</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($featuredCampaigns as $campaign)
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <img src="{{ $campaign->image_path ?? '/images/default-campaign.jpg' }}" 
                         alt="{{ $campaign->title }}" 
                         class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2">{{ $campaign->title }}</h3>
                        <p class="text-gray-600 mb-4">{{ Str::limit($campaign->summary, 100) }}</p>
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>R{{ number_format($campaign->current_amount) }} raised</span>
                                <span>{{ number_format(($campaign->current_amount / $campaign->target_amount) * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" 
                                     style="width: {{ min(100, ($campaign->current_amount / $campaign->target_amount) * 100) }}%"></div>
                            </div>
                        </div>
                        <a href="{{ route('campaigns.show', $campaign->slug) }}" 
                           class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Learn More
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('homepage', () => ({
        init() {
            // Initialize any interactive elements
            this.loadRecentActivity();
        },
        
        async loadRecentActivity() {
            try {
                const response = await fetch('/api/recent-activity');
                const data = await response.json();
                // Handle recent activity display
            } catch (error) {
                console.error('Failed to load recent activity:', error);
            }
        }
    }));
});
</script>
@endsection
```

#### Campaign Detail & Donation Flow
```php
// CampaignController.php
class CampaignController extends Controller
{
    public function show($slug)
    {
        $campaign = Campaign::where('slug', $slug)
            ->with(['organization', 'recentDonations' => function($query) {
                $query->completed()
                      ->where('is_anonymous', false)
                      ->latest()
                      ->limit(10);
            }])
            ->firstOrFail();
            
        $similarCampaigns = Campaign::where('organization_id', $campaign->organization_id)
            ->where('id', '!=', $campaign->id)
            ->active()
            ->limit(3)
            ->get();
            
        return view('campaigns.show', compact('campaign', 'similarCampaigns'));
    }
    
    public function donate(DonationRequest $request)
    {
        $campaign = Campaign::findOrFail($request->campaign_id);
        
        // Create pending donation
        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'organization_id' => $campaign->organization_id,
            'amount' => $request->amount,
            'donor_name' => $request->donor_name,
            'donor_email' => $request->donor_email,
            'donor_phone' => $request->donor_phone,
            'payment_provider' => $request->payment_method,
            'status' => 'pending',
            'message' => $request->message,
            'is_anonymous' => $request->boolean('is_anonymous', false)
        ]);
        
        // Initialize payment provider
        $paymentService = app(PaymentServiceInterface::class);
        $paymentResult = $paymentService->initializePayment($donation);
        
        if ($paymentResult['success']) {
            return redirect($paymentResult['redirect_url']);
        }
        
        return back()->withErrors(['payment' => 'Failed to initialize payment']);
    }
}
```

```html
<!-- campaigns/show.blade.php -->
@extends('layouts.app')

@section('content')
<div x-data="campaignDetail" class="min-h-screen">
    <!-- Campaign Header -->
    <section class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Campaign Image & Gallery -->
                <div>
                    <img src="{{ $campaign->image_path ?? '/images/default-campaign.jpg' }}" 
                         alt="{{ $campaign->title }}"
                         class="w-full h-64 md:h-96 object-cover rounded-lg shadow-lg">
                    
                    @if($campaign->gallery)
                    <div class="flex mt-4 space-x-2 overflow-x-auto">
                        @foreach(json_decode($campaign->gallery) as $image)
                        <img src="{{ $image }}" 
                             alt="Campaign gallery"
                             class="w-20 h-20 object-cover rounded cursor-pointer hover:opacity-80"
                             @click="showImage('{{ $image }}')">
                        @endforeach
                    </div>
                    @endif
                </div>
                
                <!-- Campaign Info & Donation Form -->
                <div>
                    <div class="mb-2">
                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                            {{ $campaign->organization->name }}
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ $campaign->title }}</h1>
                    <p class="text-lg text-gray-600 mb-6">{{ $campaign->summary }}</p>
                    
                    <!-- Progress Bar -->
                    <div class="bg-white rounded-lg p-6 shadow-sm mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-2xl font-bold text-green-600">
                                R{{ number_format($campaign->current_amount) }}
                            </span>
                            <span class="text-sm text-gray-600">
                                {{ number_format(($campaign->current_amount / $campaign->target_amount) * 100, 1) }}% complete
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                            <div class="bg-gradient-to-r from-green-500 to-blue-500 h-3 rounded-full transition-all duration-500" 
                                 style="width: {{ min(100, ($campaign->current_amount / $campaign->target_amount) * 100) }}%"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Goal: R{{ number_format($campaign->target_amount) }}</span>
                            <span>{{ $campaign->donations_count ?? 0 }} donors</span>
                        </div>
                    </div>
                    
                    <!-- Quick Donation Buttons -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                        <button @click="setDonationAmount(50)" 
                                class="bg-gray-100 hover:bg-blue-100 border-2 hover:border-blue-300 rounded-lg py-3 px-4 text-center transition-colors">
                            <div class="font-semibold">R50</div>
                        </button>
                        <button @click="setDonationAmount(100)" 
                                class="bg-gray-100 hover:bg-blue-100 border-2 hover:border-blue-300 rounded-lg py-3 px-4 text-center transition-colors">
                            <div class="font-semibold">R100</div>
                        </button>
                        <button @click="setDonationAmount(250)" 
                                class="bg-gray-100 hover:bg-blue-100 border-2 hover:border-blue-300 rounded-lg py-3 px-4 text-center transition-colors">
                            <div class="font-semibold">R250</div>
                        </button>
                        <button @click="setDonationAmount(500)" 
                                class="bg-gray-100 hover:bg-blue-100 border-2 hover:border-blue-300 rounded-lg py-3 px-4 text-center transition-colors">
                            <div class="font-semibold">R500</div>
                        </button>
                    </div>
                    
                    <!-- Donation Form -->
                    <form @submit.prevent="submitDonation" class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4">Make a Donation</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount (ZAR)</label>
                                <input type="number" 
                                       x-model="form.amount" 
                                       min="10" 
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter amount">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                <select x-model="form.payment_method" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="yoco">Credit/Debit Card (Yoco)</option>
                                    <option value="ozow">Bank Transfer (Ozow)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" 
                                       x-model="form.donor_name" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Your full name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" 
                                       x-model="form.donor_email" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="your@email.com">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number (Optional)</label>
                            <input type="tel" 
                                   x-model="form.donor_phone" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="+27 12 345 6789">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message (Optional)</label>
                            <textarea x-model="form.message" 
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Leave a message of support..."></textarea>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input type="checkbox" 
                                   x-model="form.is_anonymous" 
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-600">Make this donation anonymous</label>
                        </div>
                        
                        <button type="submit" 
                                :disabled="!isFormValid || loading"
                                class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                            <span x-show="!loading">Donate Now</span>
                            <span x-show="loading" class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                        
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            Secure payment processing. Your donation is eligible for tax deduction under Section 18A.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Campaign Description -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="prose prose-lg max-w-none">
                    {!! nl2br(e($campaign->description)) !!}
                </div>
            </div>
        </div>
    </section>
    
    <!-- Recent Donations -->
    <section class="py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <h3 class="text-2xl font-bold mb-6">Recent Supporters</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($campaign->recentDonations as $donation)
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold">{{ $donation->donor_name }}</div>
                                <div class="text-sm text-gray-600">{{ $donation->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="text-lg font-bold text-green-600">
                                R{{ number_format($donation->amount) }}
                            </div>
                        </div>
                        @if($donation->message)
                        <p class="text-sm text-gray-600 mt-2 italic">"{{ $donation->message }}"</p>
                        @endif
                    </div>
                    @empty
                    <div class="col-span-full text-center text-gray-500 py-8">
                        <div class="text-4xl mb-4">💝</div>
                        <p>Be the first to support this campaign!</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('campaignDetail', () => ({
        loading: false,
        form: {
            campaign_id: {{ $campaign->id }},
            amount: '',
            payment_method: 'yoco',
            donor_name: '',
            donor_email: '',
            donor_phone: '',
            message: '',
            is_anonymous: false
        },
        
        get isFormValid() {
            return this.form.amount >= 10 && 
                   this.form.donor_name.length > 0 && 
                   this.form.donor_email.length > 0 &&
                   this.form.donor_email.includes('@');
        },
        
        setDonationAmount(amount) {
            this.form.amount = amount;
        },
        
        showImage(imageSrc) {
            // Implement image lightbox functionality
            console.log('Show image:', imageSrc);
        },
        
        async submitDonation() {
            if (!this.isFormValid) return;
            
            this.loading = true;
            try {
                const response = await fetch('{{ route("campaigns.donate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Redirect to payment provider
                    window.location.href = data.redirect_url;
                } else {
                    // Handle validation errors
                    console.error('Donation failed:', data.errors);
                    alert('There was an error processing your donation. Please try again.');
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('Network error. Please check your connection and try again.');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
@endsection
```

### Payment Integration

#### Payment Service Architecture
```php
// app/Services/PaymentServiceInterface.php
interface PaymentServiceInterface
{
    public function initializePayment(Donation $donation): array;
    public function handleWebhook(Request $request): array;
    public function processRefund(Donation $donation, float $amount = null): array;
    public function getPaymentStatus(string $transactionId): array;
}

// app/Services/YocoPaymentService.php
class YocoPaymentService implements PaymentServiceInterface
{
    private $client;
    private $secretKey;
    
    public function __construct()
    {
        $this->secretKey = config('payments.yoco.secret_key');
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => config('payments.yoco.base_url'),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json'
            ]
        ]);
    }
    
    public function initializePayment(Donation $donation): array
    {
        try {
            $response = $this->client->post('checkouts', [
                'json' => [
                    'amount' => intval($donation->amount * 100), // Convert to cents
                    'currency' => $donation->currency,
                    'cancelUrl' => route('donations.cancelled', $donation->id),
                    'successUrl' => route('donations.success', $donation->id),
                    'failureUrl' => route('donations.failed', $donation->id),
                    'metadata' => [
                        'donation_id' => $donation->id,
                        'campaign_title' => $donation->campaign->title,
                        'donor_email' => $donation->donor_email
                    ]
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            // Update donation with transaction ID
            $donation->update([
                'transaction_id' => $data['id'],
                'status' => 'processing'
            ]);
            
            return [
                'success' => true,
                'redirect_url' => $data['redirectUrl'],
                'transaction_id' => $data['id']
            ];
            
        } catch (\Exception $e) {
            Log::error('Yoco payment initialization failed', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Payment initialization failed'
            ];
        }
    }
    
    public function handleWebhook(Request $request): array
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Yoco-Signature');
        
        // Verify webhook signature
        if (!$this->verifyWebhookSignature($payload, $signature)) {
            return ['success' => false, 'error' => 'Invalid signature'];
        }
        
        $event = json_decode($payload, true);
        
        switch ($event['type']) {
            case 'checkout.succeeded':
                return $this->handleSuccessfulPayment($event['data']);
                
            case 'checkout.failed':
                return $this->handleFailedPayment($event['data']);
                
            default:
                return ['success' => true, 'message' => 'Event not handled'];
        }
    }
    
    private function handleSuccessfulPayment(array $checkoutData): array
    {
        $donation = Donation::where('transaction_id', $checkoutData['id'])->first();
        
        if (!$donation) {
            return ['success' => false, 'error' => 'Donation not found'];
        }
        
        DB::transaction(function () use ($donation, $checkoutData) {
            // Update donation status
            $donation->update([
                'status' => 'completed',
                'provider_transaction_id' => $checkoutData['paymentId'] ?? null,
                'fee_amount' => ($checkoutData['fees'] ?? 0) / 100,
                'net_amount' => $donation->amount - (($checkoutData['fees'] ?? 0) / 100),
                'processed_at' => now(),
                'receipt_number' => $this->generateReceiptNumber($donation)
            ]);
            
            // Update campaign progress
            $donation->campaign->increment('current_amount', $donation->amount);
            
            // Send confirmation email
            Mail::to($donation->donor_email)
                ->send(new DonationReceiptMail($donation));
                
            // Audit log
            AuditLog::create([
                'user_id' => null,
                'action' => 'payment_completed',
                'model' => Donation::class,
                'model_id' => $donation->id,
                'changes' => ['status' => 'completed'],
                'ip_address' => request()->ip()
            ]);
        });
        
        return ['success' => true, 'donation_id' => $donation->id];
    }
    
    private function generateReceiptNumber(Donation $donation): string
    {
        $year = $donation->created_at->format('Y');
        $orgId = str_pad($donation->organization_id, 3, '0', STR_PAD_LEFT);
        $donationId = str_pad($donation->id, 6, '0', STR_PAD_LEFT);
        
        return "RC{$year}{$orgId}{$donationId}";
    }
    
    private function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, config('payments.yoco.webhook_secret'));
        return hash_equals($signature, $expectedSignature);
    }
}

// app/Services/OzowPaymentService.php
class OzowPaymentService implements PaymentServiceInterface
{
    // Similar implementation for Ozow API
    // Ozow uses different flow - redirect to their payment page
    // then handle return URLs and webhook notifications
}
```

### Admin Dashboard (React.js)

#### Dashboard Overview Component
```jsx
// resources/js/Pages/Admin/Dashboard.jsx
import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    LineChart, Line, AreaChart, Area, BarChart, Bar, 
    PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, 
    Tooltip, Legend, ResponsiveContainer 
} from 'recharts';
import { 
    CurrencyDollarIcon, 
    UsersIcon, 
    ChartBarIcon, 
    CalendarIcon,
    TrendingUpIcon,
    TrendingDownIcon
} from '@heroicons/react/24/outline';

export default function Dashboard({ auth, stats, chartData, recentDonations, upcomingEvents }) {
    const [timeRange, setTimeRange] = useState('30d');
    const [loading, setLoading] = useState(false);

    const statCards = [
        {
            title: 'Total Donations',
            value: `R${stats.totalDonations.toLocaleString()}`,
            change: stats.donationsChange,
            icon: CurrencyDollarIcon,
            color: 'green'
        },
        {
            title: 'Active Donors',
            value: stats.activeDonors.toLocaleString(),
            change: stats.donorsChange,
            icon: UsersIcon,
            color: 'blue'
        },
        {
            title: 'Active Campaigns',
            value: stats.activeCampaigns,
            change: stats.campaignsChange,
            icon: ChartBarIcon,
            color: 'purple'
        },
        {
            title: 'Upcoming Events',
            value: stats.upcomingEvents,
            change: stats.eventsChange,
            icon: CalendarIcon,
            color: 'orange'
        }
    ];

    const refreshData = async (range) => {
        setLoading(true);
        setTimeRange(range);
        
        try {
            const response = await fetch(`/admin/dashboard/data?range=${range}`);
            const newData = await response.json();
            // Update chart data through Inertia or state management
        } catch (error) {
            console.error('Failed to refresh dashboard data:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Dashboard Overview</h2>}
        >
            <Head title="Dashboard" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Time Range Selector */}
                    <div className="mb-6">
                        <div className="flex space-x-2">
                            {['7d', '30d', '90d', '1y'].map((range) => (
                                <button
                                    key={range}
                                    onClick={() => refreshData(range)}
                                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                        timeRange === range
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                    }`}
                                    disabled={loading}
                                >
                                    {range === '7d' && 'Last 7 Days'}
                                    {range === '30d' && 'Last 30 Days'}
                                    {range === '90d' && 'Last 90 Days'}
                                    {range === '1y' && 'Last Year'}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        {statCards.map((stat, index) => (
                            <div key={index} className="bg-white overflow-hidden shadow-sm rounded-lg">
                                <div className="p-6">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <stat.icon className={`h-8 w-8 text-${stat.color}-600`} />
                                        </div>
                                        <div className="ml-4 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    {stat.title}
                                                </dt>
                                                <dd className="text-2xl font-semibold text-gray-900">
                                                    {stat.value}
                                                </dd>
                                            </dl>
                                        </div>
                                        <div className="flex items-center">
                                            {stat.change >= 0 ? (
                                                <TrendingUpIcon className="h-4 w-4 text-green-500" />
                                            ) : (
                                                <TrendingDownIcon className="h-4 w-4 text-red-500" />
                                            )}
                                            <span className={`text-sm font-medium ml-1 ${
                                                stat.change >= 0 ? 'text-green-600' : 'text-red-600'
                                            }`}>
                                                {Math.abs(stat.change)}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Charts Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        {/* Donations Over Time */}
                        <div className="bg-white p-6 rounded-lg shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Donations Over Time</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <AreaChart data={chartData.donations}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="date" />
                                    <YAxis />
                                    <Tooltip formatter={(value) => [`R${value.toLocaleString()}`, 'Amount']} />
                                    <Area 
                                        type="monotone" 
                                        dataKey="amount" 
                                        stroke="#3B82F6" 
                                        fill="#3B82F6" 
                                        fillOpacity={0.6} 
                                    />
                                </AreaChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Campaign Performance */}
                        <div className="bg-white p-6 rounded-lg shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Campaigns</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <BarChart data={chartData.campaigns}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="name" />
                                    <YAxis />
                                    <Tooltip formatter={(value) => [`R${value.toLocaleString()}`, 'Raised']} />
                                    <Bar dataKey="raised" fill="#10B981" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Payment Methods Distribution */}
                        <div className="bg-white p-6 rounded-lg shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <PieChart>
                                    <Pie
                                        data={chartData.paymentMethods}
                                        cx="50%"
                                        cy="50%"
                                        outerRadius={80}
                                        dataKey="value"
                                        label={({name, percent}) => `${name} ${(percent * 100).toFixed(0)}%`}
                                    >
                                        {chartData.paymentMethods.map((entry, index) => (
                                            <Cell key={`cell-${index}`} fill={entry.color} />
                                        ))}
                                    </Pie>
                                    <Tooltip />
                                </PieChart>
                            </ResponsiveContainer>
                        </div>

                        {/* Donor Growth */}
                        <div className="bg-white p-6 rounded-lg shadow-sm">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Donor Growth</h3>
                            <ResponsiveContainer width="100%" height={300}>
                                <LineChart data={chartData.donorGrowth}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="month" />
                                    <YAxis />
                                    <Tooltip />
                                    <Legend />
                                    <Line 
                                        type="monotone" 
                                        dataKey="newDonors" 
                                        stroke="#8B5CF6" 
                                        strokeWidth={2}
                                        name="New Donors"
                                    />
                                    <Line 
                                        type="monotone" 
                                        dataKey="returningDonors" 
                                        stroke="#F59E0B" 
                                        strokeWidth={2}
                                        name="Returning Donors"
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    {/* Recent Activity & Quick Actions */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Recent Donations */}
                        <div className="lg:col-span-2 bg-white rounded-lg shadow-sm">
                            <div className="p-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Recent Donations</h3>
                                    <a href="/admin/donations" className="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        View all
                                    </a>
                                </div>
                                <div className="overflow-hidden">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Donor
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Campaign
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Amount
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {recentDonations.map((donation) => (
                                                <tr key={donation.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {donation.donor_name}
                                                        <div className="text-xs text-gray-500">{donation.donor_email}</div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {donation.campaign.title}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        R{donation.amount.toLocaleString()}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                                            donation.status === 'completed' 
                                                                ? 'bg-green-100 text-green-800'
                                                                : donation.status === 'pending'
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}>
                                                            {donation.status}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {/* Quick Actions & Upcoming Events */}
                        <div className="space-y-6">
                            {/* Quick Actions */}
                            <div className="bg-white p-6 rounded-lg shadow-sm">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                                <div className="space-y-3">
                                    <a href="/admin/campaigns/create" 
                                       className="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center block">
                                        Create Campaign
                                    </a>
                                    <a href="/admin/events/create"
                                       className="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-center block">
                                        Create Event
                                    </a>
                                    <a href="/admin/donations/export"
                                       className="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-center block">
                                        Export Reports
                                    </a>
                                    <a href="/admin/email/compose"
                                       className="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors text-center block">
                                        Send Email
                                    </a>
                                </div>
                            </div>

                            {/* Upcoming Events */}
                            <div className="bg-white p-6 rounded-lg shadow-sm">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900">Upcoming Events</h3>
                                    <a href="/admin/events" className="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        View all
                                    </a>
                                </div>
                                <div className="space-y-3">
                                    {upcomingEvents.map((event) => (
                                        <div key={event.id} className="border-l-4 border-blue-500 pl-4 py-2">
                                            <div className="text-sm font-medium text-gray-900">{event.title}</div>
                                            <div className="text-xs text-gray-500">
                                                {new Date(event.starts_at).toLocaleDateString()} at {event.location}
                                            </div>
                                            <div className="text-xs text-blue-600">
                                                {event.signups_count}/{event.capacity || '∞'} signed up
                                            </div>
                                        </div>
                                    ))}
                                    {upcomingEvents.length === 0 && (
                                        <div className="text-gray-500 text-sm text-center py-4">
                                            No upcoming events
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
```

#### Campaign Management Component
```jsx
// resources/js/Pages/Admin/Campaigns/Index.jsx
import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { 
    PlusIcon, 
    PencilIcon, 
    TrashIcon, 
    EyeIcon,
    ChartBarIcon 
} from '@heroicons/react/24/outline';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function CampaignsIndex({ auth, campaigns, filters, pagination }) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [campaignToDelete, setCampaignToDelete] = useState(null);
    
    const { data, setData, get, processing } = useForm({
        search: filters.search || '',
        status: filters.status || '',
        sort: filters.sort || 'created_at',
        direction: filters.direction || 'desc'
    });

    const handleSearch = (e) => {
        e.preventDefault();
        get(route('admin.campaigns.index'), {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (column) => {
        const direction = data.sort === column && data.direction === 'asc' ? 'desc' : 'asc';
        setData({...data, sort: column, direction});
        get(route('admin.campaigns.index'), {
            preserveState: true,
            replace: true
        });
    };

    const confirmDelete = (campaign) => {
        setCampaignToDelete(campaign);
        setShowDeleteModal(true);
    };

    const deleteCampaign = () => {
        router.delete(route('admin.campaigns.destroy', campaignToDelete.id), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setCampaignToDelete(null);
            }
        });
    };

    const getStatusColor = (status) => {
        const colors = {
            'draft': 'bg-gray-100 text-gray-800',
            'active': 'bg-green-100 text-green-800',
            'completed': 'bg-blue-100 text-blue-800',
            'paused': 'bg-yellow-100 text-yellow-800',
            'archived': 'bg-red-100 text-red-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">Campaign Management</h2>
                    <Link
                        href={route('admin.campaigns.create')}
                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Create Campaign
                    </Link>
                </div>
            }
        >
            <Head title="Campaigns" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input
                                    type="text"
                                    value={data.search}
                                    onChange={(e) => setData('search', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Search campaigns..."
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="paused">Paused</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                                <select
                                    value={data.sort}
                                    onChange={(e) => setData('sort', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="created_at">Created Date</option>
                                    <option value="title">Title</option>
                                    <option value="target_amount">Target Amount</option>
                                    <option value="current_amount">Current Amount</option>
                                    <option value="end_date">End Date</option>
                                </select>
                            </div>
                            <div className="flex items-end">
                                <PrimaryButton type="submit" disabled={processing}>
                                    Search
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    {/* Campaigns Table */}
                    <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th 
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('title')}
                                        >
                                            Campaign
                                        </th>
                                        <th 
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('status')}
                                        >
                                            Status
                                        </th>
                                        <th 
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('target_amount')}
                                        >
                                            Goal
                                        </th>
                                        <th 
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('current_amount')}
                                        >
                                            Raised
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Progress
                                        </th>
                                        <th 
                                            className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                            onClick={() => handleSort('created_at')}
                                        >
                                            Created
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {campaigns.data.map((campaign) => {
                                        const progress = Math.min(100, (campaign.current_amount / campaign.target_amount) * 100);
                                        
                                        return (
                                            <tr key={campaign.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="flex-shrink-0 h-12 w-12">
                                                            <img 
                                                                className="h-12 w-12 rounded-lg object-cover" 
                                                                src={campaign.image_path || '/images/default-campaign.jpg'} 
                                                                alt={campaign.title} 
                                                            />
                                                        </div>
                                                        <div className="ml-4">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {campaign.title}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {campaign.slug}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(campaign.status)}`}>
                                                        {campaign.status}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    R{campaign.target_amount.toLocaleString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    R{campaign.current_amount.toLocaleString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                            <div 
                                                                className="bg-blue-600 h-2 rounded-full" 
                                                                style={{ width: `${progress}%` }}
                                                            ></div>
                                                        </div>
                                                        <span className="text-sm text-gray-600">
                                                            {progress.toFixed(1)}%
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {new Date(campaign.created_at).toLocaleDateString()}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center justify-end space-x-2">
                                                        <Link
                                                            href={route('campaigns.show', campaign.slug)}
                                                            className="text-gray-400 hover:text-gray-600"
                                                            title="View Public Page"
                                                        >
                                                            <EyeIcon className="h-4 w-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('admin.campaigns.analytics', campaign.id)}
                                                            className="text-blue-400 hover:text-blue-600"
                                                            title="Analytics"
                                                        >
                                                            <ChartBarIcon className="h-4 w-4" />
                                                        </Link>
                                                        <Link
                                                            href={route('admin.campaigns.edit', campaign.id)}
                                                            className="text-indigo-400 hover:text-indigo-600"
                                                            title="Edit"
                                                        >
                                                            <PencilIcon className="h-4 w-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => confirmDelete(campaign)}
                                                            className="text-red-400 hover:text-red-600"
                                                            title="Delete"
                                                        >
                                                            <TrashIcon className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {campaigns.data.length > 0 && (
                            <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex-1 flex justify-between sm:hidden">
                                        {pagination.prev_page_url && (
                                            <Link
                                                href={pagination.prev_page_url}
                                                className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Previous
                                            </Link>
                                        )}
                                        {pagination.next_page_url && (
                                            <Link
                                                href={pagination.next_page_url}
                                                className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                Next
                                            </Link>
                                        )}
                                    </div>
                                    <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p className="text-sm text-gray-700">
                                                Showing{' '}
                                                <span className="font-medium">{pagination.from}</span>{' '}
                                                to{' '}
                                                <span className="font-medium">{pagination.to}</span>{' '}
                                                of{' '}
                                                <span className="font-medium">{pagination.total}</span>{' '}
                                                results
                                            </p>
                                        </div>
                                        <div>
                                            <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                {/* Pagination links would be rendered here */}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {campaigns.data.length === 0 && (
                        <div className="bg-white rounded-lg shadow-sm p-12 text-center">
                            <div className="text-gray-500 text-lg mb-4">No campaigns found</div>
                            <Link
                                href={route('admin.campaigns.create')}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                            >
                                <PlusIcon className="h-4 w-4 mr-2" />
                                Create Your First Campaign
                            </Link>
                        </div>
                    )}
                </div>
            </div>

            {/* Delete Confirmation Modal */}
            <Modal show={showDeleteModal} onClose={() => setShowDeleteModal(false)}>
                <div className="p-6">
                    <div className="flex items-center mb-4">
                        <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <TrashIcon className="h-6 w-6 text-red-600" />
                        </div>
                    </div>
                    <div className="text-center">
                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-2">
                            Delete Campaign
                        </h3>
                        <div className="text-sm text-gray-500 mb-6">
                            Are you sure you want to delete "{campaignToDelete?.title}"? This action cannot be undone and will also delete all associated donations.
                        </div>
                        <div className="flex justify-center space-x-3">
                            <SecondaryButton onClick={() => setShowDeleteModal(false)}>
                                Cancel
                            </SecondaryButton>
                            <DangerButton onClick={deleteCampaign}>
                                Delete Campaign
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
```

### Progressive Web App (PWA) Implementation

#### Service Worker Configuration
```javascript
// public/sw.js
const CACHE_NAME = 'corunest-v1.0.0';
const STATIC_ASSETS = [
    '/',
    '/campaigns',
    '/events',
    '/manifest.json',
    '/css/app.css',
    '/js/app.js',
    '/images/logo-192.png',
    '/images/logo-512.png',
    '/offline.html'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('Service Worker installed');
                self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== CACHE_NAME)
                    .map((cacheName) => caches.delete(cacheName))
            );
        }).then(() => {
            console.log('Service Worker activated');
            clients.claim();
        })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip external requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                // Return cached version if available
                if (cachedResponse) {
                    // Update cache in background for next time
                    updateCache(event.request);
                    return cachedResponse;
                }

                // Otherwise fetch from network
                return fetch(event.request)
                    .then((networkResponse) => {
                        // Don't cache if not a success response
                        if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                            return networkResponse;
                        }

                        // Cache the response for future use
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME)
                            .then((cache) => {
                                cache.put(event.request, responseToCache);
                            });

                        return networkResponse;
                    })
                    .catch(() => {
                        // Network failed, try to serve offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                        
                        // For other requests, just throw the error
                        throw new Error('Network request failed and no cache available');
                    });
            })
    );
});

// Helper function to update cache in background
function updateCache(request) {
    fetch(request)
        .then((response) => {
            if (response && response.status === 200 && response.type === 'basic') {
                const responseToCache = response.clone();
                caches.open(CACHE_NAME)
                    .then((cache) => {
                        cache.put(request, responseToCache);
                    });
            }
        })
        .catch((error) => {
            console.log('Background cache update failed:', error);
        });
}

// Handle background sync for offline actions (optional)
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

// Background sync function
function doBackgroundSync() {
    // Handle any queued offline actions here
    // This could include API calls that failed while offline
    return Promise.resolve();
}

// Handle push notifications (optional)
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const data = event.data.json();
    const options = {
        body: data.body,
        icon: '/images/logo-192.png',
        badge: '/images/logo-192.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: data.primaryKey || '1'
        },
        actions: [
            {
                action: 'explore',
                title: 'View',
                icon: '/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/images/xmark.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    } else if (event.action === 'close') {
        // Notification closed, no action needed
        return;
    } else {
        // Default action - open the app
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Log service worker messages
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
```

