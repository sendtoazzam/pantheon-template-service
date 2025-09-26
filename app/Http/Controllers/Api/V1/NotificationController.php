<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="API Endpoints for Notification Management"
 * )
 */
class NotificationController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/notifications",
     *     summary="Get user notifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by notification type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"info", "success", "warning", "error"})
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         required=false,
     *         @OA\Schema(type="string", enum={"system", "booking", "payment", "security", "marketing"})
     *     ),
     *     @OA\Parameter(
     *         name="unread_only",
     *         in="query",
     *         description="Show only unread notifications",
     *         required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of notifications per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notifications retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Notification"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = Notification::where('user_id', $user->id);

            // Apply filters
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->boolean('unread_only')) {
                $query->whereNull('read_at');
            }

            $notifications = $query->orderBy('created_at', 'desc')
                                 ->paginate($request->get('per_page', 15));

            return $this->paginated($notifications, 'Notifications retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve notifications', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notifications/unread-count",
     *     summary="Get unread notification count",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Unread count retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Unread count retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="unread_count", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();
            
            $unreadCount = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            return $this->success(['unread_count' => $unreadCount], 'Unread count retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve unread count', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notifications/{id}",
     *     summary="Get notification by ID",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Notification")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notification not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return $this->notFound('Notification not found');
            }

            return $this->success(new NotificationResource($notification), 'Notification retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve notification', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notifications",
     *     summary="Create new notification",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","type","category","title","message"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", enum={"info", "success", "warning", "error"}, example="info"),
     *             @OA\Property(property="category", type="string", enum={"system", "booking", "payment", "security", "marketing"}, example="booking"),
     *             @OA\Property(property="title", type="string", example="Booking Confirmed"),
     *             @OA\Property(property="message", type="string", example="Your booking for tomorrow has been confirmed"),
     *             @OA\Property(property="data", type="object", example={"booking_id": 123, "action_url": "/bookings/123"}),
     *             @OA\Property(property="priority", type="string", enum={"low", "normal", "high", "urgent"}, example="normal"),
     *             @OA\Property(property="is_urgent", type="boolean", example=false),
     *             @OA\Property(property="is_actionable", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Notification created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Notification")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Only admins and superadmins can create notifications
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->forbidden('Access denied. Admin role required.');
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'type' => 'required|in:info,success,warning,error',
                'category' => 'required|in:system,booking,payment,security,marketing',
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'data' => 'nullable|array',
                'priority' => 'required|in:low,normal,high,urgent',
                'is_urgent' => 'boolean',
                'is_actionable' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $notification = Notification::create([
                'user_id' => $request->user_id,
                'type' => $request->type,
                'category' => $request->category,
                'title' => $request->title,
                'message' => $request->message,
                'data' => $request->data ?? [],
                'priority' => $request->priority,
                'is_urgent' => $request->boolean('is_urgent'),
                'is_actionable' => $request->boolean('is_actionable'),
                'status' => 'pending',
            ]);

            return $this->created(new NotificationResource($notification), 'Notification created successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to create notification', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/notifications/{id}/read",
     *     summary="Mark notification as read",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification marked as read"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Notification")
     *         )
     *     )
     * )
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return $this->notFound('Notification not found');
            }

            if (!$notification->read_at) {
                $notification->update(['read_at' => now()]);
            }

            return $this->success(new NotificationResource($notification), 'Notification marked as read');

        } catch (\Exception $e) {
            return $this->error('Failed to mark notification as read', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/notifications/mark-all-read",
     *     summary="Mark all notifications as read",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications marked as read",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All notifications marked as read"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="updated_count", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            $updatedCount = Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return $this->success(['updated_count' => $updatedCount], 'All notifications marked as read');

        } catch (\Exception $e) {
            return $this->error('Failed to mark all notifications as read', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/notifications/{id}",
     *     summary="Delete notification",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notification ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notification deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return $this->notFound('Notification not found');
            }

            $notification->delete();

            return $this->success([], 'Notification deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete notification', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/notifications/clear-all",
     *     summary="Clear all notifications",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All notifications cleared",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All notifications cleared"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="deleted_count", type="integer", example=10)
     *             )
     *         )
     *     )
     * )
     */
    public function clearAll()
    {
        try {
            $user = Auth::user();
            
            $deletedCount = Notification::where('user_id', $user->id)->delete();

            return $this->success(['deleted_count' => $deletedCount], 'All notifications cleared');

        } catch (\Exception $e) {
            return $this->error('Failed to clear all notifications', $e->getMessage(), 500);
        }
    }
}
