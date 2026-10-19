@extends('layouts.app')

@section('title', $funil->nome)

@push('styles')
    <link type="text/css" href="{{ asset('assets/vendor/dragula/dragula.min.css') }}" rel="stylesheet">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.funis.index') }}">Funis WhatsApp</a></li>
    <li class="breadcrumb-item active">{{ $funil->nome }}</li>
@endsection

@section('content')
    @php
        $statusLabels = collect(\App\Enums\FunilInscricaoStatus::cases());
        $formulario = $funil->gatilhos->first(fn ($gatilho) => $gatilho->tipo === \App\Enums\FunilGatilhoTipo::Formulario);
    @endphp

    <div class="media align-items-center mb-headings">
        <div class="media-body">
            <h1 class="h2 mb-1">{{ $funil->nome }}</h1>
            <div class="text-muted">
                @if ($funil->ativo)
                    <span class="badge badge-success">Ativo</span>
                @else
                    <span class="badge badge-light">Inativo</span>
                @endif
                Instância <strong>{{ $funil->instancia }}</strong> · envia das {{ $funil->hora_inicio_envio }}h às {{ $funil->hora_fim_envio }}h
                @foreach ($statusLabels as $status)
                    @if ($resumo[$status->value] ?? 0)
                        · {{ $resumo[$status->value] }} {{ Str::lower($status->label()) }}
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    @foreach (['default', 'gatilho'] as $bag)
        @if ($errors->getBag($bag)->any())
            <div class="alert alert-light border-1 border-left-3 border-left-danger" role="alert">
                <strong>Não salvou:</strong>
                <ul class="mb-0">
                    @foreach ($errors->getBag($bag)->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach

    <div class="card mb-3">
        <ul class="nav nav-tabs nav-tabs-card" role="tablist">
            <li class="nav-item"><a class="nav-link active" href="#blocos" data-toggle="tab">Blocos e mensagens</a></li>
            <li class="nav-item"><a class="nav-link" href="#entrada" data-toggle="tab">Entrada (gatilhos)</a></li>
            <li class="nav-item"><a class="nav-link" href="#contatos" data-toggle="tab">Contatos</a></li>
            <li class="nav-item"><a class="nav-link" href="#configuracoes" data-toggle="tab">Configurações</a></li>
        </ul>
    </div>

    <div class="tab-content">
        {{-- BLOCOS --}}
        <div class="tab-pane active" id="blocos">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="flex mb-2">
                    <p class="text-muted mb-0">
                        Arraste pelo ícone <i class="material-icons" style="font-size: 16px; vertical-align: middle">drag_indicator</i> ou use as setas para mudar a ordem.
                        Variáveis: <code>{primeiro_nome}</code> <code>{produto}</code> <code>{preco}</code> <code>{link}</code>.
                        O <code>{link}</code> sai com <code>?fi=</code> para o checkout saber de qual contato veio a venda.
                    </p>
                    <small id="ordem-status" class="text-muted"></small>
                </div>
                <form method="POST" action="{{ route('admin.funil-blocos.store', $funil) }}" class="form-inline mb-2">
                    @csrf
                    <select name="product_id" class="custom-select mr-2" aria-label="Produto do novo bloco" required>
                        @foreach ($products->where('is_active', true) as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-success">Novo bloco</button>
                </form>
            </div>

            <div id="lista-blocos" data-url="{{ route('admin.funil-blocos.reorder', $funil) }}">
                @php($numero = 0)
                @foreach ($funil->blocos as $bloco)
                    @include('admin.funis._bloco', [
                        'bloco' => $bloco,
                        'numero' => $bloco->ativo ? ++$numero : null,
                        'contatos' => (int) ($contatosPorBloco[$bloco->id] ?? 0),
                    ])
                @endforeach
            </div>

            @if ($funil->blocos->isEmpty())
                <div class="card card-body text-muted">Nenhum bloco ainda. Escolha o produto e clique em "Novo bloco".</div>
            @endif

            <p class="text-muted small">
                Os produtos e as ofertas (preço e link de checkout) são os da plataforma:
                <a href="{{ route('admin.products.index') }}">gerencie em Produtos</a>.
            </p>
        </div>

        {{-- ENTRADA --}}
        <div class="tab-pane" id="entrada">
            @foreach ($funil->gatilhos as $gatilho)
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <div class="flex">
                            <h4 class="card-title">{{ $gatilho->descricao() }}</h4>
                            <p class="card-subtitle">{{ $gatilho->tipo->label() }}</p>
                        </div>
                        @unless ($gatilho->ativo)
                            <span class="badge badge-light">Inativo</span>
                        @endunless
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.funil-gatilhos.update', $gatilho) }}">
                            @csrf
                            @method('PUT')
                            @include('admin.funis._gatilho-campos', ['gatilho' => $gatilho, 'prefixo' => 'gatilho-'.$gatilho->id])
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Salvar entrada</button>
                                <button type="submit" form="excluir-gatilho-{{ $gatilho->id }}" class="btn btn-white text-danger">Excluir</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.funil-gatilhos.destroy', $gatilho) }}" id="excluir-gatilho-{{ $gatilho->id }}" onsubmit="return confirm('Excluir este gatilho? Quem já entrou continua no funil.')">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                </div>
            @endforeach

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Nova entrada</h4>
                    <p class="card-subtitle">Formulário de uma landing (isca) ou compra de um produto (pós-venda).</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.funil-gatilhos.store', $funil) }}">
                        @csrf
                        @include('admin.funis._gatilho-campos', ['gatilho' => new \App\Models\FunilGatilho(['ativo' => true]), 'prefixo' => 'gatilho-novo'])
                        <button type="submit" class="btn btn-success mt-3">Adicionar entrada</button>
                    </form>
                </div>
            </div>

            @if ($formulario)
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Ligar o formulário da landing</h4>
                        <p class="card-subtitle">No bloco HTML do WordPress, troque o <code>action</code> do formulário e mantenha os campos <code>nome</code> e <code>whatsApp</code>.</p>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" rows="3" readonly onclick="this.select()"><form method="post" action="{{ route('funil.formulario') }}" onsubmit="return validarContato()">
<input type="hidden" name="funil" value="{{ $funil->slug }}"/></textarea>
                        <small class="form-text text-muted">Telefone inválido volta para a landing com <code>?erro=telefone</code>; válido vai para a página de obrigado.</small>
                    </div>
                </div>
            @endif
        </div>

        {{-- CONTATOS --}}
        <div class="tab-pane" id="contatos">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Últimos contatos</h4>
                    <p class="card-subtitle">Os 50 mais recentes neste funil.</p>
                </div>
                <div class="table-responsive">
                    <table class="table m-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Contato</th>
                                <th>Entrou por</th>
                                <th>Situação</th>
                                <th>Bloco atual</th>
                                <th>Próximo envio</th>
                                <th>Entrou em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inscricoes as $inscricao)
                                <tr>
                                    <td>
                                        <strong>{{ $inscricao->contato->nome ?: '—' }}</strong>
                                        <br><small class="text-muted">{{ $inscricao->contato->telefone }}</small>
                                    </td>
                                    <td>{{ $inscricao->gatilho?->descricao() ?? '—' }}</td>
                                    <td><span class="badge {{ $inscricao->status->badge() }}">{{ $inscricao->status->label() }}</span></td>
                                    <td>{{ $inscricao->blocoAtual?->product?->name ?? '—' }}</td>
                                    <td>{{ $inscricao->proximo_envio_em?->format('d/m H:i') ?? '—' }}</td>
                                    <td>{{ $inscricao->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Ninguém entrou neste funil ainda.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- CONFIGURAÇÕES --}}
        <div class="tab-pane" id="configuracoes">
            <form method="POST" action="{{ route('admin.funis.update', $funil) }}">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-body">
                        @include('admin.funis._form')
                        <button type="submit" class="btn btn-success mt-3">Salvar configurações</button>
                    </div>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.funis.destroy', $funil) }}" class="mb-4" onsubmit="return confirm('Excluir este funil, os blocos e as mensagens?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Excluir funil</button>
                <small class="text-muted ml-2">Funis com contatos não podem ser excluídos, só desativados.</small>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/dragula/dragula.min.js') }}"></script>
    <script>
        (function ($) {
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            var $status = $('#ordem-status');

            // Tabs and open block follow the URL hash (#entrada, #bloco-12...).
            function mostrarDoHash() {
                var hash = window.location.hash;
                if (/^#bloco-\d+$/.test(hash)) {
                    $('a[href="#blocos"]').tab('show');
                    $(hash.replace('#bloco-', '#bloco-corpo-')).collapse('show');
                    var alvo = document.querySelector(hash);
                    if (alvo) {
                        alvo.scrollIntoView();
                    }
                } else if (hash && $('a[data-toggle="tab"][href="' + hash + '"]').length) {
                    $('a[data-toggle="tab"][href="' + hash + '"]').tab('show');
                }
            }
            mostrarDoHash();
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (event) {
                history.replaceState(null, '', event.target.getAttribute('href'));
            });
            $('.collapse[id^="bloco-corpo-"]').on('shown.bs.collapse', function () {
                history.replaceState(null, '', '#' + this.id.replace('bloco-corpo-', 'bloco-'));
            });

            function salvarOrdem(url, ids) {
                $status.removeClass('text-success text-danger').addClass('text-muted').text('Salvando a ordem...');

                return fetch(url, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ ids: ids })
                }).then(function (response) {
                    return response.json().then(function (body) {
                        if (!response.ok) {
                            throw new Error(body.message || 'Erro ao salvar.');
                        }
                        window.location.reload();
                    });
                }).catch(function (error) {
                    $status.removeClass('text-muted').addClass('text-danger').text(error.message);
                });
            }

            function ids(container, selector) {
                return Array.prototype.map.call(container.querySelectorAll(':scope > ' + selector), function (el) {
                    return Number(el.dataset.id);
                });
            }

            var listaBlocos = document.getElementById('lista-blocos');
            if (listaBlocos) {
                dragula([listaBlocos], {
                    moves: function (el, source, handle) {
                        return !!handle.closest('.js-arrastar-bloco') && el.classList.contains('js-bloco');
                    }
                }).on('drop', function () {
                    salvarOrdem(listaBlocos.dataset.url, ids(listaBlocos, '.js-bloco'));
                });
            }

            document.querySelectorAll('.js-lista-mensagens').forEach(function (lista) {
                dragula([lista], {
                    moves: function (el, source, handle) {
                        return !!handle.closest('.js-arrastar-mensagem') && el.classList.contains('js-mensagem');
                    },
                    accepts: function (el, target, source) {
                        return target === source;
                    }
                }).on('drop', function () {
                    salvarOrdem(lista.dataset.url, ids(lista, '.js-mensagem'));
                });
            });

            // WhatsApp preview, same rules as the real message.
            function previa(mensagem) {
                var bloco = mensagem.closest('.js-bloco').dataset;
                var oferta = mensagem.querySelector('.js-previa-oferta');
                var escolhida = oferta.options[oferta.selectedIndex];
                var preco = escolhida && escolhida.dataset.preco ? escolhida.dataset.preco : bloco.preco;
                var link = escolhida && escolhida.dataset.link ? escolhida.dataset.link : bloco.link;
                var texto = mensagem.querySelector('.js-previa-texto').value
                    .split('{primeiro_nome}').join('Maria')
                    .split('{produto}').join(bloco.produto)
                    .split('{preco}').join(preco)
                    .split('{link}').join(link + (link.indexOf('?') >= 0 ? '&' : '?') + 'fi=123')
                    .split(', !').join('!');

                mensagem.querySelector('.js-previa').textContent = texto;
            }
            $(document).on('input change', '.js-previa-texto, .js-previa-oferta', function () {
                previa(this.closest('.js-mensagem'));
            });

            // Entry fields that only apply to one trigger type.
            function tipoGatilho(box) {
                var tipo = box.querySelector('.js-gatilho-tipo').value;
                box.querySelectorAll('[class*="js-so-"]').forEach(function (campo) {
                    campo.hidden = !campo.classList.contains('js-so-' + tipo);
                });
            }
            document.querySelectorAll('.js-gatilho').forEach(function (box) {
                tipoGatilho(box);
                box.querySelector('.js-gatilho-tipo').addEventListener('change', function () {
                    tipoGatilho(box);
                });
            });
        })(jQuery);
    </script>
@endpush
