<?php

use Illuminate\Support\Facades\Route;
use App\Mail\NewSupportMessage;
use App\Mail\SupportTicketConfirmationMail;

Route::get('/', function () {
    return view('welcome');
});


