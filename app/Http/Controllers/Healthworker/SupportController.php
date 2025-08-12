<?php

namespace App\Http\Controllers\Healthworker;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    public function createSupportMessage(Request $request){
        try{

            $user = $request->user();

            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'subject' => 'required|string',
                'message' => 'required|string|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $supportMessage = new SupportMessage();
            $supportMessage->user_uuid = $user->uuid; // If authenticated
            $supportMessage->subject = $request->subject;
            $supportMessage->message = $request->message;
            $supportMessage->status = 'Pending';
            $supportMessage->save();

            //TODO:  After saving make sure to send an email with the
            //TODO: with the content to admin email

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Support tickek submitted successfully.',
            ], 201);

        }catch(\Exception $e){
            DB::rollBack();
            Log::error('support update failed', [
                'user_id' => $user->id,
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



    public function getSupportMessages(Request $request) {
        try{

            $user = $request->user();
            $supportDetails = SupportMessage::where('user_uuid', $user->uuid)->latest()->get();

            if ($supportDetails->isEmpty()) {
                return response()->json(['message' => 'No Support ticket found'], 404);
            }

             // Format all messages
            $formattedMessages = $supportDetails->map(function ($msg) {
                return [
                    'id' => $msg->uuid,
                    'subject' => $msg->subject,
                    'message' => $msg->message,
                    'status' => $msg->status,
                ];
            });

            return response()->json([
                'tickets' => $formattedMessages
            ]);

        }catch(\Exception $e){
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
