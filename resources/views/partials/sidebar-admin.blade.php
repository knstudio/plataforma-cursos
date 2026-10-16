<div class="sidebar-p-y">
    <div class="sidebar-heading">Administração</div>
    <ul class="sidebar-menu sm-active-button-bg">
        <li class="sidebar-menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('admin.dashboard') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">dashboard</i> Painel
            </a>
        </li>
        <li class="sidebar-menu-item {{ request()->routeIs('admin.courses.*', 'admin.lessons.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('admin.courses.index') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">school</i> Cursos
            </a>
        </li>
        <li class="sidebar-menu-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('admin.products.index') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">local_offer</i> Produtos
            </a>
        </li>
        <li class="sidebar-menu-item {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('admin.students.index') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">people</i> Alunos
            </a>
        </li>
        <li class="sidebar-menu-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('admin.orders.index') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">receipt</i> Pedidos
            </a>
        </li>
        @php($pendingQuestions = \App\Models\LessonQuestion::query()->unanswered()->count())
        <li class="sidebar-menu-item {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('admin.questions.index') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">forum</i> Perguntas
                @if ($pendingQuestions > 0)
                    <span class="badge badge-danger ml-auto">{{ $pendingQuestions }}</span>
                @endif
            </a>
        </li>
        <li class="sidebar-menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <a class="sidebar-menu-button" href="{{ route('admin.settings.edit') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">settings</i> Configurações
            </a>
        </li>
    </ul>

    <div class="sidebar-heading">Plataforma</div>
    <ul class="sidebar-menu sm-active-button-bg">
        <li class="sidebar-menu-item">
            <a class="sidebar-menu-button" href="{{ route('dashboard') }}">
                <i class="sidebar-menu-icon sidebar-menu-icon--left material-icons">account_box</i> Área do aluno
            </a>
        </li>
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
