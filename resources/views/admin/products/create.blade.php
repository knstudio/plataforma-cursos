@extends('layouts.app')

@section('title', 'Novo produto')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Produtos</a></li>
    <li class="breadcrumb-item active">Novo produto</li>
@endsection

@section('content')
    <form enctype="multipart/form-data" method="POST" action="{{ route('admin.products.store') }}">
        @csrf

        <div class="media align-items-center mb-headings">
            <div class="media-body">
                <h1 class="h2">Novo produto</h1>
            </div>
            <div class="media-right">
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </div>

        @include('admin.products._form')
    </form>
@endsection
