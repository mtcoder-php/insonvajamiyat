{{-- Bildirishnoma xati (o'zbekcha). Asos: Laravel notifications::email --}}
<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
# {{ $level === 'error' ? __('Xatolik yuz berdi') : __('Assalomu alaykum!') }}
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{!! nl2br(e($salutation)) !!}
@else
{{ __('Hurmat bilan,') }}<br>
{{ __('«:name» tahririyati', ['name' => config('journal.name')]) }}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
{{ __('Agar ":actionText" tugmasi ishlamasa, quyidagi havolani nusxalab brauzerga joylashtiring:', ['actionText' => $actionText]) }}
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
