@extends('layouts.app')

@section('title', 'Editar oferta')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produtos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.products.edit', $offer->product) }}">{{ $offer->product->name }}</a></li>
    <li class="breadcrumb-item active">{{ $offer->name }}</li>
@endsection

@section('content')
    <h1 class="h2">{{ $offer->name }}</h1>

    <form method="POST" action="{{ route('admin.offers.update', $offer) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                @include('admin.offers._fields', ['product' => $offer->product])
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.products.edit', $offer->product) }}#ofertas" class="btn btn-white mr-2">Cancelar</a>
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </div>
    </form>

    <div class="alert alert-light border-1 border-left-3 border-left-warning">
        Mudar o código altera o link. Links que você já mandou no WhatsApp com o código antigo deixam de funcionar.
    </div>
@endsection
