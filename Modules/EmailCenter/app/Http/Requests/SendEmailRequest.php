<?php

namespace Modules\EmailCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return $user && in_array($user->role, ['admin', 'partner'], true);
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:150',
            'body' => 'required|string|max:10000',
            'group' => 'nullable|in:all,admins,partners,members,newsletter',
            'user_ids' => 'nullable|array|max:100',
            'user_ids.*' => 'integer|exists:users,id',
            'newsletter_only' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('group') && ! $this->filled('user_ids')) {
                $validator->errors()->add('group', 'Pick a recipient group or at least one individual recipient.');
            }
        });
    }
}