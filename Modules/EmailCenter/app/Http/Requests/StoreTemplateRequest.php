<?php

namespace Modules\EmailCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:email_templates,name',
            'subject' => 'required|string|max:150',
            'body_markdown' => 'required|string',
        ];
    }
}