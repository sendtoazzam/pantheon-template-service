<?php

namespace App\Console\Commands;

use App\Services\LoggerService;
use Illuminate\Console\Command;

class TestLoggingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pantheon:test-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Pantheon logging system with different log levels';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Pantheon logging system...');

        // Test different log levels
        LoggerService::success('This is a success message from command');
        LoggerService::error('This is an error message from command');
        LoggerService::warning('This is a warning message from command');
        LoggerService::info('This is an info message from command');
        LoggerService::debug('This is a debug message from command');
        LoggerService::api('This is an API log message from command');
        LoggerService::activity('This is an activity log message from command');
        LoggerService::userAction(1, 'command_test', ['command' => 'pantheon:test-logs']);
        LoggerService::systemEvent('logging_test_command_executed');

        $prefix = config('app_logging.log_prefix')();
        $date = now()->format('Y-m-d');
        
        $this->info('Log test completed!');
        $this->line('Check the following log files in storage/logs/:');
        $this->line("- {$prefix}-{$date}.log");
        $this->line("- {$prefix}-success-{$date}.log");
        $this->line("- {$prefix}-error-{$date}.log");
        $this->line("- {$prefix}-warning-{$date}.log");
        $this->line("- {$prefix}-api-{$date}.log");
        $this->line("- {$prefix}-activity-{$date}.log");

        return Command::SUCCESS;
    }
}
