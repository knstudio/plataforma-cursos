{{-- Shared product fields. Expects $product and $courses. --}}
@php
    $deliveredByHotmart = (bool) old('delivered_by_hotmart', $product->delivered_by_hotmart);
    $selectedCourses = collect(old('course_ids', $product->exists ? $product->courses->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Dados do produto</h4>
            </div>
            <div class="card-body">
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="delivered_by_hotmart" name="delivered_by_hotmart" value="1"
                           @checked($deliveredByHotmart)>
                    <label class="custom-control-label" for="delivered_by_hotmart"><strong>Vendido e entregue pela Hotmart</strong></label>
                    <small class="form-text text-muted">Marque para cursos que a Hotmart vende e entrega (checkout e área de membros da Hotmart).
                        Aqui fica só o registro das compras, para o WhatsApp saber quem comprou: sem curso, sem checkout e sem e-mail de acesso.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Nome</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                           class="form-control @error('name') is-invalid @enderror" placeholder="Ex.: Treinamento Essencial + Bônus">
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="slug">Identificador (slug)</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $product->slug) }}"
                           class="form-control @error('slug') is-invalid @enderror" placeholder="gerado a partir do nome">
                    <small class="form-text text-muted">Vai ser usado no link de compra do Mercado Pago.</small>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="js-own-sale">
                <div class="form-group">
                    <label class="form-label" for="description">Descrição</label>
                    <textarea id="description" name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="form-label" for="price">Preço (R$)</label>
                        <input type="text" id="price" name="price" inputmode="decimal" required
                               value="{{ old('price', $product->price !== null ? number_format($product->price, 2, ',', '') : '') }}"
                               class="form-control @error('price') is-invalid @enderror" placeholder="97,00">
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label class="form-label" for="access_days">Dias de acesso</label>
                        <input type="number" id="access_days" name="access_days" min="1" value="{{ old('access_days', $product->access_days) }}"
                               class="form-control @error('access_days') is-invalid @enderror" placeholder="Vazio = vitalício">
                        @error('access_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="hotmart_product_id">ID do produto na Hotmart</label>
                    <input type="text" id="hotmart_product_id" name="hotmart_product_id" value="{{ old('hotmart_product_id', $product->hotmart_product_id) }}"
                           class="form-control @error('hotmart_product_id') is-invalid @enderror" placeholder="Ex.: 1234567">
                    <small class="form-text text-muted">Preencha se este produto é vendido na Hotmart: o webhook usa esse ID para registrar a compra (e liberar o acesso, se não for entregue pela Hotmart).</small>
                    @error('hotmart_product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $product->is_active))>
                    <label class="custom-control-label" for="is_active">Ativo (disponível para venda)</label>
                </div>
            </div>
        </div>

        @php
            $bumpRows = old('bumps', $product->exists
                ? $product->orderBumps->map(fn ($bump) => [
                    'id' => $bump->id,
                    'bump_product_id' => $bump->bump_product_id,
                    'price' => number_format((float) $bump->price, 2, ',', ''),
                    'headline' => $bump->headline,
                ])->all()
                : []);
        @endphp

        <div class="card js-own-sale" id="order-bumps">
            <div class="card-header d-flex align-items-center">
                <div class="flex">
                    <h4 class="card-title">Order bumps</h4>
                    <p class="card-subtitle">Ofertas extras com caixinha de marcar no checkout deste produto. O cliente pode marcar quantas quiser.</p>
                </div>
                <button type="button" class="btn btn-success ml-3 js-add-bump">
                    <i class="material-icons btn__icon--left">add</i> Adicionar order bump
                </button>
            </div>
            <div class="card-body">
                <div class="js-bump-rows">
                    @foreach ($bumpRows as $index => $row)
                        @include('admin.products._bump-row', ['index' => $index, 'row' => $row])
                    @endforeach
                </div>
                <p class="text-muted mb-0 js-bump-empty" @if (count($bumpRows) > 0) hidden @endif>Nenhum order bump. Clique em "Adicionar order bump".</p>
                <small class="form-text text-muted">
                    Na chamada, coloque entre asteriscos o trecho que fica com fundo amarelo, ex.: *OPORTUNIDADE ÚNICA*.
                    O "De R$ X por R$ Y" aparece sozinho. O produto oferecido precisa estar ativo.
                    Nas ofertas (links de campanha) dá para mudar o preço de cada bump ou tirá-lo.
                </small>
            </div>

            <template class="js-bump-template">
                @include('admin.products._bump-row', ['index' => '__INDEX__', 'row' => []])
            </template>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card js-own-sale">
            <div class="card-header">
                <h4 class="card-title">Aparência do checkout</h4>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="checkout_banner_upload">Banner</label>
                    <img src="{{ $product->checkoutBannerUrl() }}" alt="Banner" class="img-fluid rounded mb-2">
                    @if ($product->checkout_banner)
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="remove_checkout_banner" name="remove_checkout_banner" value="1">
                            <label class="custom-control-label" for="remove_checkout_banner">Voltar ao banner padrão</label>
                        </div>
                    @endif
                    <input type="file" id="checkout_banner_upload" name="checkout_banner_upload" accept="image/*"
                           class="form-control-file @error('checkout_banner_upload') is-invalid @enderror">
                    <small class="form-text text-muted">1000×400 px fica igual ao atual. Até 2 MB.</small>
                    @error('checkout_banner_upload')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="checkout_timer_minutes">Contador "oferta por tempo limitado"</label>
                    <div class="input-group" style="max-width: 180px">
                        <input type="number" min="0" max="1440" id="checkout_timer_minutes" name="checkout_timer_minutes"
                               value="{{ old('checkout_timer_minutes', $product->checkout_timer_minutes) }}"
                               class="form-control @error('checkout_timer_minutes') is-invalid @enderror">
                        <div class="input-group-append"><span class="input-group-text">min</span></div>
                    </div>
                    <small class="form-text text-muted">0 ou vazio esconde o contador.</small>
                    @error('checkout_timer_minutes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group mb-0 mt-3">
                    <label class="form-label" for="facebook_pixel_ids">Pixels extras deste produto</label>
                    <input type="text" id="facebook_pixel_ids" name="facebook_pixel_ids"
                           value="{{ old('facebook_pixel_ids', $product->facebook_pixel_ids) }}"
                           class="form-control @error('facebook_pixel_ids') is-invalid @enderror" placeholder="Ex.: 1234567890">
                    <small class="form-text text-muted">Opcional. Disparam só no checkout deste produto, além dos pixels de <a href="{{ route('admin.settings.edit') }}">Configurações</a>. Separe vários por vírgula.</small>
                    @error('facebook_pixel_ids')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Aviso de compra (WhatsApp)</h4>
                <p class="card-subtitle">Quando uma compra deste produto é aprovada, a plataforma avisa esta URL com nome, telefone, e-mail e produto.</p>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label class="form-label" for="purchase_webhook_url">URL de aviso de compra</label>
                    <input type="url" id="purchase_webhook_url" name="purchase_webhook_url"
                           value="{{ old('purchase_webhook_url', $product->purchase_webhook_url) }}"
                           class="form-control @error('purchase_webhook_url') is-invalid @enderror" placeholder="https://.../api/flows/hook/...">
                    <small class="form-text text-muted">Opcional. Cole a URL Webhook do fluxo de pós-venda do GoatFlow (bloco Início). Vale também quando o produto é comprado como order bump.</small>
                    @error('purchase_webhook_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        @if ($product->exists && isset($integrationToken))
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Para o GoatFlow: "comprou este produto?"</h4>
                    <p class="card-subtitle">Copie cada campo para o bloco <strong>Requisição HTTP</strong> do fluxo.</p>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Método</label>
                        <input type="text" readonly class="form-control" value="GET" onclick="this.select()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">URL</label>
                        <input type="text" readonly class="form-control" onclick="this.select()"
                               value="{{ route('api.funil.comprou') }}?produto={{ $product->slug }}&telefone={telefone}">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-5">
                            <label class="form-label">Header: Nome</label>
                            <input type="text" readonly class="form-control" value="Authorization" onclick="this.select()">
                        </div>
                        <div class="form-group col-7">
                            <label class="form-label">Header: Valor</label>
                            <input type="text" readonly class="form-control" value="Bearer {{ $integrationToken }}" onclick="this.select()">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-5 mb-0">
                            <label class="form-label">Campo de decisão</label>
                            <input type="text" readonly class="form-control" value="comprou" onclick="this.select()">
                        </div>
                        <div class="form-group col-7 mb-0">
                            <label class="form-label">Valores = saídas</label>
                            <input type="text" readonly class="form-control" value="true, false" onclick="this.select()">
                        </div>
                    </div>
                    <small class="form-text text-muted">Saída <strong>true</strong>: já comprou. <strong>false</strong>: ainda não comprou. Clique num campo para selecionar o texto.</small>
                </div>
            </div>
        @endif

        @if ($product->exists)
            <div class="card js-own-sale">
                <div class="card-header">
                    <h4 class="card-title">Link do checkout</h4>
                    <p class="card-subtitle">Use no botão de compra da página de vendas.</p>
                </div>
                <div class="card-body">
                    <input type="text" readonly class="form-control mb-2" value="{{ route('checkout.show', $product) }}" onclick="this.select()">
                    <a href="{{ route('checkout.show', $product) }}" target="_blank" class="btn btn-light btn-block">Abrir checkout</a>
                </div>
            </div>
        @endif

        <div class="card js-own-sale">
            <div class="card-header">
                <h4 class="card-title">Cursos liberados</h4>
                <p class="card-subtitle">Marque o que o comprador recebe.</p>
            </div>
            <ul class="list-group list-group-fit">
                @forelse ($courses as $course)
                    <li class="list-group-item">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="course-{{ $course->id }}" name="course_ids[]" value="{{ $course->id }}"
                                   @checked($selectedCourses->contains($course->id))>
                            <label class="custom-control-label" for="course-{{ $course->id }}">{{ $course->title }}</label>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Cadastre um curso primeiro.</li>
                @endforelse
            </ul>
            @error('course_ids')
                <div class="card-body pt-0"><div class="invalid-feedback">{{ $message }}</div></div>
            @enderror
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // "Vendido e entregue pela Hotmart" hides (and stops sending) what only matters for our own checkout.
        (function () {
            var toggle = document.getElementById('delivered_by_hotmart');
            var apply = function () {
                document.querySelectorAll('.js-own-sale').forEach(function (section) {
                    section.hidden = toggle.checked;
                    section.querySelectorAll('input, select, textarea, button').forEach(function (field) {
                        field.disabled = toggle.checked;
                    });
                });
            };

            toggle.addEventListener('change', apply);
            apply();
        })();

        (function () {
            var card = document.getElementById('order-bumps');
            var rows = card.querySelector('.js-bump-rows');
            var empty = card.querySelector('.js-bump-empty');
            var template = card.querySelector('.js-bump-template');
            var refresh = function () {
                empty.hidden = rows.children.length > 0;
            };

            card.querySelector('.js-add-bump').addEventListener('click', function () {
                var index = 'n' + Date.now();
                rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, index));
                refresh();
            });

            // Up/down/remove buttons; the order of the rows is the order on the checkout.
            rows.addEventListener('click', function (event) {
                var button = event.target.closest('[data-bump-action]');

                if (!button) {
                    return;
                }

                var row = button.closest('.js-bump-row');
                var action = button.dataset.bumpAction;

                if (action === 'remove' && confirm('Tirar este order bump do produto?')) {
                    row.remove();
                } else if (action === 'up' && row.previousElementSibling) {
                    rows.insertBefore(row, row.previousElementSibling);
                } else if (action === 'down' && row.nextElementSibling) {
                    rows.insertBefore(row.nextElementSibling, row);
                }

                refresh();
            });
        })();
    </script>
@endpush
