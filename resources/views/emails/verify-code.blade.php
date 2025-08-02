<x-mail::message>
<div style="text-align: center; margin-bottom: 20px;">
    <img src="https://www.supracarer.com/assets/images/logo.png" alt="Supracarer Logo" width="150" height="auto">
</div>

# Hello {{ $name }}

Use the following code to verify your email address:<br>
NOTE: This code will expire in 10 mins.

<h1>{{ $email_verification_code }}</h1>

<br>

Supracarer<br>
{{ config('app.tagline') }}
</x-mail::message>




