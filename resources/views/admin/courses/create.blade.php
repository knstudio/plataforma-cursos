@extends('layouts.app')

@section('title', 'Novo curso')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.courses.index') }}">Cursos</a></li>
    <li class="breadcrumb-item active">Novo curso</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="media align-items-center mb-headings">
            <div class="media-body">
                <h1 class="h2">Novo curso</h1>
            </div>
            <div class="media-right">
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                @include('admin.courses._form')
            </div>
            <div class="col-lg-4">
                <div class="card card-body text-muted">
                    Depois de salvar, você vai poder criar os módulos, adicionar as aulas e arrastar para ordenar.
                </div>
            </div>
        </div>
    </form>
@endsection
