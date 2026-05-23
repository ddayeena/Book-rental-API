<x-mail::message>
# {{ __('auth.verify_title') }}

{{ __('auth.verify_greeting') }}

{{ __('auth.verify_code_intro') }}

<x-mail::panel>
<div style="text-align: center; font-size: 24px; letter-spacing: 5px;">
**{{ $code }}**
</div>
</x-mail::panel>

{{ __('auth.verify_footer') }}

Дякуємо,<br>
{{ config('app.name') }}
</x-mail::message>