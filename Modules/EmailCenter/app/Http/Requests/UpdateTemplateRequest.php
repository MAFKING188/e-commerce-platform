<?php

namespace Modules\EmailCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        $templateId = $this->route('id');
        return [
            'name' => 'required|string|max:100|unique:email_templates,name,' . $templateId,
            'subject' => 'required|string|max:150',
            'body_markdown' => 'required|string',
        ];
    }
}