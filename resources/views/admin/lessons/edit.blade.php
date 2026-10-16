@extends('layouts.app')

@section('title', 'Editar aula')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">Cursos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.courses.edit', $course) }}">{{ $course->title }}</a></li>
    <li class="breadcrumb-item active">{{ $lesson->title }}</li>
@endsection

@section('content')
    <div class="media align-items-center mb-headings">
        <div class="media-body">
            <h1 class="h2">{{ $lesson->title }}</h1>
        </div>
        <div class="media-right">
            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-white mr-2">Voltar ao curso</a>
            <button type="submit" form="lesson-form" class="btn btn-success">Salvar</button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.lessons.update', $lesson) }}" id="lesson-form">
                @csrf
                @method('PUT')
                @include('admin.lessons._form')
            </form>
        </div>

        <div class="col-lg-4">
            @if ($lesson->hasVideo())
                <div class="card">
                    <div class="embed-responsive embed-responsive-16by9">
                        <iframe class="embed-responsive-item" src="{{ $lesson->embedUrl() }}" allowfullscreen></iframe>
                    </div>
                    <div class="card-body py-2">
                        <small class="text-muted">{{ $lesson->video_provider->label() }} · {{ Str::limit($lesson->video_id, 40) }}</small>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Materiais para download</h4>
                </div>
                @if ($lesson->attachments->isNotEmpty())
                    <ul class="list-group list-group-fit">
                        @foreach ($lesson->attachments as $attachment)
                            <li class="list-group-item">
                                <div class="media align-items-center">
                                    <div class="media-body">
                                        <a href="{{ route('attachments.show', $attachment) }}">{{ $attachment->title }}</a><br>
                                        <small class="text-muted">{{ $attachment->original_name }} · {{ Number::fileSize($attachment->size) }}</small>
                                    </div>
                                    <div class="media-right">
                                        <form method="POST" action="{{ route('admin.attachments.destroy', $attachment) }}" onsubmit="return confirm('Remover este material?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-white btn-sm text-danger"><i class="material-icons">delete</i></button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.attachments.store', $lesson) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <input type="text" name="title" class="form-control @error('title', 'attachment') is-invalid @enderror" placeholder="Título (opcional)">
                        </div>
                        <div class="form-group">
                            <input type="file" name="file" required class="form-control-file @error('file', 'attachment') is-invalid @enderror">
                            <small class="form-text text-muted">PDF, ZIP, imagens, Office ou MP3. Até 100 MB.</small>
                            @error('file', 'attachment')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="material-icons btn__icon--left">attach_file</i> Anexar
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.lessons.destroy', $lesson) }}" onsubmit="return confirm('Excluir esta aula e seus materiais?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-block">Excluir aula</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
