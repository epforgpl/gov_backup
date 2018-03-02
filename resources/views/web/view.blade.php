@extends('app')

@section('web_object_header')
    <div class="web_object_header">
        <div class="image">
            <img src="<?php echo $object->getCurrentVersion()->getThumbUrl() ?>" />
        </div>
        <div class="content">
            <h1><?php echo $object->getCurrentVersion()->getTitle() ?></h1>
            <p class="url"><?php echo $object->getWebUrl() ?></p>
            {{-- TODO bring it back <p class="revision">{{ $object->getCurrentRevision()->getTime() }}</p> --}}
        </div>
    </div>
@endsection

@section('content')
<iframe id="iframe" src="<?php echo $object->getUrl(); ?>"></iframe>
@endsection