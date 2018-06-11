@extends('layouts.app')

@section('body')
<div id="page">

    <div id="header">
        <div class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <!-- TODO correct the title -->
                <a href="/" class="navbar-brand">GovBackup / Kancelaria Prezesa Rady Ministrów</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarResponsive">

                    <!--
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <form action="/search">
                                <input type="text" name="q" />
                                <input style="display: none;" type="submit" />
                            </form>
                        </li>
                    </ul>
                    -->

                    <ul class="nav navbar-nav ml-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/about" target="_blank">About</a>
                        </li>
                    </ul>

                </div>
            </div>
        </div>
        <div id="web_object_header">
            <div class="container-fluid">
                <div class="web_object_header">
                    <div class="image">
                        @if ($object->getVersion()->getThumbUrl())
                            <img src="{{ $object->getVersion()->getThumbUrl() }}" />
                        @endif
                    </div>
                    <div class="content">
                        <h1>{{ $object->getVersion()->getTitle() }}</h1>
                        <p class="url">{{ $object->getWebUrl() }}</p>
                        <p class="revision">{{ $object->getTimestamp()->format('Y-m-d H:i:s') }}</p>

                        @if (config('app.debug'))<p class="object_id">Object ID: {{ $object->getId() }}</p>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="content">
        <iframe id="iframe" src="{{ $get_url }}"></iframe>
    </div>

</div>
@endsection