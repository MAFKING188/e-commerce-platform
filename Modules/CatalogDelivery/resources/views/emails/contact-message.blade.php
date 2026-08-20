<x-mail::message>
# New Contact Inquiry

**{{ $contactMessage->full_name }}** ({{ $contactMessage->email }}) sent a message through the site contact form:

{{ $contactMessage->message }}

<x-mail::panel>
Sent from IP `{{ $contactMessage->ip_address ?? 'unknown' }}`
{{ $contactMessage->created_at?->format('M d, Y H:i') }}
</x-mail::panel>

Reply to this email to answer the sender directly.

Regards,<br>
The SmartShop Support Team
</x-mail::message>