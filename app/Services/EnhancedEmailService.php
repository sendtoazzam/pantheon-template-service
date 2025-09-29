<?php

namespace App\Services;

use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class EnhancedEmailService
{
    protected $provider;
    protected $configuration;

    public function __construct($providerName = null)
    {
        $this->setProvider($providerName);
    }

    /**
     * Set email provider
     */
    public function setProvider($providerName = null)
    {
        if ($providerName) {
            $this->provider = NotificationSetting::where('provider_type', 'email')
                                                ->where('provider_name', $providerName)
                                                ->where('is_active', true)
                                                ->first();
        } else {
            $this->provider = NotificationSetting::getDefaultProvider('email');
        }

        if (!$this->provider) {
            throw new \Exception('No active email provider found');
        }

        $this->configuration = $this->provider->configuration;
        $this->configureMailer();
    }

    /**
     * Configure Laravel mailer based on provider
     */
    protected function configureMailer()
    {
        $mailerName = 'email_' . $this->provider->provider_name;
        
        Config::set("mail.mailers.{$mailerName}", [
            'transport' => $this->configuration['transport'] ?? 'smtp',
            'host' => $this->configuration['host'] ?? null,
            'port' => $this->configuration['port'] ?? 587,
            'encryption' => $this->configuration['encryption'] ?? 'tls',
            'username' => $this->configuration['username'] ?? null,
            'password' => $this->configuration['password'] ?? null,
            'timeout' => $this->configuration['timeout'] ?? null,
            'local_domain' => $this->configuration['local_domain'] ?? null,
            'verify_peer' => $this->configuration['verify_peer'] ?? true,
        ]);

        // Set additional configuration for specific providers
        switch ($this->provider->provider_name) {
            case 'postmark':
                Config::set("services.postmark.token", $this->configuration['token']);
                break;
            case 'resend':
                Config::set("services.resend.key", $this->configuration['key']);
                break;
            case 'ses':
                Config::set("services.ses.key", $this->configuration['key']);
                Config::set("services.ses.secret", $this->configuration['secret']);
                Config::set("services.ses.region", $this->configuration['region']);
                break;
        }

        // Set as default mailer
        Config::set('mail.default', $mailerName);
    }

    /**
     * Send email
     */
    public function sendEmail($to, $subject, $message, $options = [])
    {
        try {
            $fromEmail = $this->configuration['from_email'] ?? config('mail.from.address');
            $fromName = $this->configuration['from_name'] ?? config('mail.from.name');

            $mailData = [
                'subject' => $subject,
                'message' => $message,
                'to' => $to,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
            ];

            // Merge additional options
            $mailData = array_merge($mailData, $options);

            Mail::send('emails.generic', $mailData, function ($message) use ($mailData) {
                $message->to($mailData['to'])
                       ->from($mailData['from_email'], $mailData['from_name'])
                       ->subject($mailData['subject']);

                // Add attachments if provided
                if (isset($mailData['attachments'])) {
                    foreach ($mailData['attachments'] as $attachment) {
                        $message->attach($attachment);
                    }
                }

                // Add CC if provided
                if (isset($mailData['cc'])) {
                    $message->cc($mailData['cc']);
                }

                // Add BCC if provided
                if (isset($mailData['bcc'])) {
                    $message->bcc($mailData['bcc']);
                }
            });

            Log::info('Email sent successfully', [
                'provider' => $this->provider->provider_name,
                'to' => $to,
                'subject' => $subject
            ]);

            return ['success' => true, 'message' => 'Email sent successfully'];

        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'provider' => $this->provider->provider_name,
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send bulk emails
     */
    public function sendBulkEmails($recipients, $subject, $message, $options = [])
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $recipient) {
            try {
                $this->sendEmail($recipient, $subject, $message, $options);
                $results[] = ['email' => $recipient, 'status' => 'success'];
                $successCount++;
            } catch (\Exception $e) {
                $results[] = [
                    'email' => $recipient, 
                    'status' => 'failed', 
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        return [
            'success' => true,
            'total' => count($recipients),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Send via SMTP
     */
    protected function sendViaSmtp($to, $subject, $message, $options = [])
    {
        // This is handled by Laravel's built-in SMTP
        return $this->sendEmail($to, $subject, $message, $options);
    }

    /**
     * Send via Postmark
     */
    protected function sendViaPostmark($to, $subject, $message, $options = [])
    {
        // This is handled by Laravel's Postmark driver
        return $this->sendEmail($to, $subject, $message, $options);
    }

    /**
     * Send via Resend
     */
    protected function sendViaResend($to, $subject, $message, $options = [])
    {
        // This is handled by Laravel's Resend driver
        return $this->sendEmail($to, $subject, $message, $options);
    }

    /**
     * Send via AWS SES
     */
    protected function sendViaSes($to, $subject, $message, $options = [])
    {
        // This is handled by Laravel's SES driver
        return $this->sendEmail($to, $subject, $message, $options);
    }

    /**
     * Get available email providers
     */
    public static function getAvailableProviders()
    {
        return NotificationSetting::getActiveProviders('email');
    }

    /**
     * Test email provider configuration
     */
    public function testConfiguration()
    {
        try {
            $testEmail = $this->configuration['test_email'] ?? null;
            if (!$testEmail) {
                throw new \Exception('Test email not configured');
            }

            $result = $this->sendEmail(
                $testEmail, 
                'Test Email from ' . config('app.name'),
                'This is a test email to verify the email configuration.'
            );
            
            return ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
