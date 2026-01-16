<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Company Details
            'abn' => ['required', 'string', 'size:11', 'unique:companies,abn'],
            'company_name' => ['required', 'string', 'max:255'], // We fallback to this if ABN lookup fails/is manual
            
            // User Details
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            
            // Address (Optional for now, but good to have)
            'address' => ['nullable', 'string'],
            'postcode' => ['nullable', 'string', 'max:10'],
        ];
    }
}