{{-- Offer fields. Expects $offer (may be a new instance) and $product. Errors use the "offer" bag. --}}
@php($money = fn ($value) => $value !== null ? number_format((float) $value, 2, ',', '') : '')

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
    <div class="form-group col-md-3">
        <label class="form-label" for="offer_price">Preço (R$)</label>
        <input type="text" id="offer_price" name="price" inputmode="decimal" required
               value="{{ old('price', $money($offer->price)) }}"
               class="form-control @error('price', 'offer') is-invalid @enderror" placeholder="9,90">
        @error('price', 'offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-3">
        <label class="form-label" for="offer_bump_price">Preço do bump (R$)</label>
        <input type="text" id="offer_bump_price" name="order_bump_price" inputmode="decimal"
               value="{{ old('order_bump_price', $money($offer->order_bump_price)) }}"
               class="form-control @error('order_bump_price', 'offer') is-invalid @enderror"
               placeholder="{{ $product->order_bump_price !== null ? 'padrão: '.$money($product->order_bump_price) : 'sem bump' }}">
        @error('order_bump_price', 'offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-3">
        <label class="form-label" for="offer_expires_at">Válida até</label>
        <input type="date" id="offer_expires_at" name="expires_at" value="{{ old('expires_at', $offer->expires_at?->toDateString()) }}"
               class="form-control @error('expires_at', 'offer') is-invalid @enderror">
        @error('expires_at', 'offer')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-3">
        <label class="form-label d-block">&nbsp;</label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="offer_is_active" name="is_active" value="1"
                   @checked(old('is_active', $offer->is_active ?? true))>
            <label class="custom-control-label" for="offer_is_active">Ativa</label>
        </div>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="offer_without_bump" name="without_order_bump" value="1"
                   @checked(old('without_order_bump', $offer->without_order_bump))>
            <label class="custom-control-label" for="offer_without_bump">Sem order bump</label>
        </div>
    </div>
</div>
