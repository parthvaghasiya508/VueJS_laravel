<div style="font-family: monospace">
ID: {{ $user->id }}<br>
Date: {{ $user->setup_at->format('Y-m-d H:i:s') }}<br>
Hotel created: {{ $user->cd_filled_at->fromNow() }}<br>
Personal info filled: {{ $user->pd_filled_at->fromNow() }}<br>
Registration was: {{ $user->created_at->fromNow() }}<br>
Email: {{ $user->email }}<br>
@if($state==='complete')
<br>
Room Types: {{ $types }}<br>
Rate Plans: {{ $plans }}<br>
Photos: {{ $photos }}<br>
Currency: {{ $currency }}<br>
@endif
</div>
