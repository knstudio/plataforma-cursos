@extends('layouts.app')

@section('title', 'Nova aula')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">Cursos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.courses.edit', $course) }}">{{ $course->title }}</a></li>
    <li class="breadcrumb-item active">Nova aula</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.lessons.store', $course) }}">
        @csrf

        <div class="media align-items-center mb-headings">
            <div class="media-body">
                <h1 class="h2">Nova aula</h1>
            </div>
            <div class="media-right">
                <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-white mr-2">Cancelar</a>
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                @include('admin.lessons._form')
            </div>
            <div class="col-lg-4">
                <div class="card card-body text-muted">
                    Os materiais para download (PDF, apostilas...) podem ser anexados depois de salvar a aula.
                </div>
            </div>
        </div>
    </form>
@endsection
