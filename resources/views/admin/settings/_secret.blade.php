<div class="form-group">
    <label class="form-label" for="{{ $key }}">{{ $label }}</label>
    <input type="password" id="{{ $key }}" name="{{ $key }}" autocomplete="off"
           class="form-control @error($key) is-invalid @enderror"
           placeholder="{{ $hint ? 'Salvo: '.$hint.' (deixe vazio para manter)' : 'Não configurado' }}">
    @error($key)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="form-text text-muted">
        {{ $help }}
        @if ($source === 'env')
            <br><strong>Usando o valor do arquivo .env.</strong> Salve aqui para gerenciar pelo painel.
        @endif
    </small>
    @if ($source === 'admin')
        <div class="custom-control custom-checkbox mt-1">
            <input type="checkbox" class="custom-control-input" id="clear_{{ $key }}" name="clear_secrets[]" value="{{ $key }}">
            <label class="custom-control-label" for="clear_{{ $key }}">Apagar o valor salvo</label>
        </div>
    @endif
</div>
