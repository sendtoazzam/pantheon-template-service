<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\PaymentGatewaySetting;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Payment Gateway Settings",
 *     description="API Endpoints for Managing Payment Gateway Settings"
 * )
 */
class PaymentGatewaySettingsController extends BaseApiController
{
    /**
     * Get all payment gateway settings
     */
    public function index(Request $request)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $query = PaymentGatewaySetting::query();

            if ($request->has('gateway_type')) {
                $query->where('gateway_type', $request->gateway_type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $settings = $query->orderBy('priority', 'desc')
                             ->orderBy('gateway_name')
                             ->get();

            return $this->success($settings, 'Payment gateway settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve payment gateway settings', $e->getMessage(), 500);
        }
    }

    /**
     * Create payment gateway setting
     */
    public function store(Request $request)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $validator = Validator::make($request->all(), [
                'gateway_name' => 'required|string|max:255|unique:payment_gateway_settings',
                'display_name' => 'required|string|max:255',
                'gateway_type' => 'required|in:card,wallet,bank_transfer,crypto',
                'configuration' => 'required|array',
                'supported_currencies' => 'required|array',
                'supported_countries' => 'nullable|array',
                'supported_payment_methods' => 'required|array',
                'transaction_fee_percentage' => 'nullable|numeric|min:0|max:100',
                'transaction_fee_fixed' => 'nullable|numeric|min:0',
                'min_amount' => 'nullable|numeric|min:0',
                'max_amount' => 'nullable|numeric|min:0',
                'webhook_configuration' => 'nullable|array',
                'limits' => 'nullable|array',
                'description' => 'nullable|string',
                'priority' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $setting = PaymentGatewaySetting::create([
                'gateway_name' => $request->gateway_name,
                'display_name' => $request->display_name,
                'gateway_type' => $request->gateway_type,
                'configuration' => $request->configuration,
                'supported_currencies' => $request->supported_currencies,
                'supported_countries' => $request->supported_countries ?? [],
                'supported_payment_methods' => $request->supported_payment_methods,
                'transaction_fee_percentage' => $request->transaction_fee_percentage ?? 0,
                'transaction_fee_fixed' => $request->transaction_fee_fixed ?? 0,
                'min_amount' => $request->min_amount ?? 0,
                'max_amount' => $request->max_amount,
                'webhook_configuration' => $request->webhook_configuration ?? [],
                'limits' => $request->limits ?? [],
                'description' => $request->description,
                'priority' => $request->priority ?? 0,
                'is_active' => false,
                'is_default' => false,
            ]);

            return $this->created($setting, 'Payment gateway setting created successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to create payment gateway setting', $e->getMessage(), 500);
        }
    }

    /**
     * Update payment gateway setting
     */
    public function update(Request $request, $id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = PaymentGatewaySetting::find($id);
            if (!$setting) {
                return $this->notFound('Payment gateway setting not found');
            }

            $validator = Validator::make($request->all(), [
                'display_name' => 'sometimes|string|max:255',
                'gateway_type' => 'sometimes|in:card,wallet,bank_transfer,crypto',
                'configuration' => 'sometimes|array',
                'supported_currencies' => 'sometimes|array',
                'supported_countries' => 'sometimes|array',
                'supported_payment_methods' => 'sometimes|array',
                'transaction_fee_percentage' => 'sometimes|numeric|min:0|max:100',
                'transaction_fee_fixed' => 'sometimes|numeric|min:0',
                'min_amount' => 'sometimes|numeric|min:0',
                'max_amount' => 'sometimes|numeric|min:0',
                'webhook_configuration' => 'sometimes|array',
                'limits' => 'sometimes|array',
                'description' => 'sometimes|string',
                'priority' => 'sometimes|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $setting->update($request->only([
                'display_name', 'gateway_type', 'configuration', 'supported_currencies',
                'supported_countries', 'supported_payment_methods', 'transaction_fee_percentage',
                'transaction_fee_fixed', 'min_amount', 'max_amount', 'webhook_configuration',
                'limits', 'description', 'priority'
            ]));

            return $this->success($setting, 'Payment gateway setting updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update payment gateway setting', $e->getMessage(), 500);
        }
    }

    /**
     * Activate payment gateway setting
     */
    public function activate($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = PaymentGatewaySetting::find($id);
            if (!$setting) {
                return $this->notFound('Payment gateway setting not found');
            }

            $setting->update(['is_active' => true]);

            return $this->success($setting, 'Payment gateway setting activated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to activate payment gateway setting', $e->getMessage(), 500);
        }
    }

    /**
     * Deactivate payment gateway setting
     */
    public function deactivate($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = PaymentGatewaySetting::find($id);
            if (!$setting) {
                return $this->notFound('Payment gateway setting not found');
            }

            $setting->update(['is_active' => false, 'is_default' => false]);

            return $this->success($setting, 'Payment gateway setting deactivated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to deactivate payment gateway setting', $e->getMessage(), 500);
        }
    }

    /**
     * Set as default payment gateway setting
     */
    public function setDefault($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = PaymentGatewaySetting::find($id);
            if (!$setting) {
                return $this->notFound('Payment gateway setting not found');
            }

            if (!$setting->is_active) {
                return $this->error('Cannot set inactive gateway as default', null, 400);
            }

            $setting->setAsDefault();

            return $this->success($setting, 'Payment gateway setting set as default successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to set default payment gateway setting', $e->getMessage(), 500);
        }
    }

    /**
     * Test payment gateway setting
     */
    public function test($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = PaymentGatewaySetting::find($id);
            if (!$setting) {
                return $this->notFound('Payment gateway setting not found');
            }

            $paymentService = new PaymentService($setting->gateway_name);
            $testResult = $paymentService->testConfiguration();

            return $this->success(['test_result' => $testResult], 'Test completed successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to test payment gateway setting', $e->getMessage(), 500);
        }
    }

    /**
     * Calculate transaction fee
     */
    public function calculateFee(Request $request, $id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $setting = PaymentGatewaySetting::find($id);
            if (!$setting) {
                return $this->notFound('Payment gateway setting not found');
            }

            $amount = $request->amount;
            $fee = $setting->calculateFee($amount);

            return $this->success([
                'amount' => $amount,
                'fee' => $fee,
                'total' => $amount + $fee,
                'fee_percentage' => $setting->transaction_fee_percentage,
                'fee_fixed' => $setting->transaction_fee_fixed,
            ], 'Fee calculated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to calculate fee', $e->getMessage(), 500);
        }
    }

    /**
     * Delete payment gateway setting
     */
    public function destroy($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = PaymentGatewaySetting::find($id);
            if (!$setting) {
                return $this->notFound('Payment gateway setting not found');
            }

            if ($setting->is_default) {
                return $this->error('Cannot delete default gateway', null, 400);
            }

            $setting->delete();

            return $this->success([], 'Payment gateway setting deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete payment gateway setting', $e->getMessage(), 500);
        }
    }
}