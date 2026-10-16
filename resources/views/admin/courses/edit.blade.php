@extends('layouts.app')

@section('title', 'Editar curso')

@push('styles')
    <link type="text/css" href="{{ asset('assets/css/nestable.css') }}" rel="stylesheet">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">Cursos</a></li>
    <li class="breadcrumb-item active">{{ $course->title }}</li>
@endsection

@section('content')
    <div class="media align-items-center mb-headings">
        <div class="media-body">
            <h1 class="h2">{{ $course->title }}</h1>
        </div>
        <div class="media-right">
            <button type="submit" form="course-form" class="btn btn-success">Salvar</button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <div class="flex">
                        <h4 class="card-title">Módulos e aulas</h4>
                        <p class="card-subtitle">Arraste pelo ícone <i class="material-icons" style="font-size: 16px; vertical-align: middle">menu</i> para reordenar. Aulas podem ser movidas entre módulos.</p>
                    </div>
                    <small id="curriculum-status" class="text-muted ml-3"></small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.modules.store', $course) }}" class="form-inline mb-4">
                        @csrf
                        <input type="text" name="title" class="form-control mr-2 mb-2 flex @error('title', 'module') is-invalid @enderror" placeholder="Nome do novo módulo" required>
                        <button type="submit" class="btn btn-primary mb-2">Adicionar módulo <i class="material-icons btn__icon--right">add</i></button>
                    </form>
                    @error('title', 'module')
                        <div class="invalid-feedback mt-n3 mb-3">{{ $message }}</div>
                    @enderror

                    @if ($course->modules->isEmpty())
                        <p class="text-muted mb-0">Nenhum módulo ainda. Crie o primeiro acima.</p>
                    @else
                        <div class="nestable" id="curriculum" data-url="{{ route('admin.courses.curriculum.update', $course) }}">
                            <ul class="nestable-list">
                                @foreach ($course->modules as $module)
                                    <li class="nestable-item nestable-item-handle" data-id="{{ $module->id }}" data-type="module">
                                        <div class="nestable-handle"><i class="material-icons">menu</i></div>
                                        <div class="nestable-content">
                                            <div class="media align-items-center">
                                                <div class="media-body">
                                                    <h5 class="card-title h6 mb-0">{{ $module->title }}</h5>
                                                    <small class="text-muted">{{ $module->lessons->count() }} {{ Str::plural('aula', $module->lessons->count()) }}</small>
                                                </div>
                                                <div class="media-right d-flex">
                                                    <a href="{{ route('admin.lessons.create', [$course, 'module' => $module->id]) }}" class="btn btn-white btn-sm mr-1" title="Adicionar aula">
                                                        <i class="material-icons">add</i> Aula
                                                    </a>
                                                    <button type="button" class="btn btn-white btn-sm mr-1" data-toggle="collapse" data-target="#rename-module-{{ $module->id }}" title="Renomear">
                                                        <i class="material-icons">edit</i>
                                                    </button>
                                                    <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('Excluir este módulo?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-white btn-sm text-danger" title="Excluir módulo">
                                                            <i class="material-icons">delete</i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.modules.update', $module) }}" id="rename-module-{{ $module->id }}" class="collapse mt-2">
                                                @csrf
                                                @method('PUT')
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="title" value="{{ $module->title }}" class="form-control" required>
                                                    <div class="input-group-append">
                                                        <button type="submit" class="btn btn-primary">Salvar</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                        @if ($module->lessons->isNotEmpty())
                                            <ul class="nestable-list">
                                                @foreach ($module->lessons as $lesson)
                                                    <li class="nestable-item nestable-item-handle" data-id="{{ $lesson->id }}" data-type="lesson">
                                                        <div class="nestable-handle"><i class="material-icons">menu</i></div>
                                                        <div class="nestable-content">
                                                            <div class="media align-items-center">
                                                                <div class="media-body">
                                                                    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="text-body">{{ $lesson->title }}</a>
                                                                    <div>
                                                                        @if (! $lesson->is_published)
                                                                            <span class="badge badge-light">Rascunho</span>
                                                                        @endif
                                                                        @if ($lesson->hasVideo())
                                                                            <small class="text-muted mr-2"><i class="material-icons" style="font-size: 14px; vertical-align: middle">play_circle_outline</i> {{ $lesson->video_provider->label() }}</small>
                                                                        @else
                                                                            <small class="text-warning mr-2">sem vídeo</small>
                                                                        @endif
                                                                        @if ($lesson->formattedDuration())
                                                                            <small class="text-muted mr-2">{{ $lesson->formattedDuration() }}</small>
                                                                        @endif
                                                                        @if ($lesson->attachments_count)
                                                                            <small class="text-muted"><i class="material-icons" style="font-size: 14px; vertical-align: middle">attach_file</i> {{ $lesson->attachments_count }}</small>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="media-right">
                                                                    <a href="{{ route('admin.lessons.edit', $lesson) }}" class="btn btn-white btn-sm"><i class="material-icons">edit</i></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data" id="course-form">
                @csrf
                @method('PUT')
                @include('admin.courses._form')
                <button type="submit" class="btn btn-success mb-4">Salvar curso</button>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn btn-light btn-block">
                        <i class="material-icons btn__icon--left">visibility</i> Ver como aluno
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title text-danger">Zona de perigo</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Cursos com alunos não podem ser excluídos. Nesse caso, desmarque "Publicado".</p>
                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Excluir este curso, todos os módulos, aulas e materiais? Isso não pode ser desfeito.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-block">Excluir curso</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/jquery.nestable.js') }}"></script>
    <script>
        (function ($) {
            var $tree = $('#curriculum');
            var $status = $('#curriculum-status');

            if (!$tree.length) {
                return;
            }

            $tree.nestable({
                rootClass: 'nestable',
                listNodeName: 'ul',
                listClass: 'nestable-list',
                itemClass: 'nestable-item',
                dragClass: 'nestable-drag',
                handleClass: 'nestable-handle',
                collapsedClass: 'nestable-collapsed',
                placeClass: 'nestable-placeholder',
                emptyClass: 'nestable-empty',
                maxDepth: 2
            });

            /**
             * Modules must stay at the top level and lessons inside a module.
             */
            function toPayload(items) {
                var modules = [];

                for (var i = 0; i < items.length; i++) {
                    var item = items[i];
                    var children = item.children || [];

                    if (item.type !== 'module') {
                        return null;
                    }

                    var lessons = [];
                    for (var j = 0; j < children.length; j++) {
                        if (children[j].type !== 'lesson') {
                            return null;
                        }
                        lessons.push(children[j].id);
                    }

                    modules.push({ id: item.id, lessons: lessons });
                }

                return modules;
            }

            $tree.on('change', function () {
                var payload = toPayload($tree.nestable('serialize'));

                if (payload === null) {
                    alert('Aulas precisam ficar dentro de um módulo, e módulos não podem ficar dentro de outros módulos.');
                    window.location.reload();
                    return;
                }

                $status.removeClass('text-success text-danger').addClass('text-muted').text('Salvando...');

                fetch($tree.data('url'), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ modules: payload })
                })
                    .then(function (response) {
                        return response.json().then(function (body) {
                            if (!response.ok) {
                                throw new Error(body.message || 'Erro ao salvar.');
                            }
                            $status.removeClass('text-muted').addClass('text-success').text(body.message);
                        });
                    })
                    .catch(function (error) {
                        $status.removeClass('text-muted').addClass('text-danger').text(error.message);
                    });
            });
        })(jQuery);
    </script>
@endpush
