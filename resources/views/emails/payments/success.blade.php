<x-mail::message>
# Acquisition Confirmed

We have successfully processed your payment for Order **#{{ $payment->order_id }}**. Your selected pieces are now being prepared for transition to your residence.

**Payment Details:**
- **Transaction ID:** {{ $payment->transaction_id }}
- **Amount:** ${{ number_format($payment->amount, 2) }}
- **Method:** {{ strtoupper($payment->provider) }}

<x-mail::button :url="route('orders.index')">
View Order Status
</x-mail::button>

Our logistics team will provide further updates as your package moves through the archival inspection process.

Thank you for choosing LUWI.

Best Regards,<br>
The LUWI Logistics Team
</x-mail::message>
