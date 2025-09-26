<?php

namespace App\Console\Commands;

use App\Models\ApiCallLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupApiLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:cleanup-logs 
                            {--days=14 : Number of days to keep logs (default: 14)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old API call logs older than specified days (default: 14 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up API logs older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})");

        // Count logs to be deleted
        $logsToDelete = ApiCallLog::where('called_at', '<', $cutoffDate)->count();
        
        if ($logsToDelete === 0) {
            $this->info('No logs found to delete.');
            return 0;
        }

        $this->info("Found {$logsToDelete} logs to delete.");

        if ($dryRun) {
            $this->warn('DRY RUN: No logs were actually deleted.');
            $this->table(
                ['Status', 'Count', 'Size (MB)'],
                [
                    ['To Delete', $logsToDelete, $this->getLogsSize($cutoffDate)],
                    ['Remaining', ApiCallLog::where('called_at', '>=', $cutoffDate)->count(), $this->getLogsSize()],
                ]
            );
            return 0;
        }

        if (!$force) {
            if (!$this->confirm("Are you sure you want to delete {$logsToDelete} API logs?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Get size before deletion for reporting
        $sizeBefore = $this->getLogsSize();
        $countBefore = ApiCallLog::count();

        // Delete old logs
        $deletedCount = ApiCallLog::where('called_at', '<', $cutoffDate)->delete();

        $sizeAfter = $this->getLogsSize();
        $countAfter = ApiCallLog::count();

        $this->info("Successfully deleted {$deletedCount} API logs.");
        $this->table(
            ['Metric', 'Before', 'After', 'Difference'],
            [
                ['Log Count', number_format($countBefore), number_format($countAfter), '-' . number_format($countBefore - $countAfter)],
                ['Database Size', $sizeBefore . ' MB', $sizeAfter . ' MB', '-' . round($sizeBefore - $sizeAfter, 2) . ' MB'],
            ]
        );

        return 0;
    }

    /**
     * Get the size of API logs in MB
     */
    private function getLogsSize(Carbon $cutoffDate = null): float
    {
        $query = ApiCallLog::query();
        
        if ($cutoffDate) {
            $query->where('called_at', '<', $cutoffDate);
        }

        // Get total size of response_body and request_body columns
        $totalSize = $query->sum('response_size_bytes');
        
        return round($totalSize / 1024 / 1024, 2);
    }
}
