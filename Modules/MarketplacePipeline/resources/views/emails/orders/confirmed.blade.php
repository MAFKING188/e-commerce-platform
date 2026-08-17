<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; padding: 0; }
        .wrapper { width: 100%; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background: #0f172a; padding: 40px; text-align: center; color: #ffffff; }
        .content { padding: 40px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; }
        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .order-id { font-size: 14px; font-weight: bold; color: #3b82f6; text-transform: uppercase; letter-spacing: 1px; }
        .total { font-size: 24px; font-weight: 800; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1 style="margin: 0; letter-spacing: -1px;">LUWI</h1>
                <p style="opacity: 0.8; margin-top: 10px;">Archive Confirmation</p>
            </div>
            <div class="content">
                <span class="order-id">Order #{{ $order->id }}</span>
                <h2 style="margin-top: 10px;">Your acquisition is confirmed.</h2>
                <p>Thank you for choosing LUWI. We are currently preparing your items for delivery. You can track your order status in your member profile.</p>
                
                <div class="total">
                    Total: ${{ number_format($order->total_price, 2) }}
                </div>

                <a href="{{ route('orders.index') }}" class="button">View Order History</a>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} SmartShop Premium E-Commerce. Curated for the modern minimalist.
            </div>
        </div>
    </div>
</body>
</html>
