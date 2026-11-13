@extends('operator.layout')
@section('title', 'Sign in')
@section('content')
<section class="login">
    <div class="panel narrow">
        <div class="brand big">@include('partials.brand', ['u' => 'l'])<span class="tag">Console</span></div>
        <h1>Operator sign in</h1>
        <p class="muted">You can see usage statistics and block or delete rooms here. You can never read a conversation.</p>
        <form method="post" action="{{ route('operator.login') }}" class="stack">
            @csrf
            <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
            <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
            @error('email')<p class="err">{{ $message }}</p>@enderror
            <button class="btn primary block" type="submit">Sign in</button>
        </form>
    </div>
</section>
@endsection
