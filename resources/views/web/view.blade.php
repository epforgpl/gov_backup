@extends('app')

@section('content')
<iframe id="iframe" src="<?php echo $object->getUrl(); ?>"></iframe>
@endsection