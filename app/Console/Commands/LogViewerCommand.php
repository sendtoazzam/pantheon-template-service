<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogViewerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pantheon:logs {type?} {--lines=10 : Number of lines to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View Pantheon application logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type') ?? 'all';
        $lines = $this->option('lines');
        $date = now()->format('Y-m-d');
        
        $logTypes = [
            'all' => 'All logs',
            'success' => 'Success logs',
            'error' => 'Error logs', 
            'warning' => 'Warning logs',
            'api' => 'API logs',
            'activity' => 'Activity logs',
            'main' => 'Main application logs'
        ];

        if (!array_key_exists($type, $logTypes)) {
            $this->error("Invalid log type. Available types: " . implode(', ', array_keys($logTypes)));
            return Command::FAILURE;
        }

        $this->info("Showing {$logTypes[$type]} (last {$lines} lines):");
        $this->line('');

        if ($type === 'all') {
            foreach (array_keys($logTypes) as $logType) {
                if ($logType === 'all') continue;
                $this->showLogFile($logType, $date, $lines);
            }
        } else {
            $this->showLogFile($type, $date, $lines);
        }

        return Command::SUCCESS;
    }

    private function showLogFile(string $type, string $date, int $lines): void
    {
        $logFile = $this->getLogFilePath($type, $date);
        
        if (!File::exists($logFile)) {
            $this->warn("Log file not found: {$logFile}");
            return;
        }

        $this->line("=== {$type} logs ===");
        $this->line("File: {$logFile}");
        
        $content = File::get($logFile);
        $logLines = explode("\n", $content);
        $lastLines = array_slice($logLines, -$lines);
        
        foreach ($lastLines as $line) {
            if (trim($line)) {
                $this->line($line);
            }
        }
        
        $this->line('');
    }

    private function getLogFilePath(string $type, string $date): string
    {
        $basePath = storage_path('logs');
        $prefix = config('app_logging.log_prefix')();
        
        return match($type) {
            'success' => "{$basePath}/{$prefix}-success-{$date}.log",
            'error' => "{$basePath}/{$prefix}-error-{$date}.log",
            'warning' => "{$basePath}/{$prefix}-warning-{$date}.log",
            'api' => "{$basePath}/{$prefix}-api-{$date}.log",
            'activity' => "{$basePath}/{$prefix}-activity-{$date}.log",
            'main' => "{$basePath}/{$prefix}-{$date}.log",
            default => "{$basePath}/{$prefix}-{$date}.log"
        };
    }
}
