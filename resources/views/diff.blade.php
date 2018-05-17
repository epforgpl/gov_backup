@extends('layouts.base')

@section('styles')
    <link href="{{ asset('css/diff.css') }}" rel="stylesheet">
@endsection

@section('body')
    <pre>{!! $formatted_html !!}</pre>
@endsection