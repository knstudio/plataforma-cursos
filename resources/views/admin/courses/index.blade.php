@extends('layouts.app')

@section('title', 'Cursos')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active">Cursos</li>
@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row flex-wrap mb-headings align-items-start align-items-sm-center">
        <div class="flex mb-2 mb-sm-0">
            <h1 class="h2">Cursos</h1>
        </div>
        <a href="{{ route('admin.courses.create') }}" class="btn btn-success">Novo curso</a>
    </div>

    <div class="card card-body border-left-3 border-left-primary navbar-shadow mb-4">
        <form method="GET" action="{{ route('admin.courses.index') }}" class="d-flex align-items-center">
            <div class="flex search-form search-form--light">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Buscar curso">
                <button class="btn" type="submit"><i class="material-icons">search</i></button>
            </div>
        </form>
    </div>

    @if ($courses->isEmpty())
        <div class="alert alert-light border-1 border-left-3 border-left-warning" role="alert">
            <div class="text-black-70">Nenhum curso encontrado.</div>
        </div>
    @endif

    <div class="row">
        @foreach ($courses as $course)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-sm-row">
                            <a href="{{ route('admin.courses.edit', $course) }}" class="avatar avatar-lg avatar-4by3 mb-3 w-xs-plus-down-100 mr-sm-3">
                                <img src="{{ $course->coverUrl() }}" alt="{{ $course->title }}" class="avatar-img rounded">
                            </a>
                            <div class="flex" style="min-width: 200px;">
                                <h4 class="card-title mb-1">
                                    <a href="{{ route('admin.courses.edit', $course) }}">{{ $course->title }}</a>
                                </h4>
                                <p class="text-black-70 mb-2">{{ $course->short_description }}</p>
                                <div class="d-flex align-items-end">
                                    <div class="d-flex flex flex-column mr-3">
                                        <div class="d-flex align-items-center py-1 border-bottom">
                                            <small class="text-black-70 mr-2">{{ $course->lessons_count }} AULAS</small>
                                            <small class="text-black-50">{{ $course->active_enrollments_count }} ALUNOS</small>
                                        </div>
                                        <div class="d-flex align-items-center py-1">
                                            @if ($course->is_published)
                                                <span class="badge badge-success">Publicado</span>
                                            @else
                                                <span class="badge badge-light">Rascunho</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-white">Editar</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{ $courses->links() }}
@endsection
