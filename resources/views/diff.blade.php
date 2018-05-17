@extends('layouts.base')

@section('styles')
    <link href="{{ asset('css/diff.css') }}" rel="stylesheet">
@endsection

@section('body')
    <div style="margin-bottom: 40px;">
        You are comparing version from {{ $fromObject->getTimestamp()->format('Y-m-d H:i:s') }} (last time seen at [I might get it if needed] }})
        to version {{ $toObject->getTimestamp()->format('Y-m-d H:i:s') }} (first time seen at [I might get it if needed])
    </div>
    <pre>{!! $formattedHtml !!}</pre>
@endsection