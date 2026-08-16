@section('title', 'Executive Dashboard | LUWI')

@section('styles')
<style>
    .dash-header {
        margin-bottom: 4rem;
    }

    .dash-header h1 {
        font-size: 3.5rem;
        font-weight: 800;
        letter-spacing: -0.05em;
        line-height: 1;
        margin-bottom: 1rem;
    }

    .dash-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
        margin-bottom: 6rem;
    }

    .dash-card {
        background: white;
        padding: 3rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .dash-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
        border-color: var(--brand-accent);
    }

    .dash-card h3 {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        letter-spacing: -0.02em;
    }

    .dash-card p {
        color: var(--text-600);
        margin-bottom: 2rem;
    }

    .stat-label {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: var(--brand-accent);
    }

    @media (max-width: 1024px) {
        .dash-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

<x-app-layout>

<div class="dash-header">
    <span class="stat-label">Administrative Portal</span>
    <h1>Platform <br>Overview.</h1>
</div>

<div class="dash-grid">
    <a href="{{ route('products.index') }}" class="dash-card">
        <h3>Catalog Management</h3>
        <p>Overview of current stock, pricing, and high-resolution product imagery.</p>
        <span class="btn btn-ghost" style="width: 100%;">Access Inventory</span>
    </a>

    <a href="{{ route('admin.orders.index') }}" class="dash-card">
        <h3>Transaction Flow</h3>
        <p>Monitor customer purchases, fulfillment status, and lifecycle events.</p>
        <span class="btn btn-ghost" style="width: 100%;">View Sales</span>
    </a>

    <a href="{{ route('users.index') }}" class="dash-card">
        <h3>Member Directory</h3>
        <p>Manage authenticated users, roles, and platform permissions.</p>
        <span class="btn btn-ghost" style="width: 100%;">View Members</span>
    </a>

    <a href="{{ route('categories.index') }}" class="dash-card">
        <h3>Collections</h3>
        <p>Organize products into logical tiers and manage catalog taxonomy.</p>
        <span class="btn btn-ghost" style="width: 100%;">Manage Categories</span>
    </a>
</div>

</x-app-layout>
