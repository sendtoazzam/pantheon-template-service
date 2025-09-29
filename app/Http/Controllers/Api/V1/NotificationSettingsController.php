<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\NotificationSetting;
use App\Services\SmsService;
use App\Services\EnhancedEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Notification Settings",
 *     description="API Endpoints for Managing Notification Provider Settings"
 * )
 */
class NotificationSettingsController extends BaseApiController
{
    /**
     * Get all notification settings
     */
    public function index(Request $request)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $query = NotificationSetting::query();

            if ($request->has('provider_type')) {
                $query->where('provider_type', $request->provider_type);
            }

            $settings = $query->orderBy('provider_type')
                             ->orderBy('priority', 'desc')
                             ->get();

            return $this->success($settings, 'Notification settings retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve notification settings', $e->getMessage(), 500);
        }
    }

    /**
     * Create notification setting
     */
    public function store(Request $request)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $validator = Validator::make($request->all(), [
                'provider_type' => 'required|in:email,sms,push',
                'provider_name' => 'required|string|max:255',
                'display_name' => 'required|string|max:255',
                'configuration' => 'required|array',
                'capabilities' => 'nullable|array',
                'description' => 'nullable|string',
                'priority' => 'nullable|integer|min:0',
                'limits' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $existing = NotificationSetting::where('provider_type', $request->provider_type)
                                          ->where('provider_name', $request->provider_name)
                                          ->first();

            if ($existing) {
                return $this->error('Provider already exists for this type', null, 409);
            }

            $setting = NotificationSetting::create([
                'provider_type' => $request->provider_type,
                'provider_name' => $request->provider_name,
                'display_name' => $request->display_name,
                'configuration' => $request->configuration,
                'capabilities' => $request->capabilities ?? [],
                'description' => $request->description,
                'priority' => $request->priority ?? 0,
                'limits' => $request->limits ?? [],
                'is_active' => false,
                'is_default' => false,
            ]);

            return $this->created($setting, 'Notification setting created successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to create notification setting', $e->getMessage(), 500);
        }
    }

    /**
     * Update notification setting
     */
    public function update(Request $request, $id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = NotificationSetting::find($id);
            if (!$setting) {
                return $this->notFound('Notification setting not found');
            }

            $validator = Validator::make($request->all(), [
                'display_name' => 'sometimes|string|max:255',
                'configuration' => 'sometimes|array',
                'capabilities' => 'sometimes|array',
                'description' => 'sometimes|string',
                'priority' => 'sometimes|integer|min:0',
                'limits' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $setting->update($request->only([
                'display_name', 'configuration', 'capabilities', 
                'description', 'priority', 'limits'
            ]));

            return $this->success($setting, 'Notification setting updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update notification setting', $e->getMessage(), 500);
        }
    }

    /**
     * Activate notification setting
     */
    public function activate($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = NotificationSetting::find($id);
            if (!$setting) {
                return $this->notFound('Notification setting not found');
            }

            $setting->update(['is_active' => true]);

            return $this->success($setting, 'Notification setting activated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to activate notification setting', $e->getMessage(), 500);
        }
    }

    /**
     * Deactivate notification setting
     */
    public function deactivate($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = NotificationSetting::find($id);
            if (!$setting) {
                return $this->notFound('Notification setting not found');
            }

            $setting->update(['is_active' => false, 'is_default' => false]);

            return $this->success($setting, 'Notification setting deactivated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to deactivate notification setting', $e->getMessage(), 500);
        }
    }

    /**
     * Set as default notification setting
     */
    public function setDefault($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = NotificationSetting::find($id);
            if (!$setting) {
                return $this->notFound('Notification setting not found');
            }

            if (!$setting->is_active) {
                return $this->error('Cannot set inactive provider as default', null, 400);
            }

            $setting->setAsDefault();

            return $this->success($setting, 'Notification setting set as default successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to set default notification setting', $e->getMessage(), 500);
        }
    }

    /**
     * Test notification setting
     */
    public function test($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = NotificationSetting::find($id);
            if (!$setting) {
                return $this->notFound('Notification setting not found');
            }

            $testResult = null;

            if ($setting->provider_type === 'sms') {
                $smsService = new SmsService($setting->provider_name);
                $testResult = $smsService->testConfiguration();
            } elseif ($setting->provider_type === 'email') {
                $emailService = new EnhancedEmailService($setting->provider_name);
                $testResult = $emailService->testConfiguration();
            } else {
                return $this->error('Test not supported for this provider type', null, 400);
            }

            return $this->success(['test_result' => $testResult], 'Test completed successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to test notification setting', $e->getMessage(), 500);
        }
    }

    /**
     * Delete notification setting
     */
    public function destroy($id)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $setting = NotificationSetting::find($id);
            if (!$setting) {
                return $this->notFound('Notification setting not found');
            }

            if ($setting->is_default) {
                return $this->error('Cannot delete default provider', null, 400);
            }

            $setting->delete();

            return $this->success([], 'Notification setting deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete notification setting', $e->getMessage(), 500);
        }
    }
}