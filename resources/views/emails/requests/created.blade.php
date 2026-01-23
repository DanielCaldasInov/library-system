@php
    $cover = $coverUrl ?? null;
@endphp

<x-mail::message>
    # {{ $audience === 'admin' ? 'New request created' : 'Request confirmed' }}

    **Request:** #{{ $number }}
    **Book:** {{ $bookName }}
    **Requested at:** {{ $requestedAt }}
    **Due at:** {{ $dueAt }}
    **Status:** {{ $status }}

    @if($cover)
        <x-mail::panel>
            <img
                src="{{ $cover }}"
                alt="Book cover"
                style="max-width:180px; border-radius:10px; display:block; margin: 0 auto;"
            >
        </x-mail::panel>
    @endif

    <x-mail::button :url="$requestUrl">
        View request
    </x-mail::button>

    Thanks,
    {{ config('app.name') }}
</x-mail::message>
