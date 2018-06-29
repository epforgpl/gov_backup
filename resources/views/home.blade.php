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
                    <br/>
                    Search deleted phrases <input name="in_deleted" type="checkbox" title="Click to search in deleted phrases"
                                                  onChange="this.form.submit()" @if($inDeleted)checked @endif>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md" style="text-align: center">
                <h3>Results</h3>
                @forelse ($textResults as $r)
                    <div class="result-item text">
                        <h4><a href="{{ $r['link'] }}">
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

                        @if ($inDeleted)
                            <div style="font-weight: bold">"{{ $textQuery }}" was deleted between {{ $r['matching_last_seen']->format('Y-m-d H:i') }}
                                and {{ $r['not_matching_first_seen']->format('Y-m-d H:i') }}.</div>
                        @else
                        <div>This version first seen: {{ $r['first_seen']->format('Y-m-d H:i') }}</div>
                        <div>This version last seen: {{ $r['last_seen']->format('Y-m-d H:i') }}</div>
                        @endif

                        {{-- TODO image_id now stores thumbnail (if available), not image_url
                        @if ($r['data']['web_objects_versions']['image_id'] ?? null)
                            <img src="{{ $r['data']['web_objects_versions']['image_url'] }}"/>
                        @endif
                        --}}

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
