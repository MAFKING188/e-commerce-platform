@section('title', 'Email Templates | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Messaging</span>
        <h1 class="pc-title">Email Templates</h1>
    </div>
    <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary pc-btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Create Template
    </a>
</div>

@if($templates->isEmpty())
    <div class="pc-card pc-empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="M22 4L2 22M2 4l20 18"/>
        </svg>
        <h3>No email templates yet</h3>
        <p>Create your first template to start sending branded emails to users.</p>
        <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary pc-btn-sm mt-4">Create Template</a>
    </div>
@else
    <div class="pc-table-wrap">
        <table class="pc-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Created By</th>
                    <th>Created</th>
                    <th class="pc-table-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $template)
                    <tr>
                        <td>{{ $template->name }}</td>
                        <td>{{ $template->subject }}</td>
                        <td>{{ $template->creator ? $template->creator->name : 'Seeded' }}</td>
                        <td>{{ $template->created_at->format('M d, Y') }}</td>
                        <td class="pc-table-actions">
                            <a href="{{ route('admin.email-templates.edit', $template->id) }}" class="pc-action-link">Edit</a>
                            <form action="{{ route('admin.email-templates.destroy', $template->id) }}" method="POST" data-confirm="Delete this email template?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pc-action-link pc-action-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pc-pagination">
        {{ $templates->links() }}
    </div>
@endif

</x-app-layout>