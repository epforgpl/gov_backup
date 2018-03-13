@extends('layouts.app')

@section('content')

    @forelse ($revisions as $r)
        <ul>
            <li>{{ $r->timestamp->format('Y-m-d H:i:s') }}</li>
            <li>{{ $r->timestamp->format('Ymd') }}</li>
            <li>{{ $r->object_id }}</li>
            <li>{{ $r->version_id }}</li>
            <li>{{ $r->getRewrittenUrl() }}</li>
        </ul>
    @empty
        <div>No results!</div>
    @endforelse
    </ul>

@endsection