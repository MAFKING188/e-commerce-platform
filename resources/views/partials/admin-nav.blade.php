<div style="background: var(--surface-100); border: 1px solid var(--border); border-radius: 1.5rem; padding: 0.5rem; display: flex; gap: 0.5rem; margin-bottom: 3rem; overflow-x: auto; white-space: nowrap;">
    <a href="{{ route('admin.dashboard') }}" class="btn {{ request()->routeIs('admin.dashboard') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Overview</a>
    <a href="{{ route('admin.orders.index') }}" class="btn {{ request()->routeIs('admin.orders.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Fulfillment</a>
    <a href="{{ route('admin.products.index') }}" class="btn {{ request()->routeIs('admin.products.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Inventory</a>
    <a href="{{ route('admin.users.index') }}" class="btn {{ request()->routeIs('admin.users.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Members</a>
    <a href="{{ route('admin.vendors.index') }}" class="btn {{ request()->routeIs('admin.vendors.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Supply Chain</a>
    <a href="{{ route('admin.reviews.index') }}" class="btn {{ request()->routeIs('admin.reviews.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Community</a>
    <a href="{{ route('admin.categories.index') }}" class="btn {{ request()->routeIs('admin.categories.*') ? 'btn-primary' : 'btn-ghost' }}" style="padding: 0.75rem 1.5rem;">Categories</a>
</div>
