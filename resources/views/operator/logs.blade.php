@extends('operator.layout')
@section('title', 'Activity')
@section('content')
<div class="head"><div><h1>Activity</h1><p class="muted">Operator actions only. No chat content is ever recorded here.</p></div></div>
<div class="panel">
    <table class="table">
        <thead><tr><th>When</th><th>Action</th><th>Room</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($logs as $l)
            <tr>
                <td>{{ $l->created_at->format('d M Y, H:i') }}</td>
                <td><code>{{ $l->action }}</code></td>
                <td>{{ $l->room_label ?: '—' }}</td>
                <td class="muted">{{ $l->ip }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">Nothing yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
