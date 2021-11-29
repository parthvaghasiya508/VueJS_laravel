<div style="font-family: monospace">
ID: {{ $user->id }}<br>
Date: {{ $cd_filled_at->format('Y-m-d H:i:s') }}<br>
Personal info filled: {{ $pd_filled_at->fromNow() }}<br>
Registration was: {{ $created_at->fromNow() }}<br>
Email: {{ $user->email }}<br>
Name: {{ $user->fullName }}<br>
<br>
@if($skip)
Hotel registration skipped
@else
Hotel ID: {{ $hotel->id }}<br>
Hotel name: {{ $hotel->name }}<br>
ZIP: {{ $zip }}<br>
Country: {{ $country['name'] }} ({{ $country['code'] }})<br>
City: {{ $city }}<br>
Street: {{ $street }}<br>
Phone: {{ $tel }}<br>
@endif
</div>
