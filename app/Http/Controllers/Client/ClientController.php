<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClientProfileRequest;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class ClientController extends Controller
{
    public function getClientDetails(Request $request){

        $user = Auth::user();

        return new ClientResource($user);
    }

    public function update(UpdateClientProfileRequest $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $user->update($request->validated());

            return response()->json([
                'message' => 'Profile updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => Auth::user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),
            ], 500);
        }

    }


}
