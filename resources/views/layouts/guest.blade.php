@extends('layouts.base')

@section('body-class', 'login')

@section('body')
    <div class="d-flex align-items-center" style="min-height: 100vh">
        <div class="col-sm-8 col-md-6 col-lg-4 mx-auto" style="min-width: 300px;">
            <div class="text-center mt-5 mb-1">
                <div class="avatar avatar-lg">
                    <img src="{{ asset('assets/images/logo/balloon.png') }}" class="avatar-img rounded-circle" alt="{{ config('app.name') }}">
                </div>
            </div>
            <div class="d-flex justify-content-center mb-5 navbar-light">
                <a href="{{ route('login') }}" class="navbar-brand m-0">{{ config('app.name') }}</a>
            </div>

            @include('partials.flash')

            @yield('content')
        </div>
    </div>
@endsection
