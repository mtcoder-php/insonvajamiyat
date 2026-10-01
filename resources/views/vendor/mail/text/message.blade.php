<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
INSON VA JAMIYAT — {{ __('Ilmiy-nazariy jurnal') }}
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
© {{ date('Y') }} «Inson va Jamiyat» {{ __('ilmiy jurnali. Barcha huquqlar himoyalangan.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
