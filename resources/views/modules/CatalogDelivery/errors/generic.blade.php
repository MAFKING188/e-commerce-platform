{{-- Catalog Delivery: Generic Error --}}
<div class="error-page" style="background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 4rem 2rem;">
    <div>
        <div class="error-code" style="font-size: 6rem; margin-bottom: 1rem;">!</div>
        <h1 style="font-size: 1.25rem; margin-bottom: 1rem; color: #f8fafc;">An error occurred</h1>
        <p style="font-size: 1rem; color: #64748b; margin-bottom: 2rem;">
            Something unexpected happened. Our team has been notified.
        </p>
        <a href="{{ url()->previous() ?? '/collection' }}" style="display: inline-block; background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 12px; text-decoration: none;">
            Return to Collection
        </a>
    </div>
</div>