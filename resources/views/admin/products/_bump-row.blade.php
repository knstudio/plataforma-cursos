{{-- One order bump on the product form. Expects $index, $row (id, bump_product_id, price, headline) and $bumpCandidates. --}}
@php($field = fn (string $name) => "bumps.{$index}.{$name}")

<div class="border rounded p-3 mb-3 js-bump-row">
    <input type="hidden" name="bumps[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">

    <div class="form-row">
        <div class="form-group col-md-7">
            <label class="form-label" for="bump-product-{{ $index }}">Produto oferecido</label>
            <select id="bump-product-{{ $index }}" name="bumps[{{ $index }}][bump_product_id]"
                    class="form-control custom-select @error($field('bump_product_id')) is-invalid @enderror">
                <option value="">Escolha o produto</option>
                @foreach ($bumpCandidates as $candidate)
                    <option value="{{ $candidate->id }}" @selected((int) ($row['bump_product_id'] ?? 0) === $candidate->id)>{{ $candidate->name }}</option>
                @endforeach
            </select>
            @error($field('bump_product_id'))
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group col-md-3">
            <label class="form-label" for="bump-price-{{ $index }}">Preço no bump (R$)</label>
            <input type="text" id="bump-price-{{ $index }}" name="bumps[{{ $index }}][price]" inputmode="decimal"
                   value="{{ $row['price'] ?? '' }}" placeholder="12,00"
                   class="form-control @error($field('price')) is-invalid @enderror">
            @error($field('price'))
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group col-md-2 text-right" style="white-space: nowrap">
            <label class="form-label d-block">&nbsp;</label>
            <button type="button" class="btn btn-white btn-sm" data-bump-action="up" title="Subir"><i class="material-icons">arrow_upward</i></button>
            <button type="button" class="btn btn-white btn-sm" data-bump-action="down" title="Descer"><i class="material-icons">arrow_downward</i></button>
            <button type="button" class="btn btn-white btn-sm text-danger" data-bump-action="remove" title="Remover"><i class="material-icons">delete</i></button>
        </div>
    </div>

    <div class="form-group mb-0">
        <label class="form-label" for="bump-headline-{{ $index }}">Chamada ao lado da caixinha</label>
        <input type="text" id="bump-headline-{{ $index }}" name="bumps[{{ $index }}][headline]" maxlength="500"
               value="{{ $row['headline'] ?? '' }}"
               placeholder="Quero aproveitar essa *OPORTUNIDADE ÚNICA* e levar mais 21 vídeos de esculturas!"
               class="form-control @error($field('headline')) is-invalid @enderror">
        @error($field('headline'))
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
