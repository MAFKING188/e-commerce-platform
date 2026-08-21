@section('title', 'Email Your Buyers | LUWI Partner')

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-wrap-narrow">
    <a href="{{ route('partner.email.logs') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        My Send History
    </a>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Artisan Messaging</span>
            <h1 class="pc-title">Email Your Buyers</h1>
        </div>
    </div>

    @if($buyers->isEmpty())
        <div class="pc-card pc-empty-state">
            <h3>No buyers yet</h3>
            <p>You can email your customers once your first orders come in.</p>
        </div>
    @else
        <div class="pc-card">
            <form action="{{ route('partner.email.send') }}" method="POST">
                @csrf

                <div class="pc-form-grid">
                    <div class="pc-field pc-field--full">
                        <label class="pc-field__label" for="template-select">Start from a template (optional)</label>
                        <select id="template-select" class="pc-field__input">
                            <option value="">— Blank message —</option>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}" data-subject="{{ $t->subject }}" data-body="{{ $t->body_markdown }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <input type="hidden" name="template_id" id="template_id" value="">

                <div class="pc-form-grid">
                    <div class="pc-field pc-field--full">
                        <label class="pc-field__label" for="subject">Subject</label>
                        <input id="subject" type="text" name="subject" class="pc-field__input" value="{{ old('subject') }}" maxlength="150" required placeholder="Supports {name} and {email} placeholders">
                        @error('subject')<span class="pc-field__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="pc-form-grid">
                    <div class="pc-field pc-field--full">
                        <label class="pc-field__label" for="body">Body (Markdown)</label>
                        <textarea id="body" name="body" rows="12" required placeholder="# Hello {name}

A note about your order...">{{ old('body') }}</textarea>
                        <p class="pc-field__hint">Placeholders: <code>{name}</code>, <code>{email}</code> — replaced per recipient.</p>
                        @error('body')<span class="pc-field__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="pc-form-grid">
                    <div class="pc-field pc-field--full">
                        <span class="pc-field__label">Your buyers ({{ $buyers->count() }})</span>
                        @foreach($buyers as $buyer)
                            <label class="inline-label">
                                <input type="checkbox" name="user_ids[]" value="{{ $buyer->id }}">
                                {{ $buyer->name }} ({{ $buyer->email }})
                            </label>
                        @endforeach
                        <p class="pc-field__hint">You can only email customers who ordered from you. Limit 100 per send.</p>
                        @error('user_ids')<span class="pc-field__error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="pc-form-actions">
                    <button type="submit" class="btn btn-primary pc-btn-sm">Send</button>
                </div>
            </form>
        </div>
    @endif
</div>

</x-app-layout>