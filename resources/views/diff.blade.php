@extends('layouts.base')

@section('styles')
    <link href="{{ asset('css/diff.css') }}" rel="stylesheet">
@endsection

@section('body')
    {{ $identical_after_format }}
    <pre>{!! $formatted_html !!}</pre>
@endsection