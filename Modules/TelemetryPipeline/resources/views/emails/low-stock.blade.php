<h1>Low stock alert</h1>
<p>{{ $product->name }} has only {{ $product->stock }} unit(s) left.</p>
<p><a href="{{ route('partner.inventory.edit', $product) }}">Restock it here</a></p>