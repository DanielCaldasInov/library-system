<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5;">
<div style="max-width:600px; margin:0 auto; padding:24px;">
    <div style="background:#ffffff; border-radius:10px; padding:24px; font-family: Arial, Helvetica, sans-serif; color:#111827;">

        <h2 style="margin:0 0 16px 0;">Need help finishing your order?</h2>

        <p style="margin:0 0 12px 0;">
            Hello {{ $citizenName }},
        </p>

        <p style="margin:0 0 18px 0;">
            We noticed that you added books to your cart some time ago, but you haven’t completed the checkout yet.
            If you need any assistance, you can resume your purchase using the buttons below.
        </p>

        <div style="text-align:center; margin-top:18px;">
            <a
                href="{{ $cartUrl }}"
                style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:8px; font-weight:bold; margin-right:8px;"
            >
                Go to Cart
            </a>

            <a
                href="{{ $checkoutUrl }}"
                style="display:inline-block; background:#5754E8; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:8px; font-weight:bold;"
            >
                Continue checkout
            </a>
        </div>

        <p style="margin:24px 0 0 0; color:#6b7280; font-size:13px;">
            Thanks,<br>{{ $appName }}
        </p>
    </div>
</div>
</body>
</html>
