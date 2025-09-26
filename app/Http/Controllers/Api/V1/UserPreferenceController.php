<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\UserPreferenceResource;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="User Preferences",
 *     description="API Endpoints for User Preference Management"
 * )
 */
class UserPreferenceController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/user-preferences",
     *     summary="Get user preferences",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by preference category",
     *         required=false,
     *         @OA\Schema(type="string", enum={"notifications", "privacy", "appearance", "language", "security"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User preferences retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User preferences retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserPreference"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = UserPreference::where('user_id', $user->id);

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            $preferences = $query->orderBy('category')
                               ->orderBy('key')
                               ->get();

            return $this->success(UserPreferenceResource::collection($preferences), 'User preferences retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user preferences', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user-preferences/{key}",
     *     summary="Get specific user preference",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         description="Preference key",
     *         required=true,
     *         @OA\Schema(type="string", example="email_notifications")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User preference retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User preference retrieved successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserPreference")
     *         )
     *     )
     * )
     */
    public function show($key)
    {
        try {
            $user = Auth::user();
            
            $preference = UserPreference::where('user_id', $user->id)
                ->where('key', $key)
                ->first();

            if (!$preference) {
                return $this->notFound('Preference not found');
            }

            return $this->success(new UserPreferenceResource($preference), 'User preference retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user preference', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user-preferences",
     *     summary="Create or update user preference",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category","key","value","data_type"},
     *             @OA\Property(property="category", type="string", example="notifications"),
     *             @OA\Property(property="key", type="string", example="email_notifications"),
     *             @OA\Property(property="value", type="string", example="true"),
     *             @OA\Property(property="data_type", type="string", enum={"boolean", "string", "integer", "float", "array", "object"}, example="boolean"),
     *             @OA\Property(property="is_public", type="boolean", example=false),
     *             @OA\Property(property="description", type="string", example="Receive email notifications")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User preference created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User preference created successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserPreference")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'category' => 'required|string|max:100',
                'key' => 'required|string|max:100',
                'value' => 'required|string|max:1000',
                'data_type' => 'required|in:boolean,string,integer,float,array,object',
                'is_public' => 'boolean',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            // Check if preference already exists
            $preference = UserPreference::where('user_id', $user->id)
                ->where('key', $request->key)
                ->first();

            if ($preference) {
                // Update existing preference
                $preference->update($request->only([
                    'value', 'data_type', 'is_public', 'description'
                ]));
                
                return $this->success(new UserPreferenceResource($preference), 'User preference updated successfully');
            } else {
                // Create new preference
                $preference = UserPreference::create([
                    'user_id' => $user->id,
                    'category' => $request->category,
                    'key' => $request->key,
                    'value' => $request->value,
                    'data_type' => $request->data_type,
                    'is_public' => $request->boolean('is_public'),
                    'description' => $request->description,
                ]);

                return $this->created(new UserPreferenceResource($preference), 'User preference created successfully');
            }

        } catch (\Exception $e) {
            return $this->error('Failed to create user preference', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user-preferences/{key}",
     *     summary="Update user preference",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         description="Preference key",
     *         required=true,
     *         @OA\Schema(type="string", example="email_notifications")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="string", example="false"),
     *             @OA\Property(property="is_public", type="boolean", example=true),
     *             @OA\Property(property="description", type="string", example="Updated description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User preference updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User preference updated successfully"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserPreference")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $key)
    {
        try {
            $user = Auth::user();
            
            $preference = UserPreference::where('user_id', $user->id)
                ->where('key', $key)
                ->first();

            if (!$preference) {
                return $this->notFound('Preference not found');
            }

            $validator = Validator::make($request->all(), [
                'value' => 'sometimes|string|max:1000',
                'data_type' => 'sometimes|in:boolean,string,integer,float,array,object',
                'is_public' => 'boolean',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $preference->update($request->only([
                'value', 'data_type', 'is_public', 'description'
            ]));

            return $this->success(new UserPreferenceResource($preference), 'User preference updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update user preference', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/user-preferences/{key}",
     *     summary="Delete user preference",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="key",
     *         in="path",
     *         description="Preference key",
     *         required=true,
     *         @OA\Schema(type="string", example="email_notifications")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User preference deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User preference deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy($key)
    {
        try {
            $user = Auth::user();
            
            $preference = UserPreference::where('user_id', $user->id)
                ->where('key', $key)
                ->first();

            if (!$preference) {
                return $this->notFound('Preference not found');
            }

            $preference->delete();

            return $this->success([], 'User preference deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete user preference', $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user-preferences/bulk-update",
     *     summary="Bulk update user preferences",
     *     tags={"User Preferences"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"preferences"},
     *             @OA\Property(property="preferences", type="array", @OA\Items(
     *                 @OA\Property(property="key", type="string", example="email_notifications"),
     *                 @OA\Property(property="value", type="string", example="true"),
     *                 @OA\Property(property="data_type", type="string", example="boolean")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User preferences updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User preferences updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="updated_count", type="integer", example=3)
     *             )
     *         )
     *     )
     * )
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $user = Auth::user();
            
            $validator = Validator::make($request->all(), [
                'preferences' => 'required|array',
                'preferences.*.key' => 'required|string|max:100',
                'preferences.*.value' => 'required|string|max:1000',
                'preferences.*.data_type' => 'required|in:boolean,string,integer,float,array,object',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors());
            }

            $updatedCount = 0;

            foreach ($request->preferences as $pref) {
                $preference = UserPreference::where('user_id', $user->id)
                    ->where('key', $pref['key'])
                    ->first();

                if ($preference) {
                    $preference->update([
                        'value' => $pref['value'],
                        'data_type' => $pref['data_type'],
                    ]);
                    $updatedCount++;
                } else {
                    UserPreference::create([
                        'user_id' => $user->id,
                        'category' => 'general',
                        'key' => $pref['key'],
                        'value' => $pref['value'],
                        'data_type' => $pref['data_type'],
                        'is_public' => false,
                    ]);
                    $updatedCount++;
                }
            }

            return $this->success(['updated_count' => $updatedCount], 'User preferences updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update user preferences', $e->getMessage(), 500);
        }
    }
}
