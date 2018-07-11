<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @section('styles')
        @stack('styles')
    @show
</head>
<body>
    <nav id="nav-main" class="navbar navbar-expand-lg fixed-top navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" title="{{ config('app.name', 'Laravel') }}" href="{{ url('/') }}">
                <img src="{{ asset('img/logo.svg') }}" />
            </a>

            @if (isset($object))
                <div class="page-header">
                    <div class="result-item">
                        <p class="title">
                            <a href="{{ $object->getWebUrl() }}">{{ $object->getVersion()->getTitle() }}</a>
                        </p>
                        <p class="link">
                            <a href="{{ $object->getWebUrl() }}" target="_blank">{{ $object->getWebUrl() }}</a>
                        </p>
                        <p class="revision">Wersja z {{ $object->getTimestamp()->format('Y-m-d H:i:s') }}</p>
                    </div>
                </div>
            @endif

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                <ul class="navbar-nav">
                    @if (Auth::guest())
                        <li class="nav-item"><a href="{{ route('login') }}" class="nav-link">Zaloguj</a></li>
                        <li class="nav-item"><a href="{{ route('register') }}" class="nav-link">Zarejestruj się</a></li>
                    @else
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" id="navbarDropdownMenuLink" data-toggle="dropdown"
                               aria-haspopup="true" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                                <a href="{{ route('logout') }}" class="dropdown-item"
                                   onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                    Logout
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    {{ csrf_field() }}
                                </form>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <div id="app">
        @yield('content')
    </div>

    <footer id="footer" class="bg-light fixed-bottom">
        <ul>
            <li><a href="{{ route('about') }}">O portalu</a></li>
            <li><a href="{{ route('personal') }}">Dane osobowe</a></li>
            <li><a href="{{ route('terms') }}">Regulamin</a></li>
            <li><a href="{{ route('privacy') }}">Polityka prywatności</a></li>
        </ul>
        <cookie-law button-text="OK">
            <div slot="message">
                Informujemy, że nasz serwis internetowy wykorzystuje pliki cookies. Celem przetwarzania
                danych zapisanych za pomocą cookies jest dostosowanie zawartości serwisu do preferencji
                Użytkownika. Jeśli nie wyrażasz zgody, ustawienia dotyczące plików cookies możesz zmienić
                w ustawieniach swojej przeglądarki. Więcej informacji na temat cookies znajdziesz
                w <a href="{{ route('privacy') }}">Polityce Prywatności</a>.
            </div>
        </cookie-law>
    </footer>

    @section('scripts')
        <script src="{{ asset('js/app.js') }}" type="text/javascript"></script>
        @stack('scripts')
    @show
</body>
</html>
