@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row mt-5">
            <div class="col-md" style="text-align: center">
                <h2>Search in archived content</h2>
                <form>
                    <input type="submit"
                           style="position: absolute; left: -9999px; width: 1px; height: 1px;"
                           tabindex="-1"/>
                    <input name="search" type="text" placeholder="Enter some words" value="{{ $textQuery }}" style="width: 50%;"/>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md" style="text-align: center">
                <h3>Results</h3>
                @forelse ($textResults as $r)
                    <div class="result-item text">
                        <h4><a href="{{ @EpfHelpers::route_slashed('view', [
                            'url' => $r['url'],
                            # Linking to when this version was seen last time
                            'timestamp' => $r['last_seen']->format('YmdHis')])
                             }}">
                                    {!! $r['highlight']['data.web_objects_versions.title'][0] or $r['data']['web_objects_versions']['title'] !!}
                            </a>
                        </h4>

                        <div>Original link: <a href="{{-- TODO full original link needed --}}http://{{ $r['url'] }}">{!! $r['highlight']['data.web_objects_versions.url'][0] or $r['url'] !!}</a></div>

                        <div>Available versions: <a href="{{ @EpfHelpers::route_slashed('calendar', [
                            'url' => $r['url']
                            // ,'govbackup_query' => $textQuery // TODO filter revisions by query
                            ])
                             }}">
                                {{ $r['versions_count'] }}</a></div>
                        <div>This version first seen: {{ $r['first_seen']->format('Y-m-d H:i') }}</div>
                        <div>This version last seen: {{ $r['last_seen']->format('Y-m-d H:i') }}</div>

                        @if ($r['data']['web_objects_versions']['image_url'])
                            <img src="{{ $r['data']['web_objects_versions']['image_url'] }}"/>
                        @endif

                        <div>{{ $r['data']['web_objects_versions']['description'] }}</div>

                        @if(isset($r['highlight']['text']))
                        <div class="highlights">
                            @foreach($r['highlight']['text'] as $h)
                                <div>{!! $h !!}</div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                @empty
                    <div>No results!</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
