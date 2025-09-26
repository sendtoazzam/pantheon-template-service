<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ProcessBookingSuccessMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:process-success-messages 
                            {--merchant-id= : Process for specific merchant ID}
                            {--days=1 : Number of days to look back for successful bookings}
                            {--dry-run : Show what would be processed without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process successful booking messages and send notifications to users and merchants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $merchantId = $this->option('merchant-id');
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Processing booking success messages...");
        $this->info("Looking back {$days} day(s)");
        
        if ($merchantId) {
            $this->info("Filtering for merchant ID: {$merchantId}");
        }

        if ($dryRun) {
            $this->warn("DRY RUN MODE - No actual messages will be sent");
        }

        try {
            // Get successful bookings from the specified period
            $query = Booking::where('status', 'confirmed')
                ->where('created_at', '>=', now()->subDays($days))
                ->where('created_at', '<=', now());

            if ($merchantId) {
                $query->where('merchant_id', $merchantId);
            }

            $bookings = $query->with(['user', 'merchant', 'merchant.user'])->get();

            $this->info("Found {$bookings->count()} successful bookings to process");

            if ($bookings->isEmpty()) {
                $this->info("No bookings to process");
                return 0;
            }

            $processed = 0;
            $errors = 0;

            foreach ($bookings as $booking) {
                try {
                    $this->processBookingSuccess($booking, $dryRun);
                    $processed++;
                    
                    if ($this->output->isVerbose()) {
                        $this->line("✓ Processed booking ID: {$booking->id} for user: {$booking->user->name}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Failed to process booking ID: {$booking->id} - {$e->getMessage()}");
                    Log::error("Booking success message processing failed", [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Processing complete!");
            $this->info("Successfully processed: {$processed}");
            $this->info("Errors: {$errors}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error("Booking success message command failed", [
                'error' => $e->getMessage(),
                'merchant_id' => $merchantId,
                'days' => $days,
            ]);
            return 1;
        }
    }

    /**
     * Process success message for a single booking
     */
    private function processBookingSuccess(Booking $booking, bool $dryRun = false): void
    {
        // Check if we've already processed this booking
        $existingNotification = Notification::where('user_id', $booking->user_id)
            ->where('category', 'booking')
            ->where('data->booking_id', $booking->id)
            ->where('title', 'like', '%Success%')
            ->first();

        if ($existingNotification) {
            if ($this->output->isVerbose()) {
                $this->line("Skipping booking ID: {$booking->id} - already processed");
            }
            return;
        }

        if (!$dryRun) {
            DB::beginTransaction();

            try {
                // Create success notification for user
                $this->createBookingSuccessNotification($booking);

                // Create success notification for merchant
                $this->createMerchantSuccessNotification($booking);

                // Send email notification if configured
                $this->sendBookingSuccessEmail($booking);

                // Update booking with success processing timestamp
                $booking->update([
                    'success_processed_at' => now(),
                ]);

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            $this->line("Would process booking ID: {$booking->id} for user: {$booking->user->name}");
        }
    }

    /**
     * Create booking success notification for user
     */
    private function createBookingSuccessNotification(Booking $booking): void
    {
        Notification::create([
            'user_id' => $booking->user_id,
            'type' => 'success',
            'category' => 'booking',
            'title' => '🎉 Booking Confirmed Successfully!',
            'message' => "Congratulations! Your booking for {$booking->service_name} has been confirmed. We're excited to serve you!",
            'data' => [
                'booking_id' => $booking->id,
                'external_booking_id' => $booking->external_booking_id,
                'service_name' => $booking->service_name,
                'booking_date' => $booking->booking_date,
                'booking_time' => $booking->booking_time,
                'merchant_name' => $booking->merchant->business_name,
                'amount' => $booking->price,
                'currency' => $booking->currency,
            ],
            'priority' => 'high',
            'is_urgent' => false,
            'is_actionable' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * Create booking success notification for merchant
     */
    private function createMerchantSuccessNotification(Booking $booking): void
    {
        Notification::create([
            'user_id' => $booking->merchant->user_id,
            'type' => 'success',
            'category' => 'booking',
            'title' => '📅 New Booking Confirmed!',
            'message' => "Great news! You have a new confirmed booking for {$booking->service_name} from {$booking->user->name}.",
            'data' => [
                'booking_id' => $booking->id,
                'external_booking_id' => $booking->external_booking_id,
                'service_name' => $booking->service_name,
                'booking_date' => $booking->booking_date,
                'booking_time' => $booking->booking_time,
                'customer_name' => $booking->user->name,
                'customer_email' => $booking->user->email,
                'amount' => $booking->price,
                'currency' => $booking->currency,
            ],
            'priority' => 'high',
            'is_urgent' => true,
            'is_actionable' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * Send booking success email
     */
    private function sendBookingSuccessEmail(Booking $booking): void
    {
        try {
            // This would typically use a Mailable class
            // For now, we'll just log that we would send an email
            Log::info('Booking success email would be sent', [
                'booking_id' => $booking->id,
                'user_email' => $booking->user->email,
                'merchant_email' => $booking->merchant->user->email,
            ]);

            // In a real implementation, you would do:
            // Mail::to($booking->user->email)->send(new BookingSuccessMail($booking));
            // Mail::to($booking->merchant->user->email)->send(new NewBookingMail($booking));

        } catch (\Exception $e) {
            Log::error('Failed to send booking success email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
