<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use App\Models\Notification;
use App\Services\SmsService;
use App\Services\EnhancedEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Bulk Notifications",
 *     description="API Endpoints for Sending Bulk Notifications"
 * )
 */
class BulkNotificationController extends BaseApiController
{
    /**
     * Send bulk notifications
     */
    public function sendBulkNotifications(Request $request)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'type' => 'required|in:info,success,warning,error',
                'category' => 'required|in:system,booking,payment,security,marketing',
                'channels' => 'required|array',
                'channels.*' => 'in:in_app,email,sms',
                'target_users' => 'required|array',
                'target_users.type' => 'required|in:all,role,ids',
                'target_users.role' => 'required_if:target_users.type,role|string',
                'target_users.ids' => 'required_if:target_users.type,ids|array',
                'target_users.ids.*' => 'integer|exists:users,id',
                'priority' => 'nullable|in:low,normal,high,urgent',
                'is_urgent' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Get target users
            $users = $this->getTargetUsers($request->target_users);
            
            if ($users->isEmpty()) {
                return $this->error('No users found matching the criteria', null, 400);
            }

            $results = [
                'total_users' => $users->count(),
                'channels' => $request->channels,
                'success_count' => 0,
                'failure_count' => 0,
                'results' => []
            ];

            DB::beginTransaction();

            try {
                foreach ($users as $user) {
                    $userResult = $this->sendNotificationToUser($user, $request->all());
                    $results['results'][] = $userResult;
                    
                    if ($userResult['success']) {
                        $results['success_count']++;
                    } else {
                        $results['failure_count']++;
                    }
                }

                DB::commit();

                return $this->success($results, 'Bulk notifications sent successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->error('Failed to send bulk notifications', $e->getMessage(), 500);
        }
    }

    /**
     * Get target users based on criteria
     */
    protected function getTargetUsers($criteria)
    {
        $query = User::query();

        switch ($criteria['type']) {
            case 'all':
                $query->where('status', 'active');
                break;
                
            case 'role':
                $query->whereHas('roles', function ($q) use ($criteria) {
                    $q->where('name', $criteria['role']);
                });
                break;
                
            case 'ids':
                $query->whereIn('id', $criteria['ids']);
                break;
        }

        return $query->get();
    }

    /**
     * Send notification to a single user
     */
    protected function sendNotificationToUser($user, $notificationData)
    {
        $result = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'success' => true,
            'channels' => []
        ];

        try {
            // Create in-app notification
            if (in_array('in_app', $notificationData['channels'])) {
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'sender_id' => Auth::id(),
                    'type' => $notificationData['type'],
                    'category' => $notificationData['category'],
                    'title' => $notificationData['title'],
                    'message' => $notificationData['message'],
                    'priority' => $notificationData['priority'] ?? 'normal',
                    'is_urgent' => $notificationData['is_urgent'] ?? false,
                    'in_app' => true,
                    'email' => in_array('email', $notificationData['channels']),
                    'sms' => in_array('sms', $notificationData['channels']),
                    'status' => 'pending',
                ]);

                $result['channels']['in_app'] = ['success' => true, 'notification_id' => $notification->id];
            }

            // Send email notification
            if (in_array('email', $notificationData['channels'])) {
                try {
                    $emailService = new EnhancedEmailService();
                    $emailResult = $emailService->sendEmail(
                        $user->email,
                        $notificationData['title'],
                        $notificationData['message']
                    );
                    
                    $result['channels']['email'] = ['success' => true, 'result' => $emailResult];
                } catch (\Exception $e) {
                    $result['channels']['email'] = ['success' => false, 'error' => $e->getMessage()];
                }
            }

            // Send SMS notification
            if (in_array('sms', $notificationData['channels'])) {
                try {
                    if ($user->phone) {
                        $smsService = new SmsService();
                        $smsResult = $smsService->sendSms(
                            $user->phone,
                            $notificationData['message']
                        );
                        
                        $result['channels']['sms'] = ['success' => true, 'result' => $smsResult];
                    } else {
                        $result['channels']['sms'] = ['success' => false, 'error' => 'User has no phone number'];
                    }
                } catch (\Exception $e) {
                    $result['channels']['sms'] = ['success' => false, 'error' => $e->getMessage()];
                }
            }

            // Check if any channel failed
            foreach ($result['channels'] as $channel => $channelResult) {
                if (!$channelResult['success']) {
                    $result['success'] = false;
                    break;
                }
            }

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Get bulk notification statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            if (!Auth::user()->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Superadmin role required.');
            }

            $query = Notification::whereNotNull('sender_id');

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $statistics = [
                'total_notifications' => $query->count(),
                'by_type' => $query->selectRaw('type, COUNT(*) as count')
                                 ->groupBy('type')
                                 ->pluck('count', 'type'),
                'by_category' => $query->selectRaw('category, COUNT(*) as count')
                                     ->groupBy('category')
                                     ->pluck('count', 'category'),
                'by_channel' => [
                    'in_app' => $query->where('in_app', true)->count(),
                    'email' => $query->where('email', true)->count(),
                    'sms' => $query->where('sms', true)->count(),
                ],
                'by_status' => $query->selectRaw('status, COUNT(*) as count')
                                   ->groupBy('status')
                                   ->pluck('count', 'status'),
            ];

            return $this->success($statistics, 'Statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }
}