<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Track your ALAS order</title></head>
<body style="margin:0;background:#f7f4f3;color:#111;font-family:Arial,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center" style="padding:32px 16px">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border:1px solid #e5e0de">
<tr><td style="padding:36px"><div style="font-family:Georgia,serif;font-size:38px;font-weight:700">ALAS</div><p style="margin:32px 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#777">Order received</p><h1 style="margin:0;font-family:Georgia,serif;font-size:28px">Track {{ $order->order_number }}</h1>
<p style="margin:20px 0;line-height:1.7;color:#555">Hi {{ $order->customer_name }}, keep this email. Use the private link below to check payment, preparation, courier, and delivery updates for your order.</p>
<p style="margin:28px 0"><a href="{{ $trackingUrl }}" style="display:inline-block;background:#111;color:#fff;text-decoration:none;padding:15px 22px;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase">Track my order</a></p>
<p style="margin:0;color:#777;font-size:13px;line-height:1.6">Total: ₱{{ number_format((float) $order->total_amount, 2) }}<br>Payment status: {{ ucfirst($order->payment_status) }}</p>
<p style="margin:28px 0 0;padding-top:20px;border-top:1px solid #eee;color:#888;font-size:12px;line-height:1.6">This is a private link. Do not share it publicly. ALAS will never ask you to send your payment password or one-time PIN.</p></td></tr>
</table></td></tr></table>
</body></html>
