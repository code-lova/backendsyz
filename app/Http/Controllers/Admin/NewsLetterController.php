<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsLetterController extends Controller
{
    public function listSubscribers(Request $request){

        try{

            $query = NewsletterSubscriber::query();

            // Search by email
            if ($request->has('search') && !empty($request->search)) {
                $query->where('email', 'like', '%' . $request->search . '%');
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Paginate
            $perPage = $request->input('per_page', 15);
            $subscribers = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'subscribers' => $subscribers
            ], 200);

        }catch (\Exception $e) {
            // Log error for debugging
            Log::error('fetching all newsletter subscribers failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => app()->environment('production') ? 'Something went wrong.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }

    }

    public function deleteSubscriber($uuid){
        try{
            $subscriber = NewsletterSubscriber::where('uuid', $uuid)->first();

            if (!$subscriber) {
                return response()->json(['message' => 'Subscriber not found'], 404);
            }

            $subscriber->delete();

            return response()->json([
                'message' => 'Subscriber deleted successfully'
            ], 200);

        }catch (\Exception $e) {
            // Log error for debugging
            Log::error('Deleting newsletter subscriber failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => app()->environment('production') ? 'Something went wrong.' : $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
