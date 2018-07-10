@extends('layouts.base')

@section('title', 'Archiwum.io')

@section('styles')
    <link href="{{ asset('css/home.css') }}" rel="stylesheet">
@show

@section('content')
    <div class="container">
        <div id="greeting" class="row">
            <div class="col-md-8 offset-md-2 text-center">
                @if (!$textResults)
                    <a href="/"><img src="{{ asset('img/logo.svg') }}" /></a>
                    <h2 class="mt-4">Witaj w Archiwum!</h2>
                    <p class="mt-3">Możesz tutaj przeglądać archiwalne wersje stron rządowych oraz szukać usuniętych z nich treści.</p>
                @endif
                <form>
                    <fieldset>
                        <div class="form-group form-group-lg">

                            <div class="input-group mb-3">
                                <input class="form-control form-control-lg input-main" name="search" type="text" placeholder="Szukaj na stronach rządowych..." value="{{ $textQuery }}" />

                                <div class="input-group-append">
                                    <input type="submit" class="btn btn-primary button-main" value="Szukaj" />                                </div>
                            </div>

                        </div>
                        <div class="form-group mt-3">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="in_deleted" class="custom-control-input" id="onlyDeletedCheckbox" onChange="this.form.submit()" @if($inDeleted)checked @endif>
                                <label class="custom-control-label" for="onlyDeletedCheckbox">Szukaj tylko w treściach usuniętych</label>
                            </div>
                        </div>

                    </fieldset>
                </form>
            </div>
        </div>

        @if ($textResults)
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="result-items">
                        @forelse ($textResults as $r)
                            <div class="result-item text">
                                <p class="title">
                                    <a href="{{ $r['link'] }}">
                                        {!! $r['highlight']['data.web_objects_versions.title'][0] or $r['data']['web_objects_versions']['title'] !!}
                                    </a>
                                </p>

                                <p class="link">
                                    <a href="{{-- TODO full original link needed --}}http://{{ $r['url'] }}">{!! $r['highlight']['data.web_objects_versions.url'][0] or $r['url'] !!}</a>
                                </p>

                                @if(isset($r['highlight']['text']))
                                    <div class="highlights">
                                        @foreach($r['highlight']['text'] as $h)
                                            <div>{!! $h !!}</div>
                                        @endforeach
                                    </div>
                                @endif

                                <?php /*
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


 */ ?>
                            </div>
                        @empty
                            <div>No results!</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
