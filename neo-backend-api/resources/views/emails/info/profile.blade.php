<div style="font-family: monospace">
ID: {{ $user->id }}<br>
Date: {{ $pd_filled_at->format('Y-m-d H:i:s') }}<br>
Registration was: {{ $created_at->fromNow() }}<br>
Email: {{ $user->email }}<br>
<br>
First name: {{ $user->profile->first_name }}<br>
Last name: {{ $user->profile->last_name }}<br>
Phone: {{ $user->profile->tel }}<br>
</div>
