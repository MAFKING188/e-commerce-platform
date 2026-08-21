@section('title', 'Edit Email Template | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-wrap-narrow">
    <a href="{{ route('admin.email-templates.index') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Email Templates
    </a>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Messaging</span>
            <h1 class="pc-title">Edit {{ $template->name }}</h1>
        </div>
    </div>

    <div class="pc-card">
        <form action="{{ route('admin.email-templates.update', $template->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="pc-form-grid">
                <div class="pc-field">
                    <label class="pc-field__label" for="name">Template Name</label>
                    <input id="name" type="text" name="name" class="pc-field__input" value="{{ old('name', $template->name) }}" placeholder="e.g. Newsletter — LUWI Digest" required>
                    @error('name')
                        <span class="pc-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="pc-form-grid">
                <div class="pc-field">
                    <label class="pc-field__label" for="subject">Email Subject</label>
                    <input id="subject" type="text" name="subject" class="pc-field__input" value="{{ old('subject', $template->subject) }}" placeholder="e.g. This Week's Curated Finds" required maxlength="150">
                    @error('subject')
                        <span class="pc-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="pc-form-grid">
                <div class="pc-field pc-field--full">
                    <label class="pc-field__label" for="body_markdown">Body (Markdown)</label>
                    <textarea id="body_markdown" name="body_markdown" class="pc-field__textarea" rows="15" required placeholder="# Hello {name}

Welcome to our weekly digest...

---

Regards,<br>
The SmartShop Team">{{ old('body_markdown', $template->body_markdown) }}</textarea>
                    <p class="pc-field__hint">Available placeholders: <code>{name}</code> (recipient name), <code>{email}</code> (recipient email)</p>
                    @error('body_markdown')
                        <span class="pc-field__error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="pc-form-actions">
                <button type="submit" class="btn btn-primary pc-btn-sm">Update Template</button>
                <a href="{{ route('admin.email-templates.index') }}" class="btn btn-ghost pc-btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

</x-app-layout>