@extends('app')

@section('web_object_header')
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
        </div>
    </div>
@endsection

@section('content')
<iframe id="iframe" src="{{ $get_url }}"></iframe>
@endsection