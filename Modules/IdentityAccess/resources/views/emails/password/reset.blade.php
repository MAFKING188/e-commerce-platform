<x-mail::message>
# Reset Your Password, {{ $user->name }}

We received a request to reset the password for your LUWI Collection account. The link below expires in 60 minutes.

<x-mail::button :url="$resetUrl">
Choose a New Password
</x-mail::button>

If you did not request a password reset, no action is needed — your current password remains unchanged and you can safely ignore this email.

For any questions, our support team is available at [m.luwi0049@uca.ca.ma](mailto:m.luwi0049@uca.ca.ma).

Regards,<br>
The SmartShop Curators
</x-mail::message>