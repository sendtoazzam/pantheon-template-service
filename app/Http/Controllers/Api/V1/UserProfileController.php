<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserProfileResource;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="User Profiles",
 *     description="API Endpoints for Extended User Profile Management"
 * )
 */
class UserProfileController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/user-profiles",
     *     summary="Get all user profiles",
     *     tags={"User Profiles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of profiles per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profiles retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profiles retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserProfile"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            // Only admins and superadmins can view all profiles
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->forbidden('Access denied. Admin role required.');
            }

            $profiles = UserProfile::with('user')
                ->paginate($request->get('per_page', 15));

            return $this->paginated($profiles, 'User profiles retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user profiles', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user-profiles/my",
     *     summary="Get current user's extended profile",
     *     tags={"User Profiles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserProfile")
     *         )
     *     )
     * )
     */
    public function myProfile()
    {
        try {
            $user = Auth::user();
            
            $profile = UserProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                // Create default profile if it doesn't exist
                $profile = UserProfile::create([
                    'user_id' => $user->id,
                    'bio' => null,
                    'date_of_birth' => null,
                    'gender' => null,
                    'nationality' => null,
                    'address' => null,
                    'city' => null,
                    'state' => null,
                    'country' => null,
                    'postal_code' => null,
                    'phone_secondary' => null,
                    'emergency_contact_name' => null,
                    'emergency_contact_phone' => null,
                    'emergency_contact_relationship' => null,
                ]);
            }

            $profile->load('user');

            return $this->success(new UserProfileResource($profile), 'User profile retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user-profiles/my",
     *     summary="Update current user's extended profile",
     *     tags={"User Profiles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="bio", type="string", example="Software developer with 5 years experience"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other", "prefer_not_to_say"}, example="male"),
     *             @OA\Property(property="nationality", type="string", example="American"),
     *             @OA\Property(property="address", type="string", example="123 Main St, City, State 12345"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="state", type="string", example="NY"),
     *             @OA\Property(property="country", type="string", example="United States"),
     *             @OA\Property(property="postal_code", type="string", example="10001"),
     *             @OA\Property(property="phone_secondary", type="string", example="+1234567891"),
     *             @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="emergency_contact_phone", type="string", example="+1234567892"),
     *             @OA\Property(property="emergency_contact_relationship", type="string", example="Spouse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserProfile")
     *         )
     *     )
     * )
     */
    public function updateMyProfile(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'bio' => 'nullable|string|max:1000',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'nationality' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'phone_secondary' => 'nullable|string|max:20',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $profile = UserProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                $profile = UserProfile::create([
                    'user_id' => $user->id,
                    ...$request->only([
                        'bio', 'date_of_birth', 'gender', 'nationality',
                        'address', 'city', 'state', 'country', 'postal_code',
                        'phone_secondary', 'emergency_contact_name',
                        'emergency_contact_phone', 'emergency_contact_relationship'
                    ])
                ]);
            } else {
                $profile->update($request->only([
                    'bio', 'date_of_birth', 'gender', 'nationality',
                    'address', 'city', 'state', 'country', 'postal_code',
                    'phone_secondary', 'emergency_contact_name',
                    'emergency_contact_phone', 'emergency_contact_relationship'
                ]));
            }

            $profile->load('user');

            return $this->success(new UserProfileResource($profile), 'User profile updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update user profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user-profiles/{id}",
     *     summary="Get user profile by ID",
     *     tags={"User Profiles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Profile ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserProfile")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $profile = UserProfile::with('user')->find($id);

            if (!$profile) {
                return $this->notFound('User profile not found');
            }

            // Check permissions
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $profile->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            return $this->success(new UserProfileResource($profile), 'User profile retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user-profiles",
     *     summary="Create user profile",
     *     tags={"User Profiles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="bio", type="string", example="Software developer"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other", "prefer_not_to_say"}, example="male")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User profile created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserProfile")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Only admins and superadmins can create profiles for other users
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin'])) {
                return $this->forbidden('Access denied. Admin role required.');
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id|unique:user_profiles,user_id',
                'bio' => 'nullable|string|max:1000',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'nationality' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'phone_secondary' => 'nullable|string|max:20',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $profile = UserProfile::create($request->all());
            $profile->load('user');

            return $this->created(new UserProfileResource($profile), 'User profile created successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to create user profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user-profiles/{id}",
     *     summary="Update user profile",
     *     tags={"User Profiles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Profile ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="bio", type="string", example="Updated bio"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other", "prefer_not_to_say"}, example="female")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserProfile")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $profile = UserProfile::find($id);

            if (!$profile) {
                return $this->notFound('User profile not found');
            }

            // Check permissions
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'superadmin']) && $profile->user_id !== $user->id) {
                return $this->forbidden('Access denied');
            }

            $validator = Validator::make($request->all(), [
                'bio' => 'nullable|string|max:1000',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'nationality' => 'nullable|string|max:100',
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'phone_secondary' => 'nullable|string|max:20',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $profile->update($request->only([
                'bio', 'date_of_birth', 'gender', 'nationality',
                'address', 'city', 'state', 'country', 'postal_code',
                'phone_secondary', 'emergency_contact_name',
                'emergency_contact_phone', 'emergency_contact_relationship'
            ]));

            $profile->load('user');

            return $this->success(new UserProfileResource($profile), 'User profile updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update user profile', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/user-profiles/{id}",
     *     summary="Delete user profile",
     *     tags={"User Profiles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User Profile ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User profile deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User profile deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $profile = UserProfile::find($id);

            if (!$profile) {
                return $this->notFound('User profile not found');
            }

            // Check permissions - only superadmin can delete profiles
            $user = Auth::user();
            if (!$user->hasRole('superadmin')) {
                return $this->forbidden('Access denied. Only superadmin can delete profiles.');
            }

            $profile->delete();

            return $this->success([], 'User profile deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete user profile', $e->getMessage(), 500);
        }
    }
}
