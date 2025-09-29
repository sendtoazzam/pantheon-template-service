<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationSetting;
use App\Models\PaymentGatewaySetting;

class NotificationAndPaymentSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedNotificationSettings();
        $this->seedPaymentGatewaySettings();
    }

    /**
     * Seed notification settings
     */
    private function seedNotificationSettings()
    {
        // Email Providers
        $emailProviders = [
            [
                'provider_type' => 'email',
                'provider_name' => 'smtp',
                'display_name' => 'SMTP Server',
                'configuration' => [
                    'transport' => 'smtp',
                    'host' => env('MAIL_HOST', 'smtp.gmail.com'),
                    'port' => env('MAIL_PORT', 587),
                    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                    'username' => env('MAIL_USERNAME'),
                    'password' => env('MAIL_PASSWORD'),
                    'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                    'from_name' => env('MAIL_FROM_NAME', 'Muslim Finder'),
                ],
                'capabilities' => ['bulk_email', 'attachments', 'html_content'],
                'description' => 'Standard SMTP email service',
                'priority' => 1,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'provider_type' => 'email',
                'provider_name' => 'postmark',
                'display_name' => 'Postmark',
                'configuration' => [
                    'transport' => 'postmark',
                    'token' => env('POSTMARK_TOKEN'),
                    'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                    'from_name' => env('MAIL_FROM_NAME', 'Muslim Finder'),
                ],
                'capabilities' => ['bulk_email', 'attachments', 'html_content', 'tracking'],
                'description' => 'Postmark transactional email service',
                'priority' => 2,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'provider_type' => 'email',
                'provider_name' => 'ses',
                'display_name' => 'Amazon SES',
                'configuration' => [
                    'transport' => 'ses',
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                    'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                    'from_name' => env('MAIL_FROM_NAME', 'Muslim Finder'),
                ],
                'capabilities' => ['bulk_email', 'attachments', 'html_content', 'scalable'],
                'description' => 'Amazon Simple Email Service',
                'priority' => 3,
                'is_active' => false,
                'is_default' => false,
            ],
        ];

        // SMS Providers
        $smsProviders = [
            [
                'provider_type' => 'sms',
                'provider_name' => 'twilio',
                'display_name' => 'Twilio',
                'configuration' => [
                    'account_sid' => env('TWILIO_ACCOUNT_SID'),
                    'auth_token' => env('TWILIO_AUTH_TOKEN'),
                    'from_number' => env('TWILIO_FROM_NUMBER'),
                    'test_number' => env('TWILIO_TEST_NUMBER'),
                ],
                'capabilities' => ['international', 'delivery_reports', 'webhooks'],
                'description' => 'Twilio SMS service with global reach',
                'priority' => 1,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'provider_type' => 'sms',
                'provider_name' => 'nexmo',
                'display_name' => 'Vonage (Nexmo)',
                'configuration' => [
                    'api_key' => env('NEXMO_API_KEY'),
                    'api_secret' => env('NEXMO_API_SECRET'),
                    'from' => env('NEXMO_FROM'),
                    'test_number' => env('NEXMO_TEST_NUMBER'),
                ],
                'capabilities' => ['international', 'delivery_reports', 'webhooks'],
                'description' => 'Vonage SMS service (formerly Nexmo)',
                'priority' => 2,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'provider_type' => 'sms',
                'provider_name' => 'textmagic',
                'display_name' => 'TextMagic',
                'configuration' => [
                    'username' => env('TEXTMAGIC_USERNAME'),
                    'api_key' => env('TEXTMAGIC_API_KEY'),
                    'test_number' => env('TEXTMAGIC_TEST_NUMBER'),
                ],
                'capabilities' => ['international', 'bulk_sms', 'scheduling'],
                'description' => 'TextMagic SMS service with advanced features',
                'priority' => 3,
                'is_active' => false,
                'is_default' => false,
            ],
        ];

        foreach ($emailProviders as $provider) {
            NotificationSetting::updateOrCreate(
                [
                    'provider_type' => $provider['provider_type'],
                    'provider_name' => $provider['provider_name'],
                ],
                $provider
            );
        }

        foreach ($smsProviders as $provider) {
            NotificationSetting::updateOrCreate(
                [
                    'provider_type' => $provider['provider_type'],
                    'provider_name' => $provider['provider_name'],
                ],
                $provider
            );
        }
    }

    /**
     * Seed payment gateway settings
     */
    private function seedPaymentGatewaySettings()
    {
        $gateways = [
            [
                'gateway_name' => 'stripe',
                'display_name' => 'Stripe',
                'gateway_type' => 'card',
                'configuration' => [
                    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
                    'secret_key' => env('STRIPE_SECRET_KEY'),
                    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
                ],
                'supported_currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK'],
                'supported_countries' => ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'SE', 'NO', 'DK'],
                'supported_payment_methods' => ['card', 'apple_pay', 'google_pay', 'sepa_debit'],
                'transaction_fee_percentage' => 2.9,
                'transaction_fee_fixed' => 0.30,
                'min_amount' => 0.50,
                'max_amount' => 999999.99,
                'webhook_configuration' => [
                    'url' => env('APP_URL') . '/api/v1/webhooks/stripe',
                    'events' => ['payment_intent.succeeded', 'payment_intent.payment_failed'],
                ],
                'description' => 'Stripe payment processing with global support',
                'priority' => 1,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'gateway_name' => 'paypal',
                'display_name' => 'PayPal',
                'gateway_type' => 'wallet',
                'configuration' => [
                    'client_id' => env('PAYPAL_CLIENT_ID'),
                    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                    'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
                ],
                'supported_currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF'],
                'supported_countries' => ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'SE', 'NO', 'DK', 'PL', 'CZ', 'HU'],
                'supported_payment_methods' => ['paypal', 'paypal_credit'],
                'transaction_fee_percentage' => 2.9,
                'transaction_fee_fixed' => 0.30,
                'min_amount' => 1.00,
                'max_amount' => 10000.00,
                'webhook_configuration' => [
                    'url' => env('APP_URL') . '/api/v1/webhooks/paypal',
                    'events' => ['PAYMENT.CAPTURE.COMPLETED', 'PAYMENT.CAPTURE.DENIED'],
                ],
                'description' => 'PayPal payment processing with buyer protection',
                'priority' => 2,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'gateway_name' => 'razorpay',
                'display_name' => 'Razorpay',
                'gateway_type' => 'card',
                'configuration' => [
                    'key_id' => env('RAZORPAY_KEY_ID'),
                    'key_secret' => env('RAZORPAY_KEY_SECRET'),
                    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
                ],
                'supported_currencies' => ['INR', 'USD', 'EUR', 'GBP', 'SGD', 'AED', 'AUD'],
                'supported_countries' => ['IN', 'US', 'GB', 'AE', 'SG', 'AU'],
                'supported_payment_methods' => ['card', 'netbanking', 'wallet', 'upi', 'emi'],
                'transaction_fee_percentage' => 2.0,
                'transaction_fee_fixed' => 0.0,
                'min_amount' => 1.00,
                'max_amount' => 100000.00,
                'webhook_configuration' => [
                    'url' => env('APP_URL') . '/api/v1/webhooks/razorpay',
                    'events' => ['payment.captured', 'payment.failed'],
                ],
                'description' => 'Razorpay payment gateway for India and international markets',
                'priority' => 3,
                'is_active' => false,
                'is_default' => false,
            ],
            [
                'gateway_name' => 'square',
                'display_name' => 'Square',
                'gateway_type' => 'card',
                'configuration' => [
                    'application_id' => env('SQUARE_APPLICATION_ID'),
                    'access_token' => env('SQUARE_ACCESS_TOKEN'),
                    'environment' => env('SQUARE_ENVIRONMENT', 'sandbox'),
                ],
                'supported_currencies' => ['USD', 'CAD', 'AUD', 'GBP', 'JPY'],
                'supported_countries' => ['US', 'CA', 'AU', 'GB', 'JP'],
                'supported_payment_methods' => ['card', 'apple_pay', 'google_pay'],
                'transaction_fee_percentage' => 2.9,
                'transaction_fee_fixed' => 0.30,
                'min_amount' => 1.00,
                'max_amount' => 10000.00,
                'webhook_configuration' => [
                    'url' => env('APP_URL') . '/api/v1/webhooks/square',
                    'events' => ['payment.updated'],
                ],
                'description' => 'Square payment processing with point-of-sale integration',
                'priority' => 4,
                'is_active' => false,
                'is_default' => false,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGatewaySetting::updateOrCreate(
                ['gateway_name' => $gateway['gateway_name']],
                $gateway
            );
        }
    }
}