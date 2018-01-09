<!--
This view relies on $_page variable which exists when using _view method of a controller.
In case this method wasn't used - $_page variable will be defined here with empty values.
-->
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>GovBackup</title>

    @section('styles')
        <link href="{{ asset('/css/app.css') }}" rel="stylesheet">
        @stack('styles')
    @show
</head>
<body>

<div id="page">

    <div id="header">
        <div class="navbar navbar-expand-lg">
            <div class="container-fluid">
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
                @yield('web_object_header')
            </div>
        </div>
    </div>
    <div id="content">
        @yield('content')
    </div>

</div>

@section('scripts')
    <script src="{{ asset('/js/app.js') }}"></script>
    @stack('scripts')
@show
</body>
</html>