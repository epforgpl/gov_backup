@extends('layouts.app')

@section('content')

    @forelse ($revisions as $r)
        <ul>
            <li>{{ $r->timestamp }}</li>
            <li>{{ $r->object_id }}</li>
            <li>{{ $r->version_id }}</li>
            <li>{{ $r->rewritten_url }}</li>
        </ul>
    @empty
        <div>No results!</div>
    @endforelse
    </ul>

@endsection