@php
    $steps = ['pending', 'paid', 'shipped', 'completed'];
    $current = array_search(strtolower($order->status), $steps, true);
@endphp

@if($current !== false)
    <div class="order-progress" role="img" aria-label="Order progress: {{ ucfirst($order->status) }}">
        @foreach($steps as $i => $step)
            <span class="order-progress__step {{ $i < $current ? 'is-done' : '' }} {{ $i === $current ? 'is-current' : '' }}">
                <span class="order-progress__dot"></span>
                <span class="order-progress__label">{{ ucfirst($step) }}</span>
            </span>
            @if(! $loop->last)
                <span class="order-progress__bar {{ $i < $current ? 'is-done' : '' }}"></span>
            @endif
        @endforeach
    </div>
@endif
