<x-mail::message>
# {{ __('auth.reset_title') }}

{{ __('auth.reset_greeting') }}

{{ __('auth.reset_code_intro') }}

<x-mail::panel>
<div style="text-align: center; font-size: 24px; letter-spacing: 5px;">
**{{ $code }}**
</div>
</x-mail::panel>

{{ __('auth.reset_footer') }}

Дякуємо,<br>
{{ config('app.name') }}
</x-mail::message>