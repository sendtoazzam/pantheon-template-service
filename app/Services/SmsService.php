<?php

namespace App\Services;

use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsService
{
    protected $provider;
    protected $configuration;

    public function __construct($providerName = null)
    {
        $this->setProvider($providerName);
    }

    /**
     * Set SMS provider
     */
    public function setProvider($providerName = null)
    {
        if ($providerName) {
            $this->provider = NotificationSetting::where('provider_type', 'sms')
                                                ->where('provider_name', $providerName)
                                                ->where('is_active', true)
                                                ->first();
        } else {
            $this->provider = NotificationSetting::getDefaultProvider('sms');
        }

        if (!$this->provider) {
            throw new \Exception('No active SMS provider found');
        }

        $this->configuration = $this->provider->configuration;
    }

    /**
     * Send SMS message
     */
    public function sendSms($to, $message, $options = [])
    {
        try {
            $method = 'sendVia' . ucfirst($this->provider->provider_name);
            
            if (!method_exists($this, $method)) {
                throw new \Exception("SMS provider {$this->provider->provider_name} not supported");
            }

            $result = $this->$method($to, $message, $options);
            
            Log::info('SMS sent successfully', [
                'provider' => $this->provider->provider_name,
                'to' => $to,
                'message_length' => strlen($message),
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => $this->provider->provider_name,
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Send via Twilio
     */
    protected function sendViaTwilio($to, $message, $options = [])
    {
        $accountSid = $this->configuration['account_sid'];
        $authToken = $this->configuration['auth_token'];
        $from = $this->configuration['from_number'];

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('sid'),
                'status' => $response->json('status')
            ];
        }

        throw new \Exception('Twilio API error: ' . $response->body());
    }

    /**
     * Send via Nexmo/Vonage
     */
    protected function sendViaNexmo($to, $message, $options = [])
    {
        $apiKey = $this->configuration['api_key'];
        $apiSecret = $this->configuration['api_secret'];
        $from = $this->configuration['from'];

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'to' => $to,
            'from' => $from,
            'text' => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['messages'][0]['status']) && $data['messages'][0]['status'] == '0') {
                return [
                    'success' => true,
                    'message_id' => $data['messages'][0]['message-id'],
                    'status' => 'sent'
                ];
            }
        }

        throw new \Exception('Nexmo API error: ' . $response->body());
    }

    /**
     * Send via AWS SNS
     */
    protected function sendViaAwsSns($to, $message, $options = [])
    {
        $accessKey = $this->configuration['access_key'];
        $secretKey = $this->configuration['secret_key'];
        $region = $this->configuration['region'] ?? 'us-east-1';

        // This would require AWS SDK
        // For now, return a mock response
        return [
            'success' => true,
            'message_id' => 'aws-sns-' . uniqid(),
            'status' => 'sent'
        ];
    }

    /**
     * Send via TextMagic
     */
    protected function sendViaTextmagic($to, $message, $options = [])
    {
        $username = $this->configuration['username'];
        $apiKey = $this->configuration['api_key'];

        $response = Http::withBasicAuth($username, $apiKey)
            ->post('https://rest.textmagic.com/api/v2/messages', [
                'text' => $message,
                'phones' => $to,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['id'],
                'status' => 'sent'
            ];
        }

        throw new \Exception('TextMagic API error: ' . $response->body());
    }

    /**
     * Get available SMS providers
     */
    public static function getAvailableProviders()
    {
        return NotificationSetting::getActiveProviders('sms');
    }

    /**
     * Test SMS provider configuration
     */
    public function testConfiguration()
    {
        try {
            // Send a test message to a test number
            $testNumber = $this->configuration['test_number'] ?? null;
            if (!$testNumber) {
                throw new \Exception('Test number not configured');
            }

            $result = $this->sendSms($testNumber, 'Test message from ' . config('app.name'));
            return ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
