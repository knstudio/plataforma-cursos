@extends('layouts.app')

@section('title', 'Editar produto')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produtos</a></li>
    <li class="breadcrumb-item active">{{ $product->name }}</li>
@endsection

@section('content')
    <form enctype="multipart/form-data" method="POST" action="{{ route('admin.products.update', $product) }}">
        @csrf
        @method('PUT')

        <div class="media align-items-center mb-headings">
            <div class="media-body">
                <h1 class="h2">{{ $product->name }}</h1>
            </div>
            <div class="media-right">
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </div>

        @include('admin.products._form')
    </form>

    @unless ($product->delivered_by_hotmart)
        @include('admin.offers._list')
    @endunless

    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mb-4"
          onsubmit="return confirm('Excluir este produto?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-outline-danger">Excluir produto</button>
        <small class="text-muted ml-2">Produtos com pedidos não podem ser excluídos, só desativados.</small>
    </form>
@endsection
