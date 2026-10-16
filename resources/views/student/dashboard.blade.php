@extends('layouts.app')

@section('title', 'Meus cursos')

@section('breadcrumb')
    <li class="breadcrumb-item active">Meus cursos</li>
@endsection

@section('content')
    <div class="media mb-headings align-items-center">
        <div class="media-body">
            <h1 class="h2">Meus cursos</h1>
        </div>
    </div>

    @if ($courses->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="material-icons" style="font-size: 48px">school</i>
                <p class="mt-2 mb-0">Você ainda não tem cursos liberados.</p>
            </div>
        </div>
    @else
        <div class="card-columns">
            @foreach ($courses as $course)
                @php
                    $completed = (int) ($completedByCourse[$course->id] ?? 0);
                    $total = $course->lessons_count;
                    $percent = $total > 0 ? (int) round($completed / $total * 100) : 0;
                @endphp
                <div class="card">
                    <div class="card-header">
                        <div class="media">
                            <div class="media-left">
                                <a href="{{ route('courses.show', $course) }}">
                                    <img src="{{ $course->coverUrl() }}" alt="{{ $course->title }}" class="rounded course-cover">
                                </a>
                            </div>
                            <div class="media-body">
                                <h4 class="card-title m-0">
                                    <a href="{{ route('courses.show', $course) }}">{{ $course->title }}</a>
                                </h4>
                                <small class="text-muted">Aulas: {{ $completed }} de {{ $total }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="progress rounded-0">
                        <div class="progress-bar progress-bar-striped {{ $percent === 100 ? 'bg-success' : 'bg-primary' }}"
                             role="progressbar" style="width: {{ $percent }}%"
                             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="card-footer bg-white">
                        @if ($percent === 100)
                            <a href="{{ route('courses.show', $course) }}" class="btn btn-success btn-sm">
                                Concluído <i class="material-icons btn__icon--right">check</i>
                            </a>
                        @else
                            <a href="{{ route('courses.show', $course) }}" class="btn btn-primary btn-sm">
                                {{ $completed > 0 ? 'Continuar' : 'Começar' }}
                                <i class="material-icons btn__icon--right">play_circle_outline</i>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($otherCourses->isNotEmpty())
        <div class="media mb-headings align-items-center mt-4">
            <div class="media-body">
                <h1 class="h2">Outros cursos da Juliana Balões que podem te interessar</h1>
            </div>
        </div>

        <div class="card-columns">
            @foreach ($otherCourses as $course)
                @php($purchaseUrl = $course->purchaseUrl())
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="card-title mb-0">
                            <a href="{{ $purchaseUrl }}" target="_blank" rel="noopener">{{ $course->title }}</a>
                        </h4>
                        <div class="rating">
                            @for ($i = 0; $i < 5; $i++)
                                <i class="material-icons">star</i>
                            @endfor
                        </div>
                    </div>
                    <a href="{{ $purchaseUrl }}" target="_blank" rel="noopener">
                        <img src="{{ $course->coverUrl() }}" alt="{{ $course->title }}" style="width: 100%">
                    </a>
                    <div class="card-body">
                        <a href="{{ $purchaseUrl }}" target="_blank" rel="noopener" class="btn btn-danger btn-lg btn-block">SAIBA MAIS</a>
                        @if ($course->short_description)
                            <p class="text-muted mt-3 mb-0">{{ $course->short_description }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
