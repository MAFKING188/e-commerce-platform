<x-mail::message>
# Your Verification Code

Hi {{ $user->name }},

Use the code below to complete your sign-in:

# {{ $code }}

This code expires in **10 minutes**. If you did not request it, you can safely ignore this email.

Regards,<br>
The SmartShop Security Team
</x-mail::message>