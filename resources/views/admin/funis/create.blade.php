@extends('layouts.app')

@section('title', 'Novo funil')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.funis.index') }}">Funis WhatsApp</a></li>
    <li class="breadcrumb-item active">Novo</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.funis.store') }}">
        @csrf

        <div class="media align-items-center mb-headings">
            <div class="media-body">
                <h1 class="h2">Novo funil</h1>
            </div>
            <div class="media-right">
                <button type="submit" class="btn btn-success">Criar funil</button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @include('admin.funis._form')
            </div>
        </div>
    </form>
@endsection
