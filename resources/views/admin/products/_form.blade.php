{{-- Shared product fields. Expects $product and $courses. --}}
@php($selectedCourses = collect(old('course_ids', $product->exists ? $product->courses->pluck('id')->all() : []))->map(fn ($id) => (int) $id))

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Dados do produto</h4>
            </div>
            <div class="card-body">
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

                <div class="form-group">
                    <label class="form-label" for="hotmart_product_id">ID do produto na Hotmart</label>
                    <input type="text" id="hotmart_product_id" name="hotmart_product_id" value="{{ old('hotmart_product_id', $product->hotmart_product_id) }}"
                           class="form-control @error('hotmart_product_id') is-invalid @enderror" placeholder="Ex.: 1234567">
                    <small class="form-text text-muted">Preencha se este produto também é vendido na Hotmart: o webhook usa esse ID para liberar o acesso.</small>
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

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Order bump</h4>
                <p class="card-subtitle">Oferta extra com caixinha de marcar no checkout deste produto.</p>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label class="form-label" for="order_bump_product_id">Produto oferecido</label>
                        <select id="order_bump_product_id" name="order_bump_product_id" class="form-control custom-select @error('order_bump_product_id') is-invalid @enderror">
                            <option value="">Sem order bump</option>
                            @foreach ($bumpCandidates as $candidate)
                                <option value="{{ $candidate->id }}" @selected((int) old('order_bump_product_id', $product->order_bump_product_id) === $candidate->id)>{{ $candidate->name }}</option>
                            @endforeach
                        </select>
                        @error('order_bump_product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label class="form-label" for="order_bump_price">Preço no bump (R$)</label>
                        <input type="text" id="order_bump_price" name="order_bump_price" inputmode="decimal"
                               value="{{ old('order_bump_price', $product->order_bump_price !== null ? number_format($product->order_bump_price, 2, ',', '') : '') }}"
                               class="form-control @error('order_bump_price') is-invalid @enderror" placeholder="12,00">
                        @error('order_bump_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="order_bump_headline">Chamada ao lado da caixinha</label>
                    <input type="text" id="order_bump_headline" name="order_bump_headline" maxlength="500"
                           value="{{ old('order_bump_headline', $product->order_bump_headline) }}"
                           class="form-control @error('order_bump_headline') is-invalid @enderror"
                           placeholder="Quero aproveitar essa oportunidade única e levar mais 21 vídeos de esculturas!">
                    <small class="form-text text-muted">Coloque entre asteriscos o trecho que deve ficar com fundo amarelo, ex.: *OPORTUNIDADE ÚNICA*. O "De R$ X por R$ Y" aparece automaticamente. O produto oferecido precisa estar ativo.</small>
                    @error('order_bump_headline')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
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

        @if ($product->exists)
            <div class="card">
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

        <div class="card">
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
