<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ __('Ilmiy-nazariy jurnal') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ __(':year «:name» ilmiy jurnali. Barcha huquqlar himoyalangan.', ['year' => date('Y'), 'name' => config('journal.name')]) }}
@if (config('journal.contact.email'))

[{{ config('journal.contact.email') }}](mailto:{{ config('journal.contact.email') }}) · [{{ parse_url((string) config('app.url'), PHP_URL_HOST) }}]({{ config('app.url') }})
@endif
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
