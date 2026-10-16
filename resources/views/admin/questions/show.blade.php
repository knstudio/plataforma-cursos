@extends('layouts.app')

@section('title', 'Pergunta de '.$question->user->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.questions.index') }}">Perguntas</a></li>
    <li class="breadcrumb-item active">#{{ $question->id }}</li>
@endsection

@section('content')
    @php($course = $question->lesson->module->course)

    <div class="row">
        <div class="col-lg-8">
            <h1 class="h2 mb-2">{{ $question->lesson->title }}</h1>
            <p class="text-muted d-flex align-items-center flex-wrap mb-4">
                <span class="mr-3">{{ $course->title }}</span>
                <a href="{{ $question->url() }}" target="_blank" rel="noopener" class="mr-3">Ver na aula</a>
                <a href="{{ route('admin.questions.index') }}" class="text-black-50">Voltar para a lista</a>
            </p>

            <div class="card card-body">
                <div class="d-flex">
                    @include('student._avatar', ['user' => $question->user, 'size' => 'avatar-sm'])
                    <div class="flex">
                        <p class="d-flex align-items-center flex-wrap mb-2">
                            <a href="{{ route('admin.students.show', $question->user) }}" class="text-body mr-2"><strong>{{ $question->user->name }}</strong></a>
                            <small class="text-muted">{{ $question->created_at->format('d/m/Y H:i') }}</small>
                            @if ($question->is_hidden)
                                <span class="badge badge-warning ml-2">Oculta para os alunos</span>
                            @endif
                        </p>
                        <p class="mb-0 lesson-question__body">{{ $question->body }}</p>
                    </div>
                </div>
            </div>

            @foreach ($question->answers as $answer)
                <div class="d-flex ml-sm-32pt mb-3 border rounded p-3 bg-light">
                    <span class="avatar avatar-sm mr-3">
                        <img src="{{ asset('assets/images/people/juliana.jpg') }}" alt="Juliana Balões" class="avatar-img rounded-circle">
                    </span>
                    <div class="flex">
                        <div class="d-flex align-items-center">
                            <strong>Juliana Balões</strong>
                            <small class="ml-auto text-muted">{{ $answer->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <p class="mt-1 mb-1 lesson-question__body">{{ $answer->body }}</p>
                        <form method="POST" action="{{ route('admin.answers.destroy', $answer) }}" onsubmit="return confirm('Excluir esta resposta?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link btn-sm text-muted p-0">excluir resposta</button>
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="card card-body">
                @include('admin.questions._answer-form', ['question' => $question])
                <small class="text-muted mt-2">O aluno recebe um e-mail avisando da resposta. Todos os alunos do curso veem a resposta na aula.</small>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Moderação</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.questions.visibility', $question) }}" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-light btn-block">
                            <i class="material-icons btn__icon--left">{{ $question->is_hidden ? 'visibility' : 'visibility_off' }}</i>
                            {{ $question->is_hidden ? 'Mostrar para os alunos' : 'Ocultar dos alunos' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.questions.destroy', $question) }}"
                          onsubmit="return confirm('Excluir a pergunta e as respostas?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-block">
                            <i class="material-icons btn__icon--left">delete</i> Excluir pergunta
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
