@section('title', 'Partner Ecosystem | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Supply Chain</span>
        <h1 class="pc-title">Ecosystem</h1>
    </div>
    <a href="{{ route('admin.partners.create') }}" class="btn btn-primary pc-btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Establish New Partner
    </a>
</div>

<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>Partner</th>
                <th>Supply Scale</th>
                <th>Contact Registry</th>
                <th class="is-right">Operations</th>
            </tr>
        </thead>
        <tbody>
            @forelse($partners as $partner)
                <tr>
                    <td>
                        <div class="partner-info">
                            <div class="partner-icon">V</div>
                            <div>
                                <div class="is-strong">{{ $partner->name }}</div>
                                <div class="is-muted">Registered Partner</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="product-count">{{ $partner->products_count }} Collections</span>
                    </td>
                    <td class="is-muted">
                        {{ $partner->contact_info ?? 'N/A' }}
                    </td>
                    <td class="is-right">
                        <div class="pc-row-actions pc-row-actions--end">
                            <a href="{{ route('admin.partners.show', $partner->id) }}" class="pc-btn-sm">Inventory</a>
                            <a href="{{ route('admin.partners.edit', $partner->id) }}" class="pc-btn-sm">Edit</a>
                            <form action="{{ route('admin.partners.destroy', $partner->id) }}" method="POST" data-confirm="Terminate this partner relationship?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pc-btn-sm pc-btn-sm--danger">Terminate</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        @include('partials.partner.empty-state', [
                            'icon' => 'partners',
                            'title' => 'No partners yet',
                            'text' => 'Establish your first partner relationship to start building the supply chain.',
                        ])
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</x-app-layout>