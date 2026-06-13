<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150|unique:partners,name',
            'description' => 'nullable|string|max:2000',
            'contact_info' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'user_id' => 'required|exists:users,id'
        ];
    }

    /**
     * Custom messages for partner validation.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This partner artisan is already registered in our ecosystem.',
            'name.max' => 'The artisan name must be refined (too long).',
        ];
    }
}
