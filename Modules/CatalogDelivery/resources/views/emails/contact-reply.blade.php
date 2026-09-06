<x-mail::message>

# Reply from SmartShop

Hi {{ $customerName }},

{{ $replyBody }}

<x-mail::panel>
**Your original inquiry ({{ $originalDate }}):**<br>
{!! nl2br(e($originalMessage)) !!}
</x-mail::panel>

If you have further questions, simply reply to this email or visit our <a href="{{ url('/') }}">support page</a>.

Regards,<br>
<strong>{{ $adminName }}</strong><br>
SmartShop Support Team

</x-mail::message>
