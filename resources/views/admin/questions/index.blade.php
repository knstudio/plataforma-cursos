@extends('layouts.app')

@section('title', 'Perguntas dos alunos')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active">Perguntas</li>
@endsection

@section('content')
    <h1 class="h2">Perguntas dos alunos</h1>

    <div class="card card-body border-left-3 border-left-primary navbar-shadow mb-4">
        <form method="GET" action="{{ route('admin.questions.index') }}" class="form-row align-items-center">
            <div class="col-md-4 mb-2 mb-md-0">
                <select name="status" class="form-control custom-select">
                    @foreach ($filters as $value => $label)
                        <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-2 mb-md-0">
                <select name="curso" class="form-control custom-select">
                    <option value="">Todos os cursos</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected($courseId === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="card">
        <ul class="list-group list-group-fit">
            @forelse ($questions as $question)
                <li class="list-group-item">
                    <div class="d-flex">
                        @include('student._avatar', ['user' => $question->user, 'size' => 'avatar-sm'])
                        <div class="flex" style="min-width: 0">
                            <p class="d-flex align-items-center flex-wrap mb-1">
                                <strong class="mr-2">{{ $question->user->name }}</strong>
                                <small class="text-muted mr-2">{{ $question->created_at->diffForHumans() }}</small>
                                @if ($question->is_hidden)
                                    <span class="badge badge-warning">Oculta</span>
                                @elseif ($question->isAnswered())
                                    <span class="badge badge-success">Respondida</span>
                                @else
                                    <span class="badge badge-danger">Sem resposta</span>
                                @endif
                            </p>
                            <a href="{{ route('admin.questions.show', $question) }}" class="text-body d-block mb-1">
                                {{ str($question->body)->limit(180) }}
                            </a>
                            <small class="text-muted">
                                {{ $question->lesson->module->course->title }} › {{ $question->lesson->title }}
                            </small>
                        </div>
                        <div class="ml-3">
                            <a href="{{ route('admin.questions.show', $question) }}" class="btn btn-sm {{ $question->isAnswered() ? 'btn-light' : 'btn-primary' }}">
                                {{ $question->isAnswered() ? 'Ver' : 'Responder' }}
                            </a>
                        </div>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-4">
                    {{ $filter === 'pendentes' ? 'Nenhuma pergunta esperando resposta. 🎈' : 'Nenhuma pergunta encontrada.' }}
                </li>
            @endforelse
        </ul>
    </div>

    {{ $questions->links() }}
@endsection
