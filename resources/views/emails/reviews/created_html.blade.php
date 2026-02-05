@php
    $cover = null;

    if (!empty($bookCoverUrl)) {
        $cover = str_starts_with($bookCoverUrl, 'http')
            ? $bookCoverUrl
            : url($bookCoverUrl);
    }

    $avatar = null;

    if (!empty($citizenPhotoUrl)) {
        $avatar = str_starts_with($citizenPhotoUrl, 'http')
            ? $citizenPhotoUrl
            : url($citizenPhotoUrl);
    }
@endphp

    <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5;">
<div style="max-width:600px; margin:0 auto; padding:24px;">
    <div style="background:#ffffff; border-radius:10px; padding:24px; font-family: Arial, Helvetica, sans-serif; color:#111827;">

        <h2 style="margin:0 0 16px 0;">
            New review submitted
        </h2>

        <div style="display:flex; align-items:center; gap:12px; margin:0 0 16px 0;">
            @if($avatar)
                <img
                    src="{{ $avatar }}"
                    alt="Citizen photo"
                    style="width:44px; height:44px; border-radius:999px; object-fit:cover; display:block; background:#e5e7eb;"
                >
            @else
                <div style="width:44px; height:44px; border-radius:999px; background:#e5e7eb;"></div>
            @endif

            <div>
                <p style="margin:0; font-weight:700;">
                    {{ $citizenName }}
                </p>
                <p style="margin:4px 0 0 0; color:#6b7280; font-size:13px;">
                    {{ $citizenEmail }}
                </p>
            </div>
        </div>

        <p style="margin:0 0 10px 0;">
            <strong>Book:</strong> {{ $bookName }}
        </p>

        <p style="margin:0 0 10px 0;">
            <strong>Request:</strong> #{{ $requestNumber }}
        </p>

        <p style="margin:0 0 10px 0;">
            <strong>Request status:</strong> {{ $requestStatus }}
        </p>

        <p style="margin:0 0 10px 0;">
            <strong>Review status:</strong> {{ $reviewStatus }}
        </p>

        <p style="margin:0 0 18px 0;">
            <strong>Rating:</strong> {{ $rating ?? '—' }}/5
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

        <div style="background:#f3f4f6; border-radius:10px; padding:14px; margin:0 0 18px 0;">
            <p style="margin:0 0 8px 0; color:#6b7280; font-size:13px;">
                <strong>Citizen comment</strong>
            </p>
            <p style="margin:0; white-space:pre-line;">
                {{ $comment ?: '—' }}
            </p>
        </div>

        <div style="text-align:center; margin-top:18px;">
            <a
                href="{{ $reviewUrl }}"
                style="display:inline-block; background:#111827; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:8px; font-weight:bold;"
            >
                View review
            </a>
        </div>

        <p style="margin:24px 0 0 0; color:#6b7280; font-size:13px;">
            Thanks,<br>{{ config('app.name') }}
        </p>

    </div>
</div>
</body>
</html>
