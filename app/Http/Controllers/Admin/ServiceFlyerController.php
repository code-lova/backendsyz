<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceFlyer;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceFlyerController extends Controller
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }
    /**
     * Get all service flyers for admin
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $targetAudience = $request->get('target_audience');
            $status = $request->get('status'); // active, inactive, all

            $query = ServiceFlyer::with('creator:uuid,name,email')
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc');

            // Search functionality
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by target audience
            if ($targetAudience && in_array($targetAudience, ['client', 'healthworker', 'both'])) {
                $query->where('target_audience', $targetAudience);
            }

            // Filter by status
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }

            $flyers = $query->paginate($perPage);

            return response()->json([
                'message' => 'Service flyers retrieved successfully',
                'data' => $flyers
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve service flyers', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service flyers',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific service flyer (this function is not currently in use)
     */
    public function show($uuid)
    {
        try {
            $flyer = ServiceFlyer::with('creator:uuid,name,email')
                ->where('uuid', $uuid)
                ->firstOrFail();

            return response()->json([
                'message' => 'Service flyer retrieved successfully',
                'data' => $flyer
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service flyer not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve service flyer', [
                'uuid' => $uuid,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service flyer',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new service flyer
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'image_url' => 'required|url',
                'image_public_id' => 'required|string',
                'target_audience' => ['required', Rule::in(['client', 'healthworker', 'both'])],
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0'
            ], [
                'title.required' => 'Flyer title is required',
                'image_url.required' => 'Image URL is required',
                'image_url.url' => 'Please provide a valid image URL',
                'image_public_id.required' => 'Image public ID is required',
                'target_audience.required' => 'Target audience is required',
                'target_audience.in' => 'Invalid target audience. Must be client, healthworker, or both'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $validated = $validator->validated();
            $validated['created_by'] = Auth::user()->uuid;
            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['sort_order'] = $validated['sort_order'] ?? 0;

            $flyer = ServiceFlyer::create($validated);

            DB::commit();

            Log::info('Service flyer created', [
                'flyer_id' => $flyer->id,
                'flyer_uuid' => $flyer->uuid,
                'admin_id' => Auth::id(),
                'title' => $flyer->title
            ]);

            return response()->json([
                'message' => 'Service flyer created successfully',
                'data' => $flyer->load('creator:uuid,name,email')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create service flyer', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create service flyer',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a service flyer
     */
    public function update(Request $request, $uuid)
    {
        try {
            $flyer = ServiceFlyer::where('uuid', $uuid)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:1000',
                'image_url' => 'sometimes|url',
                'image_public_id' => 'sometimes|string',
                'target_audience' => ['sometimes', Rule::in(['client', 'healthworker', 'both'])],
                'is_active' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:0'
            ], [
                'image_url.url' => 'Please provide a valid image URL',
                'target_audience.in' => 'Invalid target audience. Must be client, healthworker, or both'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $validated = $validator->validated();

            // Handle image replacement
            if (isset($validated['image_url']) && isset($validated['image_public_id'])) {
                // Only delete old image if the public_id has actually changed
                if ($flyer->image_public_id && $flyer->image_public_id !== $validated['image_public_id']) {
                    $this->cloudinaryService->deleteImage($flyer->image_public_id);

                    Log::info('Image replaced for service flyer', [
                        'flyer_uuid' => $flyer->uuid,
                        'old_public_id' => $flyer->image_public_id,
                        'new_public_id' => $validated['image_public_id'],
                        'admin_id' => Auth::id()
                    ]);
                } elseif ($flyer->image_public_id === $validated['image_public_id']) {
                    Log::info('Image update skipped - same public_id', [
                        'flyer_uuid' => $flyer->uuid,
                        'public_id' => $flyer->image_public_id,
                        'admin_id' => Auth::id()
                    ]);
                }
            }

            $flyer->update($validated);

            DB::commit();

            Log::info('Service flyer updated', [
                'flyer_id' => $flyer->id,
                'flyer_uuid' => $flyer->uuid,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Service flyer updated successfully',
                'data' => $flyer->load('creator:uuid,name,email')
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service flyer not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update service flyer', [
                'uuid' => $uuid,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update service flyer',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a service flyer
     */
    public function destroy($uuid)
    {
        try {
            $flyer = ServiceFlyer::where('uuid', $uuid)->firstOrFail();

            DB::beginTransaction();

            // Delete image from Cloudinary
            if ($flyer->image_public_id) {
                $this->cloudinaryService->deleteImage($flyer->image_public_id);
            }

            $flyerData = [
                'id' => $flyer->id,
                'uuid' => $flyer->uuid,
                'title' => $flyer->title,
                'image_public_id' => $flyer->image_public_id
            ];

            $flyer->delete();

            DB::commit();

            Log::info('Service flyer deleted', [
                'flyer_data' => $flyerData,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Service flyer deleted successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service flyer not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete service flyer', [
                'uuid' => $uuid,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to delete service flyer',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle flyer status (active/inactive)
     */
    public function toggleStatus($uuid)
    {
        try {
            $flyer = ServiceFlyer::where('uuid', $uuid)->firstOrFail();

            DB::beginTransaction();

            $flyer->is_active = !$flyer->is_active;
            $flyer->save();

            DB::commit();

            Log::info('Service flyer status toggled', [
                'flyer_uuid' => $flyer->uuid,
                'new_status' => $flyer->is_active ? 'active' : 'inactive',
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Service flyer status updated successfully',
                'data' => $flyer
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service flyer not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to toggle service flyer status', [
                'uuid' => $uuid,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to update flyer status',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update flyer sort order
     */
    public function updateSortOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'flyers' => 'required|array|min:1',
                'flyers.*.uuid' => 'required|string',
                'flyers.*.sort_order' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            foreach ($request->flyers as $flyerData) {
                ServiceFlyer::where('uuid', $flyerData['uuid'])
                    ->update(['sort_order' => $flyerData['sort_order']]);
            }

            DB::commit();

            Log::info('Service flyers sort order updated', [
                'flyers_count' => count($request->flyers),
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Sort order updated successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update sort order', [
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to update sort order',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }
}
