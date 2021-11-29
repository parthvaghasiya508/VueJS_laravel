@component('mail::layout')
{{-- Header --}}
@slot('header1')
@component('mail::header', ['url' => config('app.frontend_url')])
{{ config('app.name') }}
@endcomponent
@endslot
@slot('header')
  <br>
@endslot

@slot('image')
@component('mail::image', ['name' => 'cloud.png'])
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer1')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
@endcomponent
@endslot
@slot('footer')
  <br>
@endslot
@endcomponent
