@extends('layouts.app')

@section('title', 'Alunos')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active">Alunos</li>
@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row flex-wrap mb-headings align-items-start align-items-sm-center">
        <div class="flex mb-2 mb-sm-0">
            <h1 class="h2">Alunos</h1>
        </div>
        <a href="{{ route('admin.students.create') }}" class="btn btn-success">Novo aluno</a>
    </div>

    <div class="card card-body border-left-3 border-left-primary navbar-shadow mb-4">
        <form method="GET" action="{{ route('admin.students.index') }}" class="d-flex align-items-center">
            <div class="flex search-form search-form--light">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Buscar por nome, e-mail ou telefone">
                <button class="btn" type="submit"><i class="material-icons">search</i></button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-nowrap m-0">
                <thead class="thead-light">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th class="text-center">Cursos ativos</th>
                        <th>Cadastro</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>
                                <a href="{{ route('admin.students.show', $student) }}"><strong>{{ $student->name }}</strong></a>
                                @if ($student->isAdmin())
                                    <span class="badge badge-primary ml-1">Admin</span>
                                @endif
                            </td>
                            <td>{{ $student->email }}</td>
                            <td>{{ $student->phone ?? '—' }}</td>
                            <td class="text-center">{{ $student->active_enrollments_count }}</td>
                            <td>{{ $student->created_at->format('d/m/Y') }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-white btn-sm"><i class="material-icons">edit</i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum aluno encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $students->links() }}
@endsection
