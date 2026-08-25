{{-- Partner Hub: Error 500 --}}
<div class="pc-panel pc-panel--error" style="background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 4rem 2rem;">
    <div>
        <div class="error-code" style="font-size: 8rem; font-weight: 800; color: #3b82f6; margin-bottom: 1rem;">500</div>
        <h3 class="pc-panel__title" style="font-size: 1.5rem; margin-bottom: 1rem; color: #f8fafc;">Something went wrong</h3>
        <p class="pc-panel__style" style="font-size: 1rem; color: #64748b; margin-bottom: 2rem;">
            We're experiencing technical difficulties. Please try again or contact support.
        </p>
        <a href="{{ route('partner.dashboard') }}" style="display: inline-block; background: #3b82f6; color: white; padding: 0.75rem 1.5rem; border-radius: 12px; text-decoration: none;">
            Return to Dashboard
        </a>
    </div>
</div>