{{-- Marketplace Pipeline: Validation Error --}}
<div class="error-panel" style="background: #1e293b; color: #c5c6c7; max-width: 500px; padding: 2rem; border-radius: 12px; margin: 0 auto;">
    <h2 style="color: #3b82f6; margin-bottom: 1rem;">Validation Error</h2>
    <p style="color: #64748b; margin-bottom: 1rem;">
        Please check the form and try again.
    </p>
    <ul style="color: #c5c6c7; margin: 0; padding-left: 1rem;">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <a href="{{ url()->previous() ?? '/shop' }}" style="display: block; width: fit-content; margin: 1.5rem auto 0; background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 12px; text-align: center; text-decoration: none;">
        Return to Shop
    </a>
</div>