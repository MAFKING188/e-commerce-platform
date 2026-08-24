<h2>Action needed: Payment proof rejected for Order #{{ $order->id }}</h2>

<p>Reason: {{ $reason }}</p>

<p>Your proof of payment was not accepted. Please upload a new proof <a href="{{ url('/orders/{$order->id}/upload-proof') }}">here</a>.</p>

<p>Your order will remain pending until a valid proof is submitted.</p>

<p>Thank you for your understanding.</p>