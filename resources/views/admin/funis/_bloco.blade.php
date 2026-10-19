{{-- One block of the funnel. Expects $bloco, $numero (position among active blocks, null when inactive), $products and $contatos. --}}
@use('App\Enums\EsperaUnidade')
@use('App\Services\Funil\FunilTexto')
@php
    $product = $bloco->product;
    $precos = $bloco->mensagens->where('ativo', true)->map(fn ($mensagem) => $mensagem->offer?->price)->filter()->unique()->map(fn ($preco) => FunilTexto::preco($preco));
    $primeiro = $numero === 1;
    $usaEsperaCompra = $bloco->espera_se_comprou_seg !== null;
@endphp
<div class="card mb-3 js-bloco" id="bloco-{{ $bloco->id }}" data-id="{{ $bloco->id }}"
     data-produto="{{ $product?->name }}"
     data-preco="{{ FunilTexto::preco($product?->price) }}"
     data-link="{{ $product ? route('checkout.show', $product) : '' }}"
     style="{{ $bloco->ativo ? '' : 'opacity: .7' }}">
    <div class="card-header d-flex align-items-center" style="cursor: pointer" data-toggle="collapse" data-target="#bloco-corpo-{{ $bloco->id }}">
        <span class="js-arrastar-bloco text-muted mr-1" style="cursor: grab" title="Arrastar" onclick="event.stopPropagation()"><i class="material-icons">drag_indicator</i></span>
        <span class="d-flex flex-column mr-2" onclick="event.stopPropagation()">
            <button type="submit" form="mover-bloco-{{ $bloco->id }}-cima" class="btn btn-link btn-sm p-0 text-muted" title="Subir"><i class="material-icons" style="font-size: 18px">keyboard_arrow_up</i></button>
            <button type="submit" form="mover-bloco-{{ $bloco->id }}-baixo" class="btn btn-link btn-sm p-0 text-muted" title="Descer"><i class="material-icons" style="font-size: 18px">keyboard_arrow_down</i></button>
        </span>
        <span class="badge badge-pill {{ $bloco->ativo ? 'badge-primary' : 'badge-light' }} mr-3" style="min-width: 28px">{{ $numero ?? '–' }}</span>
        <div class="flex">
            <strong>{{ $product?->name ?? '(produto removido)' }}</strong>
            <div class="small text-muted">
                @if (! $bloco->ativo)
                    Bloco desativado
                @else
                    Começa {{ EsperaUnidade::descrever($bloco->espera_inicio_seg) }} {{ $primeiro ? 'após a entrada no funil' : 'após o bloco anterior' }}
                    @if (! $primeiro && $usaEsperaCompra)
                        · {{ EsperaUnidade::descrever($bloco->espera_se_comprou_seg) }} se comprou o anterior
                    @endif
                @endif
            </div>
        </div>
        @if ($precos->isNotEmpty())
            <span class="badge badge-warning ml-2">R$ {{ $precos->implode(' / ') }}</span>
        @endif
        <span class="badge badge-light ml-2">{{ $bloco->mensagens->count() }} msg</span>
        <span class="badge badge-success ml-2" title="Contatos neste bloco agora">{{ $contatos }} {{ $contatos === 1 ? 'contato' : 'contatos' }}</span>
        <i class="material-icons ml-2 text-muted">expand_more</i>
    </div>

    <div class="collapse" id="bloco-corpo-{{ $bloco->id }}">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.funil-blocos.update', $bloco) }}" class="bg-light rounded p-3 mb-4">
                @csrf
                @method('PUT')
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label class="form-label" for="bloco-produto-{{ $bloco->id }}">Produto</label>
                        <select id="bloco-produto-{{ $bloco->id }}" name="product_id" class="form-control form-control-sm">
                            @foreach ($products as $opcao)
                                <option value="{{ $opcao->id }}" @selected($opcao->id === $bloco->product_id)>{{ $opcao->name }}{{ $opcao->is_active ? '' : ' (inativo)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="form-label" for="espera-inicio-{{ $bloco->id }}">{{ $primeiro ? 'Começa após a entrada' : 'Começa após o bloco anterior' }}</label>
                        @include('admin.funis._espera', ['campo' => 'espera_inicio', 'segundos' => $bloco->espera_inicio_seg, 'id' => 'espera-inicio-'.$bloco->id])
                    </div>
                    <div class="form-group col-md-4 {{ $primeiro ? 'd-none' : '' }}">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input" id="usa-compra-{{ $bloco->id }}" name="usa_espera_compra" value="1" @checked($usaEsperaCompra)>
                            <label class="custom-control-label form-label" for="usa-compra-{{ $bloco->id }}">Se comprou o produto anterior</label>
                        </div>
                        @include('admin.funis._espera', ['campo' => 'espera_compra', 'segundos' => $bloco->espera_se_comprou_seg ?? 1800, 'id' => 'espera-compra-'.$bloco->id])
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <div class="custom-control custom-checkbox mr-3">
                        <input type="checkbox" class="custom-control-input" id="bloco-ativo-{{ $bloco->id }}" name="ativo" value="1" @checked($bloco->ativo)>
                        <label class="custom-control-label" for="bloco-ativo-{{ $bloco->id }}">Bloco ativo</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mr-2">Salvar bloco</button>
                    <button type="submit" form="excluir-bloco-{{ $bloco->id }}" class="btn btn-white btn-sm text-danger">Excluir bloco</button>
                </div>
                <small class="form-text text-muted">Desativar tira o bloco do funil: quem está nele passa para o próximo bloco. Quem já tem o produto pula o bloco automaticamente.</small>
            </form>

            <div class="js-lista-mensagens" data-url="{{ route('admin.funil-mensagens.reorder', $bloco) }}">
                @foreach ($bloco->mensagens as $indice => $mensagem)
                    @include('admin.funis._mensagem', ['mensagem' => $mensagem, 'bloco' => $bloco, 'indice' => $indice])
                @endforeach
            </div>

            @if ($bloco->mensagens->isEmpty())
                <p class="text-muted">Nenhuma mensagem neste bloco ainda.</p>
            @endif

            <form method="POST" action="{{ route('admin.funil-mensagens.store', $bloco) }}">
                @csrf
                <button type="submit" class="btn btn-white btn-sm"><i class="material-icons btn__icon--left">add</i> Mensagem</button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.funil-blocos.destroy', $bloco) }}" id="excluir-bloco-{{ $bloco->id }}" onsubmit="return confirm('Excluir este bloco e as mensagens dele?')">
        @csrf
        @method('DELETE')
    </form>
    @foreach (['cima', 'baixo'] as $direcao)
        <form method="POST" action="{{ route('admin.funil-blocos.move', $bloco) }}" id="mover-bloco-{{ $bloco->id }}-{{ $direcao }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="direcao" value="{{ $direcao }}">
        </form>
    @endforeach
</div>
