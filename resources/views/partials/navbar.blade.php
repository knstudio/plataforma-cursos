<nav id="default-navbar" class="navbar navbar-expand navbar-dark bg-primary m-0">
    <div class="container-fluid">
        <button class="navbar-toggler d-block" data-toggle="sidebar" type="button">
            <span class="material-icons">menu</span>
        </button>

        <a href="{{ request()->routeIs('admin.*') ? route('admin.dashboard') : route('dashboard') }}" class="navbar-brand">
            <img src="{{ asset('assets/images/logo/balloon.png') }}" class="mr-2" alt="{{ config('app.name') }}" height="32">
            <span class="d-none d-xs-md-block">{{ config('app.name') }}</span>
        </a>

        <div class="flex"></div>

        <ul class="nav navbar-nav flex-nowrap">
            @if (auth()->user()->isAdmin())
                <li class="nav-item d-none d-md-flex">
                    @if (request()->routeIs('admin.*'))
                        <a class="nav-link" href="{{ route('dashboard') }}">Área do aluno</a>
                    @else
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">Administração</a>
                    @endif
                </li>
            @endif

            <li class="nav-item dropdown ml-1 ml-md-3">
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button">
                    <span class="text-white">Olá, {{ Str::of(auth()->user()->name)->before(' ')->ucfirst() }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="{{ route('account.edit') }}">
                        <i class="material-icons">edit</i> Minha conta
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="material-icons">lock</i> Sair
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</nav>
