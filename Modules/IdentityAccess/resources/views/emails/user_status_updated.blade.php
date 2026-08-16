<x-app-layout>
<div style="background: var(--surface-100); padding: 4rem; border-radius: 2rem; border: 1px solid var(--border); max-width: 600px; margin: 4rem auto; text-align: center;">
    <h1 style="font-size: 2rem; font-weight: 800; margin-bottom: 2rem; color: var(--text-900);">Member Status Update</h1>
    
    <p style="font-size: 1.1rem; color: var(--text-600); line-height: 1.8; margin-bottom: 2rem;">
        Hello, <strong>{{ $user->name }}</strong>.
    </p>
    
    <p style="font-size: 1.1rem; color: var(--text-600); line-height: 1.8; margin-bottom: 2rem;">
        Your account status has been refined by the Administrative Command Center. 
        Your current status is now: <span class="cat-badge" style="background: var(--brand-accent-soft); color: var(--brand-accent); padding: 0.5rem 1rem; border-radius: 2rem; font-weight: 800; text-transform: uppercase;">{{ $user->status }}</span>.
    </p>

    @if($user->status === 'active')
        <p style="font-size: 1rem; color: var(--text-600); margin-bottom: 3rem;">
            You now have full access to your assigned role features.
        </p>
        <a href="{{ url('/') }}" class="btn btn-primary" style="padding: 1rem 3rem;">Access Platform</a>
    @else
        <p style="font-size: 1rem; color: var(--text-600); margin-bottom: 3rem;">
            If you have questions regarding this change, please contact our support team.
        </p>
    @endif

    <div style="margin-top: 4rem; padding-top: 2rem; border-top: 1px solid var(--border); color: var(--text-400); font-size: 0.85rem;">
        &copy; {{ date('Y') }} SmartShop Premium Ecosystem.
    </div>
</div>
</x-app-layout>
