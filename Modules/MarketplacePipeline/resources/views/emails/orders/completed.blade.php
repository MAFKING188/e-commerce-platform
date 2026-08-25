<x-mail::message>
# Your Order Is Complete

Hello,

Your order **#{{ $order->id }}** has been delivered and is now marked as complete. We hope every piece brings you joy.

**Order Summary:**
- **Order ID:** #{{ $order->id }}
- **Total Value:** ${{ number_format($order->total_price, 2) }}
- **Status:** Completed

Thank you for collecting with LUWI. We would love to see how you style your new pieces.

<x-mail::button :url="route('orders.index')">
View My Orders
</x-mail::button>

With gratitude,<br>
The LUWI Curators
</x-mail::message>
