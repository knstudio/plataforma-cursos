{{-- Shared lesson fields. Expects $lesson and $modules. --}}
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Dados da aula</h4>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="form-label" for="title">Título</label>
            <input type="text" id="title" name="title" value="{{ old('title', $lesson->title) }}" required
                   class="form-control @error('title') is-invalid @enderror" placeholder="Ex.: Técnicas Básicas">
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <div class="form-group col-md-8">
                <label class="form-label" for="module_id">Módulo</label>
                <select id="module_id" name="module_id" class="form-control custom-select @error('module_id') is-invalid @enderror">
                    @foreach ($modules as $module)
                        <option value="{{ $module->id }}" @selected((int) old('module_id', $lesson->module_id) === $module->id)>{{ $module->title }}</option>
                    @endforeach
                </select>
                @error('module_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group col-md-4">
                <label class="form-label" for="duration">Duração</label>
                <input type="text" id="duration" name="duration" value="{{ old('duration', $lesson->formattedDuration()) }}"
                       class="form-control @error('duration') is-invalid @enderror" placeholder="mm:ss">
                @error('duration')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="video_url">Link do vídeo</label>
            <input type="text" id="video_url" name="video_url" value="{{ old('video_url', $lesson->videoUrl()) }}"
                   class="form-control @error('video_url') is-invalid @enderror" placeholder="https://vimeo.com/563689407">
            <small class="form-text text-muted">Cole o link do Vimeo, YouTube ou o link de embed do Panda Video. Pode deixar vazio para aulas só com texto/material.</small>
            @error('video_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Descrição da aula</label>
            @include('admin.partials.editor', ['name' => 'content', 'value' => old('content', $lesson->content), 'height' => '220px'])
            @error('content')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="is_published" name="is_published" value="1"
                   @checked(old('is_published', $lesson->is_published))>
            <label class="custom-control-label" for="is_published">Publicada (visível para os alunos)</label>
        </div>
    </div>
</div>
