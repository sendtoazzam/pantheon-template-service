<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Booking;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Webhooks",
 *     description="API Endpoints for Webhook Handling"
 * )
 */
class WebhookController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/booking/callback",
     *     summary="Handle booking callback from external merchant",
     *     tags={"Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","status","booking_id"},
     *             @OA\Property(property="token", type="string", example="booking_abc123_1234567890"),
     *             @OA\Property(property="status", type="string", enum={"success", "failed", "cancelled"}, example="success"),
     *             @OA\Property(property="booking_id", type="string", example="EXT_BOOKING_123"),
     *             @OA\Property(property="external_booking_id", type="string", example="MERCHANT_BOOKING_456"),
     *             @OA\Property(property="amount", type="number", format="float", example=1500.00),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="payment_reference", type="string", example="PAY_REF_789"),
     *             @OA\Property(property="booking_details", type="object", example={"package_name": "Umrah Package", "travel_date": "2025-12-01"}),
     *             @OA\Property(property="customer_info", type="object", example={"name": "John Doe", "email": "john@example.com", "phone": "+1234567890"}),
     *             @OA\Property(property="special_requests", type="string", example="Wheelchair accessible"),
     *             @OA\Property(property="notes", type="string", example="Customer requested early check-in")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook processed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="booking_id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="confirmed"),
     *                 @OA\Property(property="external_booking_id", type="string", example="EXT_BOOKING_123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid webhook data",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking session not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function handleBookingCallback(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required|string|max:100',
                'status' => 'required|in:success,failed,cancelled',
                'booking_id' => 'required|string|max:100',
                'external_booking_id' => 'nullable|string|max:100',
                'amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'payment_reference' => 'nullable|string|max:100',
                'booking_details' => 'nullable|array',
                'customer_info' => 'nullable|array',
                'special_requests' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Retrieve booking session from cache
            $sessionKey = "booking_session_{$request->token}";
            $sessionData = Cache::get($sessionKey);

            if (!$sessionData) {
                return $this->notFound('Booking session not found or expired');
            }

            $merchant = Merchant::find($sessionData['merchant_id']);
            $user = User::find($sessionData['user_id']);

            if (!$merchant || !$user) {
                return $this->error('Invalid booking session data', null, 400);
            }

            DB::beginTransaction();

            try {
                // Create or update booking
                $booking = Booking::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'merchant_id' => $merchant->id,
                        'external_booking_id' => $request->external_booking_id ?? $request->booking_id,
                    ],
                    [
                        'service_name' => $request->booking_details['package_name'] ?? 'External Booking',
                        'service_description' => $request->booking_details['description'] ?? 'Booking from external merchant',
                        'booking_date' => $request->booking_details['travel_date'] ?? now()->addDays(30)->format('Y-m-d'),
                        'booking_time' => $request->booking_details['travel_time'] ?? '09:00:00',
                        'duration_minutes' => $request->booking_details['duration'] ?? 480, // 8 hours default
                        'price' => $request->amount ?? 0,
                        'currency' => $request->currency ?? 'USD',
                        'status' => $this->mapExternalStatus($request->status),
                        'notes' => $request->notes,
                        'special_requests' => $request->special_requests,
                        'external_booking_id' => $request->external_booking_id ?? $request->booking_id,
                        'payment_reference' => $request->payment_reference,
                        'external_data' => [
                            'booking_details' => $request->booking_details,
                            'customer_info' => $request->customer_info,
                            'webhook_data' => $request->all(),
                        ],
                    ]
                );

                // Create notification for user
                $this->createBookingNotification($user, $booking, $request->status);

                // Create notification for merchant
                $this->createMerchantNotification($merchant, $booking, $request->status);

                // Clear the session cache
                Cache::forget($sessionKey);

                DB::commit();

                Log::info('Booking webhook processed successfully', [
                    'booking_id' => $booking->id,
                    'external_booking_id' => $request->external_booking_id,
                    'status' => $request->status,
                    'merchant_id' => $merchant->id,
                    'user_id' => $user->id,
                ]);

                return $this->success([
                    'booking_id' => $booking->id,
                    'status' => $booking->status,
                    'external_booking_id' => $booking->external_booking_id,
                ], 'Webhook processed successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Booking webhook error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to process webhook', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/payment/callback",
     *     summary="Handle payment callback from external payment gateway",
     *     tags={"Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"booking_id","payment_status","transaction_id"},
     *             @OA\Property(property="booking_id", type="string", example="EXT_BOOKING_123"),
     *             @OA\Property(property="payment_status", type="string", enum={"completed", "failed", "pending", "refunded"}, example="completed"),
     *             @OA\Property(property="transaction_id", type="string", example="TXN_789"),
     *             @OA\Property(property="amount", type="number", format="float", example=1500.00),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="payment_method", type="string", example="credit_card"),
     *             @OA\Property(property="gateway_response", type="object", example={"gateway": "stripe", "charge_id": "ch_123"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment webhook processed successfully")
     *         )
     *     )
     * )
     */
    public function handlePaymentCallback(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'booking_id' => 'required|string|max:100',
                'payment_status' => 'required|in:completed,failed,pending,refunded',
                'transaction_id' => 'required|string|max:100',
                'amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'payment_method' => 'nullable|string|max:50',
                'gateway_response' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Find booking by external booking ID
            $booking = Booking::where('external_booking_id', $request->booking_id)->first();

            if (!$booking) {
                return $this->notFound('Booking not found');
            }

            DB::beginTransaction();

            try {
                // Update booking with payment information
                $booking->update([
                    'payment_status' => $request->payment_status,
                    'payment_reference' => $request->transaction_id,
                    'payment_method' => $request->payment_method,
                    'payment_data' => $request->gateway_response,
                ]);

                // Create payment notification
                $this->createPaymentNotification($booking, $request->payment_status);

                DB::commit();

                Log::info('Payment webhook processed successfully', [
                    'booking_id' => $booking->id,
                    'external_booking_id' => $request->booking_id,
                    'payment_status' => $request->payment_status,
                    'transaction_id' => $request->transaction_id,
                ]);

                return $this->success([], 'Payment webhook processed successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Payment webhook error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return $this->error('Failed to process payment webhook', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/webhooks/status",
     *     summary="Get webhook status and statistics",
     *     tags={"Webhooks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Merchant ID to get webhook status for",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook status retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="webhook_url", type="string", example="https://api.example.com/webhooks/booking/callback"),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="last_received", type="string", format="date-time", example="2025-09-26T10:30:00Z"),
     *                 @OA\Property(property="total_received", type="integer", example=150),
     *                 @OA\Property(property="successful_webhooks", type="integer", example=145),
     *                 @OA\Property(property="failed_webhooks", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function getWebhookStatus(Request $request)
    {
        try {
            $merchantId = $request->get('merchant_id');
            
            if ($merchantId) {
                $merchant = Merchant::find($merchantId);
                if (!$merchant) {
                    return $this->notFound('Merchant not found');
                }

                // Check if user has access to this merchant
                $user = Auth::user();
                if (!$user->hasRole(['admin', 'superadmin']) && $merchant->user_id !== $user->id) {
                    return $this->forbidden('Access denied');
                }
            }

            // Get webhook statistics from logs or database
            $webhookStats = $this->getWebhookStatistics($merchantId);

            return $this->success($webhookStats, 'Webhook status retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Webhook status error: ' . $e->getMessage());
            return $this->error('Failed to retrieve webhook status', $e->getMessage(), 500);
        }
    }

    /**
     * Map external status to internal booking status
     */
    private function mapExternalStatus(string $externalStatus): string
    {
        return match($externalStatus) {
            'success' => 'confirmed',
            'failed' => 'cancelled',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Create booking notification for user
     */
    private function createBookingNotification(User $user, Booking $booking, string $status): void
    {
        $title = match($status) {
            'success' => 'Booking Confirmed!',
            'failed' => 'Booking Failed',
            'cancelled' => 'Booking Cancelled',
            default => 'Booking Update',
        };

        $message = match($status) {
            'success' => "Your booking for {$booking->service_name} has been confirmed successfully.",
            'failed' => "Unfortunately, your booking for {$booking->service_name} could not be processed.",
            'cancelled' => "Your booking for {$booking->service_name} has been cancelled.",
            default => "There's an update regarding your booking for {$booking->service_name}.",
        };

        Notification::create([
            'user_id' => $user->id,
            'type' => $status === 'success' ? 'success' : 'warning',
            'category' => 'booking',
            'title' => $title,
            'message' => $message,
            'data' => [
                'booking_id' => $booking->id,
                'external_booking_id' => $booking->external_booking_id,
                'status' => $booking->status,
            ],
            'priority' => $status === 'success' ? 'high' : 'normal',
            'is_urgent' => $status === 'failed',
            'is_actionable' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * Create booking notification for merchant
     */
    private function createMerchantNotification(Merchant $merchant, Booking $booking, string $status): void
    {
        $merchantUser = $merchant->user;
        
        $title = match($status) {
            'success' => 'New Booking Received!',
            'failed' => 'Booking Failed',
            'cancelled' => 'Booking Cancelled',
            default => 'Booking Update',
        };

        $message = match($status) {
            'success' => "You have received a new booking for {$booking->service_name}.",
            'failed' => "A booking for {$booking->service_name} failed to process.",
            'cancelled' => "A booking for {$booking->service_name} has been cancelled.",
            default => "There's an update regarding a booking for {$booking->service_name}.",
        };

        Notification::create([
            'user_id' => $merchantUser->id,
            'type' => $status === 'success' ? 'success' : 'warning',
            'category' => 'booking',
            'title' => $title,
            'message' => $message,
            'data' => [
                'booking_id' => $booking->id,
                'external_booking_id' => $booking->external_booking_id,
                'status' => $booking->status,
                'customer_name' => $booking->user->name,
            ],
            'priority' => $status === 'success' ? 'high' : 'normal',
            'is_urgent' => $status === 'success',
            'is_actionable' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * Create payment notification
     */
    private function createPaymentNotification(Booking $booking, string $paymentStatus): void
    {
        $title = match($paymentStatus) {
            'completed' => 'Payment Successful!',
            'failed' => 'Payment Failed',
            'refunded' => 'Payment Refunded',
            default => 'Payment Update',
        };

        $message = match($paymentStatus) {
            'completed' => "Payment for your booking has been processed successfully.",
            'failed' => "Payment for your booking could not be processed.",
            'refunded' => "Your payment has been refunded.",
            default => "There's an update regarding your payment.",
        };

        Notification::create([
            'user_id' => $booking->user_id,
            'type' => $paymentStatus === 'completed' ? 'success' : 'warning',
            'category' => 'payment',
            'title' => $title,
            'message' => $message,
            'data' => [
                'booking_id' => $booking->id,
                'payment_status' => $paymentStatus,
                'amount' => $booking->price,
                'currency' => $booking->currency,
            ],
            'priority' => $paymentStatus === 'completed' ? 'high' : 'normal',
            'is_urgent' => $paymentStatus === 'failed',
            'is_actionable' => true,
            'status' => 'pending',
        ]);
    }

    /**
     * Get webhook statistics
     */
    private function getWebhookStatistics(?int $merchantId = null): array
    {
        // This would typically query a webhook_logs table or similar
        // For now, return mock data
        return [
            'webhook_url' => url('/api/v1/webhooks/booking/callback'),
            'status' => 'active',
            'last_received' => now()->subMinutes(5)->toISOString(),
            'total_received' => 150,
            'successful_webhooks' => 145,
            'failed_webhooks' => 5,
            'success_rate' => 96.67,
            'merchant_id' => $merchantId,
        ];
    }
}
