{{-- One message of a block. Expects $mensagem, $bloco and $indice (0-based). --}}
@php
    $ofertas = $bloco->product?->offers->filter(fn ($offer) => $offer->is_active || $offer->id === $mensagem->product_offer_id) ?? collect();
    $formId = 'mensagem-form-'.$mensagem->id;
@endphp
<div class="js-mensagem mb-3" data-id="{{ $mensagem->id }}">
    <div class="d-flex align-items-center text-muted small mb-1 ml-4">
        <i class="material-icons mr-1" style="font-size: 16px">schedule</i>
        <span class="mr-2">esperar</span>
        @include('admin.funis._espera', ['campo' => 'espera', 'segundos' => $mensagem->espera_seg, 'id' => 'espera-'.$mensagem->id, 'form' => $formId])
        <span class="ml-2">{{ $indice === 0 ? 'depois do início do bloco' : 'depois da mensagem anterior' }}</span>
    </div>

    <div class="card mb-0 {{ $mensagem->ativo ? '' : 'bg-light' }}" style="{{ $mensagem->ativo ? '' : 'opacity: .65' }}">
        <div class="card-body p-3 d-flex">
            <div class="d-flex flex-column align-items-center mr-2">
                <span class="js-arrastar-mensagem text-muted" style="cursor: grab" title="Arrastar"><i class="material-icons">drag_indicator</i></span>
                <button type="submit" form="mover-mensagem-{{ $mensagem->id }}-cima" class="btn btn-link btn-sm p-0 text-muted" title="Subir"><i class="material-icons">keyboard_arrow_up</i></button>
                <button type="submit" form="mover-mensagem-{{ $mensagem->id }}-baixo" class="btn btn-link btn-sm p-0 text-muted" title="Descer"><i class="material-icons">keyboard_arrow_down</i></button>
            </div>

            <div class="flex">
                <div class="row">
                    <div class="col-lg-7">
                        <textarea name="texto" form="{{ $formId }}" rows="5" class="form-control js-previa-texto" required>{{ $mensagem->texto }}</textarea>
                        <div class="d-flex flex-wrap align-items-center mt-2">
                            <select name="product_offer_id" form="{{ $formId }}" class="custom-select custom-select-sm mr-3 mb-2 js-previa-oferta" style="max-width: 280px" aria-label="Oferta">
                                <option value="">Sem oferta (preço e link do produto)</option>
                                @foreach ($ofertas as $offer)
                                    <option value="{{ $offer->id }}" data-preco="{{ \App\Services\Funil\FunilTexto::preco($offer->price) }}" data-link="{{ $offer->checkoutUrl() }}"
                                            @selected($offer->id === $mensagem->product_offer_id)>
                                        {{ $offer->name }} · R$ {{ \App\Services\Funil\FunilTexto::preco($offer->price) }}{{ $offer->is_active ? '' : ' (desativada)' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="mensagem-ativo-{{ $mensagem->id }}" name="ativo" value="1" form="{{ $formId }}" @checked($mensagem->ativo)>
                                <label class="custom-control-label" for="mensagem-ativo-{{ $mensagem->id }}">Ativa</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <label class="form-label">Prévia no WhatsApp</label>
                        <div class="rounded p-2" style="background: #ece5dd">
                            <div class="rounded p-2 js-previa" style="background: #dcf8c6; white-space: pre-wrap; word-break: break-word; font-size: 13px">{{ \App\Services\Funil\FunilTexto::mensagem($mensagem, $bloco, 'Maria', 123) }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-2">
                    <button type="submit" form="{{ $formId }}" class="btn btn-primary btn-sm">Salvar mensagem</button>
                    <button type="submit" form="excluir-mensagem-{{ $mensagem->id }}" class="btn btn-white btn-sm text-danger">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.funil-mensagens.update', $mensagem) }}" id="{{ $formId }}">
        @csrf
        @method('PUT')
    </form>
    <form method="POST" action="{{ route('admin.funil-mensagens.destroy', $mensagem) }}" id="excluir-mensagem-{{ $mensagem->id }}" onsubmit="return confirm('Excluir esta mensagem?')">
        @csrf
        @method('DELETE')
    </form>
    @foreach (['cima', 'baixo'] as $direcao)
        <form method="POST" action="{{ route('admin.funil-mensagens.move', $mensagem) }}" id="mover-mensagem-{{ $mensagem->id }}-{{ $direcao }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="direcao" value="{{ $direcao }}">
        </form>
    @endforeach
</div>
