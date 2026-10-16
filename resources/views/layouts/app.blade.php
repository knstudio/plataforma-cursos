@extends('layouts.base')

@section('body')
    <div class="preloader">
        <div class="sk-chase">
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
            <div class="sk-chase-dot"></div>
        </div>
    </div>

    <div class="mdk-header-layout js-mdk-header-layout">

        <div id="header" class="mdk-header bg-dark js-mdk-header m-0" data-fixed data-effects="waterfall">
            <div class="mdk-header__content">
                @include('partials.navbar')
            </div>
        </div>

        <div class="mdk-header-layout__content d-flex flex-column">
            <div class="page">
                <div class="container-fluid page__container">
                    @hasSection('breadcrumb')
                        <ol class="breadcrumb">
                            @yield('breadcrumb')
                        </ol>
                    @endif

                    @include('partials.flash')

                    @yield('content')
                </div>

                <div class="container-fluid page__container">
                    <div class="footer">
                        Copyright &copy; {{ date('Y') }} - {{ config('app.name') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mdk-drawer js-mdk-drawer" id="default-drawer">
        <div class="mdk-drawer__content">
            <div class="sidebar sidebar-left sidebar-dark bg-dark o-hidden" data-perfect-scrollbar>
                @if (request()->routeIs('admin.*'))
                    @include('partials.sidebar-admin')
                @else
                    @include('partials.sidebar-student')
                @endif
            </div>
        </div>
    </div>
@endsection
