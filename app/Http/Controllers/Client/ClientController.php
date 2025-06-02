<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class ClientController extends Controller
{
    public function getClientDetails(Request $request){

        $user = Auth::user();

        return new ClientResource($user);
    }

    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'phone' => ['required', 'regex:/^\+?[0-9]{10,15}$/', 'unique:users,phone,' . $user->id],
                'date_of_birth' => 'required|date',
                'place_of_birth' => 'required|string|max:255',
                'blood_group' => 'required|string',
                'genotype' => 'required|string',
                'address' => 'nullable|string',
                'religion' => 'nullable|string',
                'nationality' => 'required|string',
                'weight' => 'required|numeric|min:0',
                'height' => 'required|numeric|min:0',
                'gender' => 'required|in:male,female,other',
                'about' => 'required|string|max:1000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ], [
                'email.unique' => 'This email is already in use.',
                'phone.unique' => 'This phone number is already registered.',
                'about.max' => 'About section must not exceed 1000 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($user->image && file_exists(public_path($user->image))) {
                    unlink(public_path($user->image));
                }

                $file = $request->file('image');
                $filename = time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/profile'), $filename);

                $validated['image'] = 'uploads/profile/' . $filename;
            }


            /** @var \App\Models\User $user */
            $user->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Profile updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed', [
                'user_id' => optional(Auth::user())->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = app()->environment('production')
            ? 'Something Went Wrong'
            : $e->getMessage();


            return response()->json([
                'message' => $message,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
