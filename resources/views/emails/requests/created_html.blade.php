@php
    // Normaliza a URL da capa:
    // - se já for http/https, usa direto
    // - se for caminho relativo, concatena com a URL do site
    $cover = null;

    if (!empty($coverUrl)) {
        $cover = str_starts_with($coverUrl, 'http')
            ? $coverUrl
            : url($coverUrl);
    }
@endphp

    <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background:#f5f5f5;">
<div style="max-width:600px; margin:0 auto; padding:24px;">
    <div style="background:#ffffff; border-radius:10px; padding:24px; font-family: Arial, Helvetica, sans-serif; color:#111827;">

        <h2 style="margin:0 0 16px 0;">
            {{ $audience === 'admin' ? 'New request created' : 'Request confirmed' }}
        </h2>

        <p style="margin:0 0 10px 0;">
            <strong>Request:</strong> #{{ $number }}
        </p>

        <p style="margin:0 0 10px 0;">
            <strong>Book:</strong> {{ $bookName }}
        </p>

        <p style="margin:0 0 10px 0;">
            <strong>Requested at:</strong> {{ $requestedAt }}
        </p>

        <p style="margin:0 0 10px 0;">
            <strong>Due at:</strong> {{ $dueAt }}
        </p>

        <p style="margin:0 0 18px 0;">
            <strong>Status:</strong> {{ $status }}
        </p>

        @if($cover)
            <div style="text-align:center; margin:18px 0 22px;">
                <img
                    src="{{ $cover }}"
                    alt="Book cover"
                    style="width:180px; height:auto; border-radius:12px; display:inline-block;"
                >
            </div>
        @endif

        <div style="text-align:center; margin-top:18px;">
            <a
                href="{{ $requestUrl }}"
                style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:8px; font-weight:bold;"
            >
                View request
            </a>
        </div>

        <p style="margin:24px 0 0 0; color:#6b7280; font-size:13px;">
            Thanks,<br>{{ config('app.name') }}
        </p>

    </div>
</div>
</body>
</html>
