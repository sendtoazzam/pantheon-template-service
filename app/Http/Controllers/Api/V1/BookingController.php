<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Bookings",
 *     description="API Endpoints for Booking Management"
 * )
 */
class BookingController extends BaseApiController
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/bookings",
     *     summary="Get all bookings",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bookings retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
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
            // Mock bookings data - in a real app, this would come from a bookings table
            $bookings = [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'merchant_id' => 2,
                    'service' => 'Haircut',
                    'date' => '2024-01-15',
                    'time' => '10:00',
                    'status' => 'confirmed',
                    'created_at' => '2024-01-10T10:00:00Z',
                ],
                [
                    'id' => 2,
                    'user_id' => 3,
                    'merchant_id' => 2,
                    'service' => 'Massage',
                    'date' => '2024-01-16',
                    'time' => '14:00',
                    'status' => 'pending',
                    'created_at' => '2024-01-11T14:00:00Z',
                ]
            ];
            
            return $this->success($bookings, 'Bookings retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve bookings', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bookings/my-bookings",
     *     summary="Get authenticated user's bookings",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User bookings retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function myBookings(Request $request)
    {
        try {
            $user = $request->user();
            
            // Mock user bookings data
            $bookings = [
                [
                    'id' => 1,
                    'user_id' => $user->id,
                    'merchant_id' => 2,
                    'service' => 'Haircut',
                    'date' => '2024-01-15',
                    'time' => '10:00',
                    'status' => 'confirmed',
                    'created_at' => '2024-01-10T10:00:00Z',
                ]
            ];
            
            return $this->success($bookings, 'User bookings retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user bookings', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bookings/merchant-bookings",
     *     summary="Get merchant's bookings",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Merchant bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Merchant bookings retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function merchantBookings(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->hasRole('vendor')) {
                return $this->error('Access denied. Vendor role required.', 403);
            }
            
            // Mock merchant bookings data
            $bookings = [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'merchant_id' => $user->id,
                    'service' => 'Haircut',
                    'date' => '2024-01-15',
                    'time' => '10:00',
                    'status' => 'confirmed',
                    'created_at' => '2024-01-10T10:00:00Z',
                ],
                [
                    'id' => 2,
                    'user_id' => 3,
                    'merchant_id' => $user->id,
                    'service' => 'Massage',
                    'date' => '2024-01-16',
                    'time' => '14:00',
                    'status' => 'pending',
                    'created_at' => '2024-01-11T14:00:00Z',
                ]
            ];
            
            return $this->success($bookings, 'Merchant bookings retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve merchant bookings', 500, $e->getMessage());
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
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking retrieved successfully"),
     *             @OA\Property(property="data", type="object")
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
            // Mock booking data
            $booking = [
                'id' => $id,
                'user_id' => 1,
                'merchant_id' => 2,
                'service' => 'Haircut',
                'date' => '2024-01-15',
                'time' => '10:00',
                'status' => 'confirmed',
                'created_at' => '2024-01-10T10:00:00Z',
            ];
            
            return $this->success($booking, 'Booking retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve booking', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/bookings",
     *     summary="Create a new booking",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"merchant_id","service","date","time"},
     *             @OA\Property(property="merchant_id", type="integer", example=2),
     *             @OA\Property(property="service", type="string", example="Haircut"),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="time", type="string", example="10:00"),
     *             @OA\Property(property="notes", type="string", example="Please be gentle"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking created successfully"),
     *             @OA\Property(property="data", type="object")
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
            $request->validate([
                'merchant_id' => 'required|integer|exists:users,id',
                'service' => 'required|string|max:255',
                'date' => 'required|date|after:today',
                'time' => 'required|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            $user = $request->user();
            
            // Mock booking creation
            $booking = [
                'id' => rand(1000, 9999),
                'user_id' => $user->id,
                'merchant_id' => $request->merchant_id,
                'service' => $request->service,
                'date' => $request->date,
                'time' => $request->time,
                'notes' => $request->notes,
                'status' => 'pending',
                'created_at' => now()->toISOString(),
            ];

            return $this->success($booking, 'Booking created successfully', 201);
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create booking', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/bookings/{id}",
     *     summary="Update booking by ID",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="service", type="string", example="Haircut"),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="time", type="string", example="10:00"),
     *             @OA\Property(property="notes", type="string", example="Please be gentle"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Booking not found",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'service' => 'sometimes|string|max:255',
                'date' => 'sometimes|date|after:today',
                'time' => 'sometimes|string',
                'notes' => 'sometimes|nullable|string|max:1000',
            ]);

            // Mock booking update
            $booking = [
                'id' => $id,
                'user_id' => 1,
                'merchant_id' => 2,
                'service' => $request->service ?? 'Haircut',
                'date' => $request->date ?? '2024-01-15',
                'time' => $request->time ?? '10:00',
                'notes' => $request->notes,
                'status' => 'confirmed',
                'updated_at' => now()->toISOString(),
            ];

            return $this->success($booking, 'Booking updated successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update booking', 500, $e->getMessage());
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
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="confirmed"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Booking status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking status updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,confirmed,cancelled,completed',
            ]);

            $user = $request->user();
            
            // Check if user has permission to update booking status
            if (!$user->hasRole(['admin', 'superadmin', 'vendor'])) {
                return $this->error('Access denied. Insufficient permissions.', 403);
            }

            // Mock booking status update
            $booking = [
                'id' => $id,
                'user_id' => 1,
                'merchant_id' => 2,
                'service' => 'Haircut',
                'date' => '2024-01-15',
                'time' => '10:00',
                'status' => $request->status,
                'updated_at' => now()->toISOString(),
            ];

            return $this->success($booking, 'Booking status updated successfully');
        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update booking status', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/bookings/{id}",
     *     summary="Delete booking by ID",
     *     tags={"Bookings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Booking ID",
     *         @OA\Schema(type="integer", example=1)
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
            $user = Auth::user();
            
            // Check if user has permission to delete booking
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->error('Access denied. Insufficient permissions.', 403);
            }

            // Mock booking deletion
            return $this->success([], 'Booking deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete booking', 500, $e->getMessage());
        }
    }
}
