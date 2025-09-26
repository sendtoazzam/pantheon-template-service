<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Bookings",
 *     description="API Endpoints for Booking Management"
 * )
 */
class BookingController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/bookings",
     *     summary="Get all bookings",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "confirmed", "cancelled", "completed", "no_show"})
     *     ),
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Filter by merchant ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter bookings from date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter bookings to date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of bookings per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bookings retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Booking::with(['user', 'merchant']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            if ($request->has('date_from')) {
                $query->whereDate('booking_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('booking_date', '<=', $request->date_to);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('service_name', 'like', "%{$search}%")
                      ->orWhere('service_description', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            // Check permissions based on user role
            $user = Auth::user();
            if ($user->hasRole(['admin', 'superadmin'])) {
                // Admins can see all bookings
            } elseif ($user->hasRole('vendor')) {
                // Vendors can only see their own merchant's bookings
                $merchant = Merchant::where('user_id', $user->id)->first();
                if ($merchant) {
                    $query->where('merchant_id', $merchant->id);
                } else {
                    return $this->error('Merchant profile not found', null, 404);
                }
            } else {
                // Regular users can only see their own bookings
                $query->where('user_id', $user->id);
            }

            $bookings = $query->orderBy('booking_date', 'desc')
                            ->orderBy('booking_time', 'desc')
                            ->paginate($request->get('per_page', 15));

            return $this->paginated($bookings, 'Bookings retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve bookings', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bookings/my",
     *     summary="Get current user's bookings",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User bookings retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking"))
     *         )
     *     )
     * )
     */
    public function myBookings(Request $request)
    {
        try {
            $user = Auth::user();
            
            $bookings = Booking::with(['merchant'])
                ->where('user_id', $user->id)
                ->orderBy('booking_date', 'desc')
                ->orderBy('booking_time', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->paginated($bookings, 'User bookings retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user bookings', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bookings/merchant",
     *     summary="Get merchant's bookings",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Merchant bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant bookings retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking"))
     *         )
     *     )
     * )
     */
    public function merchantBookings(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user->hasRole('vendor')) {
                return $this->forbidden('Access denied. Vendor role required.');
            }

            $merchant = Merchant::where('user_id', $user->id)->first();
            if (!$merchant) {
                return $this->error('Merchant profile not found', null, 404);
            }

            $bookings = Booking::with(['user'])
                ->where('merchant_id', $merchant->id)
                ->orderBy('booking_date', 'desc')
                ->orderBy('booking_time', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->paginated($bookings, 'Merchant bookings retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant bookings', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bookings/{id}",
     *     summary="Get booking by ID",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $booking = Booking::with(['user', 'merchant'])->find($id);

            if (!$booking) {
                return $this->notFound('Booking not found');
            }

            // Check permissions
            $user = Auth::user();
            $canView = false;

            if ($user->hasRole(['admin', 'superadmin'])) {
                $canView = true;
            } elseif ($user->hasRole('vendor')) {
                $merchant = Merchant::where('user_id', $user->id)->first();
                $canView = $merchant && $booking->merchant_id === $merchant->id;
            } else {
                $canView = $booking->user_id === $user->id;
            }

            if (!$canView) {
                return $this->forbidden('Access denied');
            }

            return $this->success(new BookingResource($booking), 'Booking retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve booking', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/bookings",
     *     summary="Create new booking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"merchant_id","service_name","booking_date","booking_time","duration_minutes","price"},
     *             @OA\Property(property="merchant_id", type="integer", example=1),
     *             @OA\Property(property="service_name", type="string", example="Haircut"),
     *             @OA\Property(property="service_description", type="string", example="Professional haircut and styling"),
     *             @OA\Property(property="booking_date", type="string", format="date", example="2025-09-26"),
     *             @OA\Property(property="booking_time", type="string", format="time", example="14:30:00"),
     *             @OA\Property(property="duration_minutes", type="integer", example=60),
     *             @OA\Property(property="price", type="number", format="float", example=50.00),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="notes", type="string", example="Please arrive 10 minutes early"),
     *             @OA\Property(property="special_requests", type="string", example="Wheelchair accessible")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Booking")
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
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'merchant_id' => 'required|exists:merchants,id',
                'service_name' => 'required|string|max:255',
                'service_description' => 'nullable|string|max:1000',
                'booking_date' => 'required|date|after_or_equal:today',
                'booking_time' => 'required|date_format:H:i:s',
                'duration_minutes' => 'required|integer|min:15|max:480', // 15 minutes to 8 hours
                'price' => 'required|numeric|min:0',
                'currency' => 'required|string|size:3',
                'notes' => 'nullable|string|max:1000',
                'special_requests' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Check if merchant exists and is active
            $merchant = Merchant::where('id', $request->merchant_id)
                ->where('status', 'active')
                ->first();

            if (!$merchant) {
                return $this->error('Merchant not found or inactive', null, 404);
            }

            // Check for time conflicts
            $conflict = Booking::where('merchant_id', $request->merchant_id)
                ->where('booking_date', $request->booking_date)
                ->where('status', '!=', 'cancelled')
                ->where(function($query) use ($request) {
                    $startTime = $request->booking_time;
                    $endTime = date('H:i:s', strtotime($startTime . ' + ' . $request->duration_minutes . ' minutes'));
                    
                    $query->where(function($q) use ($startTime, $endTime) {
                        $q->whereBetween('booking_time', [$startTime, $endTime])
                          ->orWhere(function($subQ) use ($startTime, $endTime) {
                              $subQ->where('booking_time', '<=', $startTime)
                                   ->whereRaw("ADDTIME(booking_time, CONCAT(duration_minutes, ':00')) > ?", [$startTime]);
                          });
                    });
                })
                ->exists();

            if ($conflict) {
                return $this->error('Time slot is already booked', null, 409);
            }

            $booking = Booking::create([
                'user_id' => $user->id,
                'merchant_id' => $request->merchant_id,
                'service_name' => $request->service_name,
                'service_description' => $request->service_description,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'duration_minutes' => $request->duration_minutes,
                'price' => $request->price,
                'currency' => $request->currency,
                'status' => 'pending',
                'notes' => $request->notes,
                'special_requests' => $request->special_requests,
            ]);

            $booking->load(['user', 'merchant']);

            return $this->created(new BookingResource($booking), 'Booking created successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to create booking', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/bookings/{id}",
     *     summary="Update booking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="service_name", type="string", example="Updated Service"),
     *             @OA\Property(property="booking_date", type="string", format="date", example="2025-09-27"),
     *             @OA\Property(property="booking_time", type="string", format="time", example="15:30:00"),
     *             @OA\Property(property="notes", type="string", example="Updated notes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Booking")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $booking = Booking::find($id);

            if (!$booking) {
                return $this->notFound('Booking not found');
            }

            // Check permissions
            $user = Auth::user();
            $canUpdate = false;

            if ($user->hasRole(['admin', 'superadmin'])) {
                $canUpdate = true;
            } elseif ($user->hasRole('vendor')) {
                $merchant = Merchant::where('user_id', $user->id)->first();
                $canUpdate = $merchant && $booking->merchant_id === $merchant->id;
            } else {
                $canUpdate = $booking->user_id === $user->id && $booking->status === 'pending';
            }

            if (!$canUpdate) {
                return $this->forbidden('Access denied');
            }

            $validator = Validator::make($request->all(), [
                'service_name' => 'sometimes|string|max:255',
                'service_description' => 'nullable|string|max:1000',
                'booking_date' => 'sometimes|date|after_or_equal:today',
                'booking_time' => 'sometimes|date_format:H:i:s',
                'duration_minutes' => 'sometimes|integer|min:15|max:480',
                'price' => 'sometimes|numeric|min:0',
                'currency' => 'sometimes|string|size:3',
                'notes' => 'nullable|string|max:1000',
                'special_requests' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Check for time conflicts if date/time is being updated
            if ($request->has('booking_date') || $request->has('booking_time')) {
                $bookingDate = $request->booking_date ?? $booking->booking_date;
                $bookingTime = $request->booking_time ?? $booking->booking_time;
                $duration = $request->duration_minutes ?? $booking->duration_minutes;

                $conflict = Booking::where('merchant_id', $booking->merchant_id)
                    ->where('id', '!=', $booking->id)
                    ->where('booking_date', $bookingDate)
                    ->where('status', '!=', 'cancelled')
                    ->where(function($query) use ($bookingTime, $duration) {
                        $startTime = $bookingTime;
                        $endTime = date('H:i:s', strtotime($startTime . ' + ' . $duration . ' minutes'));
                        
                        $query->where(function($q) use ($startTime, $endTime) {
                            $q->whereBetween('booking_time', [$startTime, $endTime])
                              ->orWhere(function($subQ) use ($startTime, $endTime) {
                                  $subQ->where('booking_time', '<=', $startTime)
                                       ->whereRaw("ADDTIME(booking_time, CONCAT(duration_minutes, ':00')) > ?", [$startTime]);
                              });
                        });
                    })
                    ->exists();

                if ($conflict) {
                    return $this->error('Time slot is already booked', null, 409);
                }
            }

            $booking->update($request->only([
                'service_name', 'service_description', 'booking_date', 'booking_time',
                'duration_minutes', 'price', 'currency', 'notes', 'special_requests'
            ]));

            $booking->load(['user', 'merchant']);

            return $this->success(new BookingResource($booking), 'Booking updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update booking', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/bookings/{id}/status",
     *     summary="Update booking status",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed", "no_show"}, example="confirmed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking status updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Booking")
     *         )
     *     )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $booking = Booking::find($id);

            if (!$booking) {
                return $this->notFound('Booking not found');
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,confirmed,cancelled,completed,no_show',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Check permissions
            $user = Auth::user();
            $canUpdateStatus = false;

            if ($user->hasRole(['admin', 'superadmin'])) {
                $canUpdateStatus = true;
            } elseif ($user->hasRole('vendor')) {
                $merchant = Merchant::where('user_id', $user->id)->first();
                $canUpdateStatus = $merchant && $booking->merchant_id === $merchant->id;
            } else {
                // Users can only cancel their own bookings
                $canUpdateStatus = $booking->user_id === $user->id && $request->status === 'cancelled';
            }

            if (!$canUpdateStatus) {
                return $this->forbidden('Access denied');
            }

            $booking->update(['status' => $request->status]);

            $booking->load(['user', 'merchant']);

            return $this->success(new BookingResource($booking), 'Booking status updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update booking status', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/bookings/{id}",
     *     summary="Delete booking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Booking ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $booking = Booking::find($id);

            if (!$booking) {
                return $this->notFound('Booking not found');
            }

            // Check permissions
            $user = Auth::user();
            $canDelete = false;

            if ($user->hasRole(['admin', 'superadmin'])) {
                $canDelete = true;
            } elseif ($user->hasRole('vendor')) {
                $merchant = Merchant::where('user_id', $user->id)->first();
                $canDelete = $merchant && $booking->merchant_id === $merchant->id;
            } else {
                $canDelete = $booking->user_id === $user->id && $booking->status === 'pending';
            }

            if (!$canDelete) {
                return $this->forbidden('Access denied');
            }

            $booking->delete();

            return $this->success([], 'Booking deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete booking', $e->getMessage(), 500);
        }
    }
}