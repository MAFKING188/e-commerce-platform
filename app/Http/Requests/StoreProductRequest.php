<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * ODO 1: Change this from 'false' to a condition that checks if the current user 
     * is logged in and is an 'admin'. 
     * Hint: Look into $this->user() and how to check for admin status.
     */
    public function authorize(): bool
    {
        if($this->user() && $this->user()->role =="admin")
            {
                return true;
            }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'price' => 'required|numeric',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer',
            'description' => 'required|string'
        ];
    }
}
