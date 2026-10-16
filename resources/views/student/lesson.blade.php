@extends('layouts.app')

@section('title', $lesson->title.' - '.$course->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Meus cursos</a></li>
    <li class="breadcrumb-item active">{{ $course->title }}</li>
@endsection

@section('content')
    <h1 class="h2">{{ $course->title }}</h1>
    <p>{{ $lessonNumber ? $lessonNumber.'. ' : '' }}{{ $lesson->title }}</p>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                @if ($lesson->hasVideo())
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="{{ $lesson->embedUrl() }}"
                                allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                    </div>
                @endif

                <div class="row lesson-nav">
                    <div class="col">
                        <div class="card-body">
                            @if ($previousLesson)
                                <a href="{{ route('courses.lessons.show', [$course, $previousLesson]) }}" class="btn btn-warning float-right">
                                    <span class="material-icons">arrow_back</span>&nbsp; Anterior
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="col">
                        <div class="card-body">
                            @if ($nextLesson)
                                <a href="{{ route('courses.lessons.show', [$course, $nextLesson]) }}" class="btn btn-warning">
                                    Próxima &nbsp;<span class="material-icons">arrow_forward</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body border-top">
                    @if ($isCompleted)
                        <form method="POST" action="{{ route('courses.lessons.uncomplete', [$course, $lesson]) }}" class="d-flex align-items-center">
                            @csrf
                            @method('DELETE')
                            <span class="text-success mr-3"><i class="material-icons lesson-done-icon">check_circle</i> Aula concluída</span>
                            <button type="submit" class="btn btn-link btn-sm text-muted p-0">desmarcar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('courses.lessons.complete', [$course, $lesson]) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="material-icons btn__icon--left">check</i> Marcar como concluída
                            </button>
                        </form>
                    @endif
                </div>

                @if (filled($lesson->content))
                    <div class="card-body border-top">
                        <h4 class="card-title mb-3">{{ $lesson->title }}</h4>
                        {!! $lesson->content !!}
                    </div>
                @endif
            </div>

            @include('student._questions')
        </div>

        <div class="col-md-4">
            @if ($lesson->attachments->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Materiais da aula</h4>
                    </div>
                    <ul class="list-group list-group-fit">
                        @foreach ($lesson->attachments as $attachment)
                            <li class="list-group-item">
                                <a href="{{ route('attachments.show', $attachment) }}" class="d-flex align-items-center">
                                    <i class="material-icons mr-2">get_app</i> {{ $attachment->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <div class="flex">
                        <h4 class="card-title">Módulos</h4>
                        <p class="card-subtitle">{{ $progress }}% concluído</p>
                    </div>
                </div>
                <div class="progress rounded-0" style="height: 4px">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"
                         aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="card-body">
                    <div class="accordion" id="accordionPlaylist">
                        @php($number = 0)
                        @foreach ($modules as $module)
                            @php($isCurrentModule = $module->id === $lesson->module_id)
                            <button style="margin-bottom: .5rem" type="button"
                                    class="btn btn-block {{ $isCurrentModule ? 'btn-primary' : 'btn-secondary' }}"
                                    data-toggle="collapse" data-target="#module-{{ $module->id }}"
                                    aria-expanded="{{ $isCurrentModule ? 'true' : 'false' }}">
                                {{ $module->title }}
                            </button>

                            <div id="module-{{ $module->id }}" class="collapse {{ $isCurrentModule ? 'show' : '' }}" data-parent="#accordionPlaylist">
                                <ul class="card list-group list-group-fit">
                                    @foreach ($module->lessons as $item)
                                        @php($number++)
                                        <li class="list-group-item {{ $item->id === $lesson->id ? 'lesson-active' : '' }}">
                                            <div class="media">
                                                <div class="media-left">
                                                    @if ($completedIds->contains($item->id))
                                                        <i class="material-icons text-success lesson-done-icon">check_circle</i>
                                                    @else
                                                        <div class="text-muted">{{ $number }}.</div>
                                                    @endif
                                                </div>
                                                <div class="media-body">
                                                    <a href="{{ route('courses.lessons.show', [$course, $item]) }}">{{ $item->title }}</a>
                                                </div>
                                                <div class="media-right">
                                                    <small class="text-muted-light">{{ $item->formattedDuration() }}</small>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
