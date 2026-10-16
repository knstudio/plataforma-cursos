<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@hasSection('title')@yield('title') - @endif{{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css?family=Oswald:400,500,700%7CRoboto:400,500%7CRoboto:400,500&amp;display=swap" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/vendor/perfect-scrollbar.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/css/material-icons.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/css/fontawesome.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/vendor/spinkit.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="@yield('body-class', 'layout-fluid')">

    @yield('body')

    <script src="{{ asset('assets/vendor/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/popper.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/dom-factory.js') }}"></script>
    <script src="{{ asset('assets/vendor/material-design-kit.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
