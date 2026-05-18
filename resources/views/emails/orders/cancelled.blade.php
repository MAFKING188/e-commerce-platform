<x-mail::message>
# Order Cancelled

Your order **#{{ $order->id }}** has been successfully cancelled. The pieces have been restored to the LUWI Archive, and any pending checkout sessions have been voided.

**Order Summary:**
- **Order ID:** #{{ $order->id }}
- **Total Value:** ${{ number_format($order->total_price, 2) }}
- **Status:** Cancelled

<x-mail::button :url="route('shop')">
Continue Discovery
</x-mail::button>

If you believe this was an error or would like to discuss a future acquisition, please reach out to our curators.

Regards,<br>
The LUWI Curators
</x-mail::message>
