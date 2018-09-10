@extends('layouts.base')

@section('title', 'Ostatnio usunięte strony | Archiwum.io')

@section('styles')
@endsection

@section('content')
    <div class="container">
        <div id="deleted-pages" class="row">
            <div class="col-md-8 offset-md-2 text-center">

                <h1>Ostatnio usunięte strony</h1>
                <ul>
                    @foreach($pages as $page)
                        <li><a href="{{ $page['url'] }}">{{ $page['url'] }}</a><br/>
                            missing ({{ $page['missing_code'] }}) on {{ $page['missing_cts'] }}<br/>
                            last seen ({{ $page['ok_code'] }}) on <a href="{{ $page['last_seen_link'] }}">{{ $page['ok_cts'] }}</a>
                        </li>
                    @endforeach
                </ul>

            </div>
        </div>
    </div>
@endsection
