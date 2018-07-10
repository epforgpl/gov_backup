@extends('layouts.base')

@section('title', 'Porównanie wersji: ' . $fromObject->getTimestamp()->format('Y-m-d H:i:s')
    . ' - ' . $toObject->getTimestamp()->format('Y-m-d H:i:s') . ' | Archiwum.io')

@section('styles')
    <link href="{{ asset('css/diff.css') }}" rel="stylesheet">
@endsection

@section('body')
    <div style="margin-bottom: 40px;">
        You are comparing version from {{ $fromObject->getTimestamp()->format('Y-m-d H:i:s') }} (last time seen at [I might get it if needed] }})
        to version {{ $toObject->getTimestamp()->format('Y-m-d H:i:s') }} (first time seen at [I might get it if needed])
    </div>
    @if ($diffType == 'text')
        <p>
            {!! $diff !!}
        </p>

    @elseif ($diffType == 'html' || $diffType == 'html-formatted')
        <pre>{!! $diff !!}</pre>html\\-rendered

    @elseif ($diffType == 'html-rendered')
        <div style="width: 100%; height: 100vh;">
            <iframe id="iframe" sandbox srcdoc="{{ $diff }}"></iframe>
        </div>
    @else
        What do you want to show here?
    @endif
@endsection
