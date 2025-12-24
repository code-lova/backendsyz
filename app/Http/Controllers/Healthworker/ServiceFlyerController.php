<?php

namespace App\Http\Controllers\Healthworker;

use App\Http\Controllers\Controller;
use App\Models\ServiceFlyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ServiceFlyerController extends Controller
{
    /**
     * Get active service flyers for health workers
     */
    public function index(Request $request)
    {
        try {
            $limit = $request->get('limit', 10); // Default limit of 10
            $limit = min($limit, 50); // Maximum limit of 50

            $flyers = ServiceFlyer::active()
                ->forAudience('healthworker')
                ->ordered()
                ->limit($limit)
                ->get(['uuid', 'title', 'description', 'image_url', 'sort_order', 'created_at']);

            return response()->json([
                'message' => 'Service flyers retrieved successfully',
                'data' => $flyers,
                'count' => $flyers->count()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve healthworker service flyers', [
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
     * Get a specific service flyer for health worker
     */
    public function show($uuid)
    {
        try {
            $flyer = ServiceFlyer::active()
                ->forAudience('healthworker')
                ->where('uuid', $uuid)
                ->firstOrFail(['uuid', 'title', 'description', 'image_url', 'created_at']);

            return response()->json([
                'message' => 'Service flyer retrieved successfully',
                'data' => $flyer
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service flyer not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve healthworker service flyer', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve service flyer',
                'error' => app()->environment('production') ? 'Something went wrong' : $e->getMessage()
            ], 500);
        }
    }
}