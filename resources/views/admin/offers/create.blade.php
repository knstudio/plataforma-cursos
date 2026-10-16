@extends('layouts.app')

@section('title', 'Nova oferta')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produtos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a></li>
    <li class="breadcrumb-item active">Nova oferta</li>
@endsection

@section('content')
    <h1 class="h2">Nova oferta · {{ $product->name }}</h1>
    <p class="text-muted">Preço padrão do produto: R$ {{ number_format($product->price, 2, ',', '.') }}</p>

    <form method="POST" action="{{ route('admin.offers.store', $product) }}">
        @csrf

        <div class="card">
            <div class="card-body">
                @include('admin.offers._fields')
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.products.edit', $product) }}#ofertas" class="btn btn-white mr-2">Cancelar</a>
                <button type="submit" class="btn btn-success">Criar oferta</button>
            </div>
        </div>
    </form>
@endsection
