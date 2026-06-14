<div style="background: var(--surface-100); border: 1px solid var(--border); border-radius: 1.5rem; padding: 0.5rem; display: flex; gap: 0.5rem; margin-bottom: 3rem; overflow-x: auto; white-space: nowrap;">
    <a href="{{ route('partner.dashboard') }}" class="btn {{ request()->routeIs('partner.dashboard') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Dashboard</a>
    <a href="{{ route('partner.orders.index') }}" class="btn {{ request()->routeIs('partner.orders.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Orders</a>
    <a href="{{ route('partner.inventory.index') }}" class="btn {{ request()->routeIs('partner.inventory.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Inventory</a>
    <a href="{{ route('partner.payouts.index') }}" class="btn {{ request()->routeIs('partner.payouts.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Earnings</a>
</div>
