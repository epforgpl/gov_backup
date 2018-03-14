@extends('app')

@section('web_object_header')
    <div class="web_object_header">
        <div class="image">
            @if ($object->getCurrentVersion()->getThumbUrl())
            <img src="{{ $object->getCurrentVersion()->getThumbUrl() }}" />
            @endif
        </div>
        <div class="content">
            <h1>{{ $object->getCurrentVersion()->getTitle() }}</h1>
            <p class="url">{{ $object->getWebUrl() }}</p>
            <p class="revision">{{ $object->getLastSeen()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
@endsection

@section('content')
<iframe id="iframe" src="{{ $object->getUrl() }}"></iframe>
@endsection