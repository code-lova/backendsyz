<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ensure only logged-in users can update their profile
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'required',
                'regex:/^\+?[1-9]\d{9,14}$/',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'country' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'religion' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Non-binary', 'Transgender', 'Bigender'])],
            'about' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already in use.',
            'phone.unique' => 'This phone number is already registered.',
            'about.max' => 'About section must not exceed 1000 characters.',
        ];
    }

}
