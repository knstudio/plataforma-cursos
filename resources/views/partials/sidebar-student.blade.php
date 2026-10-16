<div class="sidebar-p-y">
    <div class="sidebar-heading">Aluno</div>
    <ul class="sidebar-menu sm-active-button-bg">
        <li class="sidebar-menu-item {{ request()->routeIs('dashboard', 'courses.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('dashboard') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">school</i> Meus cursos
            </a>
        </li>
        <li class="sidebar-menu-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('account.edit') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">person_outline</i> Minha conta
            </a>
        </li>
        @if (auth()->user()->isAdmin())
            <li class="sidebar-menu-item">
                <a class="sidebar-menu-button" href="{{ route('admin.dashboard') }}">
                    <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">settings</i> Administração
                </a>
            </li>
        @endif
        <li class="sidebar-menu-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-menu-button btn-link border-0 w-100 text-left">
                    <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">lock_open</i> Sair
                </button>
            </form>
        </li>
    </ul>
</div>
