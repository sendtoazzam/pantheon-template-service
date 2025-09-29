<?php

namespace App\Services;

use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    protected $gateway;
    protected $configuration;

    public function __construct($gatewayName = null)
    {
        $this->setGateway($gatewayName);
    }

    /**
     * Set payment gateway
     */
    public function setGateway($gatewayName = null)
    {
        if ($gatewayName) {
            $this->gateway = PaymentGatewaySetting::where('gateway_name', $gatewayName)
                                                 ->where('is_active', true)
                                                 ->first();
        } else {
            $this->gateway = PaymentGatewaySetting::getDefaultGateway();
        }

        if (!$this->gateway) {
            throw new \Exception('No active payment gateway found');
        }

        $this->configuration = $this->gateway->configuration;
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent($amount, $currency, $options = [])
    {
        try {
            $method = 'createPaymentIntentVia' . ucfirst($this->gateway->gateway_name);
            
            if (!method_exists($this, $method)) {
                throw new \Exception("Payment gateway {$this->gateway->gateway_name} not supported");
            }

            // Validate amount
            if ($amount < $this->gateway->min_amount) {
                throw new \Exception("Amount must be at least {$this->gateway->min_amount}");
            }

            if ($this->gateway->max_amount && $amount > $this->gateway->max_amount) {
                throw new \Exception("Amount must not exceed {$this->gateway->max_amount}");
            }

            // Validate currency
            if (!$this->gateway->supportsCurrency($currency)) {
                throw new \Exception("Currency {$currency} not supported by this gateway");
            }

            $result = $this->$method($amount, $currency, $options);
            
            Log::info('Payment intent created successfully', [
                'gateway' => $this->gateway->gateway_name,
                'amount' => $amount,
                'currency' => $currency,
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Payment intent creation failed', [
                'gateway' => $this->gateway->gateway_name,
                'amount' => $amount,
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Process payment
     */
    public function processPayment($paymentIntentId, $paymentMethodId, $options = [])
    {
        try {
            $method = 'processPaymentVia' . ucfirst($this->gateway->gateway_name);
            
            if (!method_exists($this, $method)) {
                throw new \Exception("Payment gateway {$this->gateway->gateway_name} not supported");
            }

            $result = $this->$method($paymentIntentId, $paymentMethodId, $options);
            
            Log::info('Payment processed successfully', [
                'gateway' => $this->gateway->gateway_name,
                'payment_intent_id' => $paymentIntentId,
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'gateway' => $this->gateway->gateway_name,
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create payment intent via Stripe
     */
    protected function createPaymentIntentViaStripe($amount, $currency, $options = [])
    {
        $apiKey = $this->configuration['secret_key'];
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/payment_intents', [
            'amount' => $amount * 100, // Convert to cents
            'currency' => $currency,
            'metadata' => $options['metadata'] ?? [],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'payment_intent_id' => $data['id'],
                'client_secret' => $data['client_secret'],
                'status' => $data['status']
            ];
        }

        throw new \Exception('Stripe API error: ' . $response->body());
    }

    /**
     * Process payment via Stripe
     */
    protected function processPaymentViaStripe($paymentIntentId, $paymentMethodId, $options = [])
    {
        $apiKey = $this->configuration['secret_key'];
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post("https://api.stripe.com/v1/payment_intents/{$paymentIntentId}/confirm", [
            'payment_method' => $paymentMethodId,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'payment_intent_id' => $data['id'],
                'status' => $data['status'],
                'amount' => $data['amount'],
                'currency' => $data['currency']
            ];
        }

        throw new \Exception('Stripe API error: ' . $response->body());
    }

    /**
     * Create payment intent via PayPal
     */
    protected function createPaymentIntentViaPaypal($amount, $currency, $options = [])
    {
        $clientId = $this->configuration['client_id'];
        $clientSecret = $this->configuration['client_secret'];
        $environment = $this->configuration['environment'] ?? 'sandbox';
        
        $baseUrl = $environment === 'production' 
            ? 'https://api.paypal.com' 
            : 'https://api.sandbox.paypal.com';

        // Get access token
        $tokenResponse = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials'
            ]);

        if (!$tokenResponse->successful()) {
            throw new \Exception('PayPal authentication failed');
        }

        $accessToken = $tokenResponse->json('access_token');

        // Create payment
        $paymentResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/v1/payments", [
            'intent' => 'sale',
            'payer' => [
                'payment_method' => 'paypal'
            ],
            'transactions' => [[
                'amount' => [
                    'total' => number_format($amount, 2),
                    'currency' => $currency
                ]
            ]],
            'redirect_urls' => [
                'return_url' => $options['return_url'] ?? config('app.url') . '/payment/success',
                'cancel_url' => $options['cancel_url'] ?? config('app.url') . '/payment/cancel'
            ]
        ]);

        if ($paymentResponse->successful()) {
            $data = $paymentResponse->json();
            return [
                'success' => true,
                'payment_intent_id' => $data['id'],
                'approval_url' => $data['links'][1]['href'] ?? null,
                'status' => 'created'
            ];
        }

        throw new \Exception('PayPal API error: ' . $paymentResponse->body());
    }

    /**
     * Process payment via PayPal
     */
    protected function processPaymentViaPaypal($paymentIntentId, $paymentMethodId, $options = [])
    {
        // PayPal payments are processed through redirect flow
        // This would typically be handled in the frontend
        return [
            'success' => true,
            'payment_intent_id' => $paymentIntentId,
            'status' => 'requires_action',
            'action_url' => $options['approval_url']
        ];
    }

    /**
     * Create payment intent via Razorpay
     */
    protected function createPaymentIntentViaRazorpay($amount, $currency, $options = [])
    {
        $keyId = $this->configuration['key_id'];
        $keySecret = $this->configuration['key_secret'];
        
        $response = Http::withBasicAuth($keyId, $keySecret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amount * 100, // Convert to paise
                'currency' => $currency,
                'receipt' => $options['receipt'] ?? 'order_' . uniqid(),
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'payment_intent_id' => $data['id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => 'created'
            ];
        }

        throw new \Exception('Razorpay API error: ' . $response->body());
    }

    /**
     * Process payment via Razorpay
     */
    protected function processPaymentViaRazorpay($paymentIntentId, $paymentMethodId, $options = [])
    {
        // Razorpay payments are processed through frontend integration
        return [
            'success' => true,
            'payment_intent_id' => $paymentIntentId,
            'status' => 'requires_action'
        ];
    }

    /**
     * Get available payment gateways
     */
    public static function getAvailableGateways()
    {
        return PaymentGatewaySetting::getActiveGateways();
    }

    /**
     * Calculate transaction fee
     */
    public function calculateFee($amount)
    {
        return $this->gateway->calculateFee($amount);
    }

    /**
     * Test gateway configuration
     */
    public function testConfiguration()
    {
        try {
            // This would typically involve a small test transaction
            // For now, we'll just validate the configuration
            $requiredFields = $this->getRequiredFields();
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (!isset($this->configuration[$field]) || empty($this->configuration[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new \Exception('Missing required fields: ' . implode(', ', $missingFields));
            }

            return ['success' => true, 'message' => 'Configuration is valid'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get required fields for current gateway
     */
    protected function getRequiredFields()
    {
        switch ($this->gateway->gateway_name) {
            case 'stripe':
                return ['secret_key', 'publishable_key'];
            case 'paypal':
                return ['client_id', 'client_secret'];
            case 'razorpay':
                return ['key_id', 'key_secret'];
            default:
                return [];
        }
    }
}
