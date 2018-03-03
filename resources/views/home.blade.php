@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row mt-5">
            <div class="col-md-5 offset-md-1" style="text-align: center">
                <h2>Browse domains</h2>
                <form>
                    <input type="submit"
                           style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                           tabindex="-1"/>
                    <input name="url" type="text" placeholder="Enter a URL or words related to a site’s home page"
                           value="" style="width: 50%;"/>
                </form>
            </div>
            <div class="col-md-5 offset-md-1" style="text-align: center">
                <h2>Search in archived content</h2>
                <form>
                    <input type="submit"
                           style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                           tabindex="-1"/>
                    <input name="search" type="text" placeholder="Enter some words" value="" style="width: 50%;"/>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md" style="text-align: center">
                <h3>Results</h3>
                @if ($textResults !== null)
                @forelse ($textResults as $r)
                    <div class="result-item text">
                        <h4><a href="{{ @route('view', ['url' => $r['url']]) }}">
                                {!! $r['highlight']['data.web_objects_versions.title'][0] or $r['data']['web_objects_versions']['title'] !!}
                            </a></h4>
                        <div><a href="{{-- TODO full original link needed --}}http://{{ $r['url'] }}">Link to original</a></div>
                        <div>Available versions: {{ $r['versions_count'] }}</div>
                        <div>First seen: {{ $r['first_seen_ms'] }}, Last seen: {{ $r['last_seen_ms'] }}</div>
                        @if ($r['data']['web_objects_versions']['image_url'])
                            <img src="{{ $r['data']['web_objects_versions']['image_url'] }}"/>
                        @endif
                        <div>{{ $r['data']['web_objects_versions']['description'] }}</div>
                        <div class="highlights">
                            @foreach($r['highlight']['text'] as $h)
                                <div>{!! $h !!}</div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div>No results!</div>
                @endforelse
                @endif

                @if ($urlResults !== null)
                @forelse ($urlResults as $r)
                    <div class="result-item url">
                        <h4><a href="{{ @route('view', ['url' => $r['url']]) }}">
                                {!! $r['highlight']['data.web_objects_versions.title'][0] or $r['data']['web_objects_versions']['title'] !!}
                            </a></h4>
                        <div><a href="{{-- TODO full original link needed --}}http://{{ $r['url'] }}">
                                {!! $r['highlight']['data.web_objects.url'][0] or $r['data']['web_objects_versions']['title'] !!}
                            </a></div>
                        @if ($r['data']['web_objects_versions']['image_url'])
                            <img src="{{ $r['data']['web_objects_versions']['image_url'] }}"/>
                        @endif
                        <div>{{ $r['data']['web_objects_versions']['description'] }}</div>
                    </div>
                @empty
                    <div>No results!</div>
                @endforelse
                @endif
            </div>
        </div>
    </div>
@endsection
