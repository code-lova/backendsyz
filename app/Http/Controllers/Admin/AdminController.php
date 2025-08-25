<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function getAdminDetails(Request $request){

        $user = $request->user();

        return new AdminResource($user);
    }
}
