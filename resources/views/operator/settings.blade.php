@extends('operator.layout')
@section('title', 'Settings')
@section('content')
<div class="head"><div><h1>Settings</h1><p class="muted">Your operator account.</p></div></div>

<div class="panel pad spaced">
    <h3>Operators</h3>
    <p class="muted small">Everyone listed here has full control of rooms and members. None of them can read chats.</p>
    <table class="table">
        <tbody>
        @foreach ($operators as $o)
            <tr>
                <td><b>{{ $o->name }}</b><br><span class="muted small">{{ $o->email }}</span></td>
                <td class="right">
                    @if ($o->id === auth()->id()) <span class="pill ok">You</span>
                    @else
                        <form method="post" action="{{ route('operator.operators.remove', $o) }}" data-confirm="Remove this operator?">@csrf @method('DELETE')<button class="btn danger sm" type="submit">Remove</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @error('operators')<p class="err">{{ $message }}</p>@enderror
    <form method="post" action="{{ route('operator.operators.add') }}" class="newroom addop">
        @csrf
        <input type="text" name="name" placeholder="Name" maxlength="80" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password (12+ characters)" minlength="12" autocomplete="new-password" required>
        <button class="btn primary" type="submit">Add operator</button>
    </form>
</div>

<div class="grid2">
    <div class="panel pad">
        <h3>Change password</h3>
        <form method="post" action="{{ route('operator.password') }}" class="stack">
            @csrf
            <label>Current password<input type="password" name="current" autocomplete="current-password" required></label>
            <label>New password (12+ characters)<input type="password" name="password" autocomplete="new-password" minlength="12" required></label>
            <label>Repeat new password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required></label>
            @foreach ($errors->all() as $e)<p class="err">{{ $e }}</p>@endforeach
            <button class="btn primary" type="submit">Update password</button>
        </form>
    </div>
    <div class="panel pad">
        <h3>What you can and cannot see</h3>
        <ul class="plain">
            <li>You <b>can</b> create rooms, disable them, issue a replacement Member ID, erase stored messages and delete rooms.</li>
            <li>You <b>cannot</b> read messages, nicknames or reactions. They are encrypted in the members' browsers with a secret phrase only they know.</li>
            <li>Chat IDs and Member IDs are stored as one-way hashes, so they cannot be shown again after creation.</li>
            <li>Members' support address: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>. Change it with <code>SUPPORT_EMAIL</code> in <code>.env</code>.</li>
        </ul>
    </div>
</div>
@endsection
