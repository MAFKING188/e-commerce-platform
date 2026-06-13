<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
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
            'name' => 'required|string|max:150|unique:vendors,name,' . $this->route('vendor'),
            'description' => 'nullable|string|max:2000',
            'contact_info' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255'
        ];
    }
}
