{{-- Funnel settings. Expects $funil. --}}
<div class="form-row">
    <div class="form-group col-md-6">
        <label class="form-label" for="nome">Nome</label>
        <input type="text" id="nome" name="nome" value="{{ old('nome', $funil->nome) }}" required
               class="form-control @error('nome') is-invalid @enderror" placeholder="Ex.: Ebook Arte com Balões">
        @error('nome')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label class="form-label" for="slug">Identificador (slug)</label>
        <input type="text" id="slug" name="slug" value="{{ old('slug', $funil->slug) }}"
               class="form-control @error('slug') is-invalid @enderror" placeholder="gerado a partir do nome">
        <small class="form-text text-muted">Vai no campo escondido <code>funil</code> do formulário da landing.</small>
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label class="form-label" for="instancia">Instância do WhatsApp (Evolution)</label>
        <input type="text" id="instancia" name="instancia" value="{{ old('instancia', $funil->instancia) }}" required
               class="form-control @error('instancia') is-invalid @enderror" placeholder="bubbles">
        @error('instancia')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label class="form-label" for="hora_inicio_envio">Enviar ofertas das</label>
        <select id="hora_inicio_envio" name="hora_inicio_envio" class="form-control @error('hora_inicio_envio') is-invalid @enderror">
            @for ($hora = 0; $hora <= 23; $hora++)
                <option value="{{ $hora }}" @selected((int) old('hora_inicio_envio', $funil->hora_inicio_envio) === $hora)>{{ $hora }}h</option>
            @endfor
        </select>
        @error('hora_inicio_envio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label class="form-label" for="hora_fim_envio">até as</label>
        <select id="hora_fim_envio" name="hora_fim_envio" class="form-control @error('hora_fim_envio') is-invalid @enderror">
            @for ($hora = 1; $hora <= 24; $hora++)
                <option value="{{ $hora }}" @selected((int) old('hora_fim_envio', $funil->hora_fim_envio) === $hora)>{{ $hora }}h</option>
            @endfor
        </select>
        @error('hora_fim_envio')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label class="form-label" for="prioridade">Prioridade</label>
        <input type="number" id="prioridade" name="prioridade" min="-100" max="100" value="{{ old('prioridade', $funil->prioridade) }}"
               class="form-control @error('prioridade') is-invalid @enderror">
        <small class="form-text text-muted">Quando o contato está em mais de um funil, o de maior prioridade vai primeiro (o pós-venda sempre passa na frente).</small>
        @error('prioridade')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="ativo" name="ativo" value="1" @checked(old('ativo', $funil->ativo))>
    <label class="custom-control-label" for="ativo">Ativo (enviando mensagens)</label>
</div>
