@extends('layouts.base')

@section('title', 'Lista wersji dla ' . $url . ' | Archiwum.io')

@section('content')

    @forelse ($revisions as $r)
        <ul>
            <li>{{ $r->timestamp->format('Y-m-d H:i:s') }}</li>
            <li>{{ $r->timestamp->format('Ymd') }}</li>
            <li>{{ $r->object_id }}</li>
            <li>{{ $r->version_id }}</li>
            <li><a href="{{ EpfHelpers::route_slashed('view', [
            'url' => $r->object_url,
            'timestamp' => $r->timestamp->format('YmdHis')]) }}">View</a></li>
        </ul>
    @empty
        <div>No results!</div>
    @endforelse

@endsection
