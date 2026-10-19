{{-- Wait time as number + unit. Expects $campo (field prefix), $segundos and $id (unique html id); $form when the inputs sit outside their form. --}}
@php
    [$valorEspera, $unidadeEspera] = \App\Enums\EsperaUnidade::separar((int) $segundos);
    $formAttribute = isset($form) ? 'form='.$form : '';
@endphp
<div class="input-group input-group-sm" style="max-width: 190px">
    <input type="number" min="0" max="100000" class="form-control" id="{{ $id }}" name="{{ $campo }}_valor" value="{{ $valorEspera }}" required {{ $formAttribute }}>
    <div class="input-group-append">
        <select class="custom-select custom-select-sm" name="{{ $campo }}_unidade" aria-label="Unidade" {{ $formAttribute }}>
            @foreach (\App\Enums\EsperaUnidade::cases() as $unidade)
                <option value="{{ $unidade->value }}" @selected($unidade === $unidadeEspera)>{{ $unidade->value }}</option>
            @endforeach
        </select>
    </div>
</div>
