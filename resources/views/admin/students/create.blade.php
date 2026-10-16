@extends('layouts.app')

@section('title', 'Novo aluno')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Alunos</a></li>
    <li class="breadcrumb-item active">Novo aluno</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.students.store') }}">
        @csrf

        <div class="media align-items-center mb-headings">
            <div class="media-body">
                <h1 class="h2">Novo aluno</h1>
            </div>
            <div class="media-right">
                <button type="submit" class="btn btn-success">Cadastrar</button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="name">Nome</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-7">
                                <label class="form-label" for="email">E-mail</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-control @error('email') is-invalid @enderror">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-5">
                                <label class="form-label" for="phone">Telefone / WhatsApp</label>
                                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="password">Senha</label>
                            <input type="text" id="password" name="password" autocomplete="off" class="form-control @error('password') is-invalid @enderror">
                            <small class="form-text text-muted">Deixe vazio para o aluno receber um e-mail e criar a própria senha.</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Liberar cursos</h4>
                        <p class="card-subtitle">Acesso vitalício. Para dar prazo, ajuste depois no cadastro.</p>
                    </div>
                    <ul class="list-group list-group-fit">
                        @forelse ($courses as $course)
                            <li class="list-group-item">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="course-{{ $course->id }}" name="course_ids[]" value="{{ $course->id }}"
                                           @checked(in_array($course->id, old('course_ids', [])))>
                                    <label class="custom-control-label" for="course-{{ $course->id }}">{{ $course->title }}</label>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Nenhum curso cadastrado.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </form>
@endsection
