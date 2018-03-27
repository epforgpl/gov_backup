@extends('app')

@section('web_object_header')
    <div class="web_object_header">
        <div class="image">
            @if ($object->getVersion()->getThumbUrl())
            <img src="{{ $object->getCurrentVersion()->getThumbUrl() }}" />
            @endif
        </div>
        <div class="content">
            <h1>{{ $object->getVersion()->getTitle() }}</h1>
            <p class="url">{{ $object->getWebUrl() }}</p>
            <!-- <p class="revision">{{ $object->getVersion()->getTimestamp()->format('Y-m-d H:i:s') }}</p>
            TODO bring it after all data are properly indexed
            TODO we might not want to show lastSeen here, but an actual timestamp
-->
        </div>
    </div>
@endsection

@section('content')
<iframe id="iframe" src="{{ $get_url }}"></iframe>
@endsection