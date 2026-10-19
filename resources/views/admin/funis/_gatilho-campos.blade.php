{{-- Fields of an entry trigger. Expects $gatilho (new or saved), $products, $tiposGatilho and $prefixo (unique html ids). --}}
@php
    $tipoAtual = $gatilho->tipo?->value ?? \App\Enums\FunilGatilhoTipo::Formulario->value;
@endphp
<div class="js-gatilho" data-tipo="{{ $tipoAtual }}">
    <div class="form-row">
        <div class="form-group col-md-6">
            <label class="form-label" for="{{ $prefixo }}-tipo">Tipo de entrada</label>
            <select id="{{ $prefixo }}-tipo" name="tipo" class="form-control js-gatilho-tipo">
                @foreach ($tiposGatilho as $tipo)
                    <option value="{{ $tipo->value }}" @selected($tipo->value === $tipoAtual)>{{ $tipo->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6 js-so-compra_produto">
            <label class="form-label" for="{{ $prefixo }}-product">Quem comprar o produto</label>
            <select id="{{ $prefixo }}-product" name="product_id" class="form-control">
                <option value="">Escolha…</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected($product->id === $gatilho->product_id)>{{ $product->name }}{{ $product->is_active ? '' : ' (inativo)' }}</option>
                @endforeach
            </select>
            <small class="form-text text-muted">A compra aprovada coloca a pessoa neste funil na hora, na frente dos outros funis dela.</small>
        </div>
    </div>

    <div class="js-so-formulario">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="form-label" for="{{ $prefixo }}-msg_confirmacao">Mensagem de confirmação (enviada após o formulário)</label>
                <textarea id="{{ $prefixo }}-msg_confirmacao" name="msg_confirmacao" rows="4" class="form-control">{{ $gatilho->opcao('msg_confirmacao') }}</textarea>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="{{ $prefixo }}-msg_ebook">Mensagem com o ebook (enviada quando a pessoa responde)</label>
                <textarea id="{{ $prefixo }}-msg_ebook" name="msg_ebook" rows="4" class="form-control">{{ $gatilho->opcao('msg_ebook') }}</textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="form-label" for="{{ $prefixo }}-link_ebook">Link do ebook (<code>{link_ebook}</code>)</label>
                <input type="url" id="{{ $prefixo }}-link_ebook" name="link_ebook" value="{{ $gatilho->opcao('link_ebook') }}" class="form-control" placeholder="https://drive.google.com/...">
            </div>
            <div class="form-group col-md-6">
                <label class="form-label" for="{{ $prefixo }}-pagina_obrigado">Página de obrigado (depois do formulário)</label>
                <input type="url" id="{{ $prefixo }}-pagina_obrigado" name="pagina_obrigado" value="{{ $gatilho->opcao('pagina_obrigado') }}" class="form-control" placeholder="https://julianabaloes.com.br/obrigado-01/">
            </div>
        </div>
        <p class="text-muted small">Variáveis: <code>{primeiro_nome}</code> <code>{link_ebook}</code>. Só quem responde à confirmação entra nos blocos.</p>
    </div>

    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="{{ $prefixo }}-ativo" name="ativo" value="1" @checked($gatilho->ativo ?? true)>
        <label class="custom-control-label" for="{{ $prefixo }}-ativo">Ativo</label>
    </div>
</div>
