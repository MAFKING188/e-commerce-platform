@php
    $defaultMap = [
        'pending' => 'warn',
        'processing' => 'warn',
        'paid' => 'ok',
        'completed' => 'ok',
        'processed' => 'ok',
        'cancelled' => 'danger',
        'low' => 'danger',
    ];
    $variant = $variant ?? ($defaultMap[$status] ?? 'neutral');
@endphp
<span class="pc-badge pc-badge--{{ $variant }}">{{ ucfirst($status) }}</span>