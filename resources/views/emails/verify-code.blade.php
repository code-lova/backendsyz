{{-- @php
   $verificationUrl = config('app.frontend_url') . '/verify-email';
@endphp
 --}}

<x-mail::message>
# Hello {{ $name }}
# Welcome to Supracarer you own In-Home Health Care App.


Use the following code to verify your email address:<br>
NOTE: This code will expire in 10 mins.

<h1>{{ $email_verification_code }}</h1>

<br>

{{-- (Optionally) You can click the button below to open the email verification form <br>

@component('mail::button', ['url' => $verificationUrl])
Go to Verification Form
@endcomponent --}}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>




