<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowAppConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the current application configuration for logging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Application Logging Configuration');
        $this->line('================================');
        $this->line('');
        
        $appName = config('app_logging.app_name');
        $logPrefix = config('app_logging.log_prefix')();
        $date = now()->format('Y-m-d');
        
        $this->line("App Name: {$appName}");
        $this->line("Log Prefix: {$logPrefix}");
        $this->line("Current Date: {$date}");
        $this->line('');
        
        $this->info('Log Channels:');
        $this->line('- ' . $logPrefix . '-daily');
        $this->line('- ' . $logPrefix . '-success');
        $this->line('- ' . $logPrefix . '-error');
        $this->line('- ' . $logPrefix . '-warning');
        $this->line('- ' . $logPrefix . '-api');
        $this->line('- ' . $logPrefix . '-activity');
        $this->line('');
        
        $this->info('Log Files (today):');
        $this->line('- ' . $logPrefix . '-' . $date . '.log');
        $this->line('- ' . $logPrefix . '-success-' . $date . '.log');
        $this->line('- ' . $logPrefix . '-error-' . $date . '.log');
        $this->line('- ' . $logPrefix . '-warning-' . $date . '.log');
        $this->line('- ' . $logPrefix . '-api-' . $date . '.log');
        $this->line('- ' . $logPrefix . '-activity-' . $date . '.log');
        $this->line('');
        
        $this->info('To change the app name, update APP_NAME in your .env file');
        $this->line('Example: APP_NAME="My New App"');
        
        return Command::SUCCESS;
    }
}
