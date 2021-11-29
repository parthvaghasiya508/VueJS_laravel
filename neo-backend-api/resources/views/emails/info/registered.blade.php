<div style="font-family: monospace">
ID: {{ $id }}<br>
Date: {{ $date }}<br>
Email: {{ $email }}<br>
<a href="{{ $url }}">Verification link</a><br>
<br>
Verification link is valid for {{ config('auth.verification.expire', 60) }} minutes.
</div>
