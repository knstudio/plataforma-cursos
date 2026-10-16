{{-- Shared fields for create/edit. Expects $course. --}}
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Informações do curso</h4>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="form-label" for="title">Título</label>
            <input type="text" id="title" name="title" value="{{ old('title', $course->title) }}" required
                   class="form-control @error('title') is-invalid @enderror" placeholder="Ex.: Arte com Balões Básico">
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="slug">Endereço (slug)</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">/cursos/</span>
                </div>
                <input type="text" id="slug" name="slug" value="{{ old('slug', $course->slug) }}"
                       class="form-control @error('slug') is-invalid @enderror" placeholder="gerado a partir do título">
            </div>
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="short_description">Resumo</label>
            <input type="text" id="short_description" name="short_description" maxlength="500"
                   value="{{ old('short_description', $course->short_description) }}"
                   class="form-control @error('short_description') is-invalid @enderror" placeholder="Uma frase que aparece na lista de cursos">
            @error('short_description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mb-0">
            <label class="form-label">Descrição</label>
            @include('admin.partials.editor', ['name' => 'description', 'value' => old('description', $course->description), 'height' => '180px'])
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Publicação</h4>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="form-label" for="cover">Capa</label>
            @if ($course->cover_image)
                <div class="mb-2 d-flex align-items-center">
                    <img src="{{ $course->coverUrl() }}" alt="Capa" class="rounded course-cover mr-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remove_cover" name="remove_cover" value="1">
                        <label class="custom-control-label" for="remove_cover">Remover capa</label>
                    </div>
                </div>
            @endif
            <input type="file" id="cover" name="cover" accept="image/*" class="form-control-file @error('cover') is-invalid @enderror">
            <small class="form-text text-muted">JPG ou PNG, até 2 MB. Proporção 4:3 fica melhor.</small>
            @error('cover')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="sales_url">Link da página de vendas</label>
            <input type="url" id="sales_url" name="sales_url" value="{{ old('sales_url', $course->sales_url) }}"
                   class="form-control @error('sales_url') is-invalid @enderror" placeholder="https://julianabaloes.com.br/...">
            <small class="form-text text-muted">
                Usado no botão "Saiba mais" que alunos sem este curso veem no painel deles.
                Vazio: o botão leva direto ao checkout do produto que vende o curso.
            </small>
            @error('sales_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="sort_order">Ordem na lista</label>
            <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $course->sort_order ?? 0) }}"
                   class="form-control @error('sort_order') is-invalid @enderror" style="max-width: 120px">
            @error('sort_order')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="is_published" name="is_published" value="1"
                   @checked(old('is_published', $course->is_published))>
            <label class="custom-control-label" for="is_published">Publicado (visível para alunos com acesso)</label>
        </div>
    </div>
</div>
