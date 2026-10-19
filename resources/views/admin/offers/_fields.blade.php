{{-- Offer fields. Expects $offer (may be a new instance) and $product. Errors use the "offer" bag. --}}
@php
    $money = fn ($value) => $value !== null ? number_format((float) $value, 2, ',', '') : '';
@endphp

<div class="form-row">
    <div class="form-group col-md-5">
        <label class="form-label" for="offer_name">Nome da oferta</label>
        <input type="text" id="offer_name" name="name" value="{{ old('name', $offer->name) }}" required
               class="form-control @error('name', 'offer') is-invalid @enderror" placeholder="Ex.: Campanha WhatsApp outubro">
        @error('name', 'offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-7">
        <label class="form-label" for="offer_code">Código do link</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">/checkout/{{ $product->slug }}/</span>
            </div>
            <input type="text" id="offer_code" name="code" value="{{ old('code', $offer->code) }}"
                   class="form-control @error('code', 'offer') is-invalid @enderror" placeholder="whats-outubro">
        </div>
        <small class="form-text text-muted">Vazio = gerado a partir do nome.</small>
        @error('code', 'offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label class="form-label" for="offer_price">Preço (R$)</label>
        <input type="text" id="offer_price" name="price" inputmode="decimal" required
               value="{{ old('price', $money($offer->price)) }}"
               class="form-control @error('price', 'offer') is-invalid @enderror" placeholder="9,90">
        @error('price', 'offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label class="form-label" for="offer_expires_at">Válida até</label>
        <input type="date" id="offer_expires_at" name="expires_at" value="{{ old('expires_at', $offer->expires_at?->toDateString()) }}"
               class="form-control @error('expires_at', 'offer') is-invalid @enderror">
        @error('expires_at', 'offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label class="form-label d-block">&nbsp;</label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="offer_is_active" name="is_active" value="1"
                   @checked(old('is_active', $offer->is_active ?? true))>
            <label class="custom-control-label" for="offer_is_active">Ativa</label>
        </div>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="offer_without_bump" name="without_order_bump" value="1"
                   @checked(old('without_order_bump', $offer->without_order_bump))>
            <label class="custom-control-label" for="offer_without_bump">Sem nenhum order bump</label>
        </div>
    </div>
</div>

@if ($product->orderBumps->isNotEmpty())
    <h5 class="mt-2 mb-1">Order bumps nesta oferta</h5>
    <p class="text-muted small mb-2">Deixe o preço vazio para usar o preço padrão do produto. Marque "Tirar" para o bump não aparecer neste link.</p>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Order bump</th>
                    <th class="text-right">Preço padrão</th>
                    <th style="width: 170px">Preço nesta oferta (R$)</th>
                    <th class="text-center">Tirar</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($product->orderBumps as $bump)
                    @php
                        $override = $offer->exists ? $offer->bumpOverride($bump) : null;
                        $field = "bumps.{$bump->id}";
                    @endphp
                    <tr>
                        <td>{{ $bump->bumpProduct?->name }}</td>
                        <td class="text-right">R$ {{ $money($bump->price) }}</td>
                        <td>
                            <input type="text" inputmode="decimal" name="bumps[{{ $bump->id }}][price]"
                                   value="{{ old($field.'.price', $money($override?->price)) }}" placeholder="{{ $money($bump->price) }}"
                                   class="form-control form-control-sm @error($field.'.price', 'offer') is-invalid @enderror">
                            @error($field.'.price', 'offer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </td>
                        <td class="text-center">
                            <div class="custom-control custom-checkbox d-inline-block">
                                <input type="checkbox" class="custom-control-input" id="offer_bump_removed_{{ $bump->id }}"
                                       name="bumps[{{ $bump->id }}][removed]" value="1"
                                       @checked(old($field.'.removed', $override?->is_removed))>
                                <label class="custom-control-label" for="offer_bump_removed_{{ $bump->id }}"></label>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
