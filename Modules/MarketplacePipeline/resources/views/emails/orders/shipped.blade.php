<x-mail::message>
# Good News — Your Order Has Shipped

Hello,

Your order **#{{ $order->id }}** has been carefully packed by its artisan and is now on its way to you.

**Order Summary:**
- **Order ID:** #{{ $order->id }}
- **Total Value:** ${{ number_format($order->total_price, 2) }}
- **Status:** Shipped

You can follow the status of your order at any time from your account.

<x-mail::button :url="route('orders.index')">
Track My Order
</x-mail::button>

Thank you for collecting with LUWI.

Regards,<br>
The LUWI Curators
</x-mail::message>