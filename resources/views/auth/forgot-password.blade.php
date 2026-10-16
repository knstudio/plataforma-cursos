@extends('layouts.guest')

@section('title', 'Recuperar senha')

@section('content')
    <div class="card navbar-shadow">
        <div class="card-header text-center">
            <h4 class="card-title">Recuperar senha</h4>
            <p class="card-subtitle">Enviaremos um link para você criar uma nova senha</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">E-mail:</label>
                    <div class="input-group input-group-merge">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="form-control form-control-prepended @error('email') is-invalid @enderror" placeholder="Seu e-mail">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><span class="far fa-envelope"></span></div>
                        </div>
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Enviar link</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center text-black-50">
            Lembrou a senha? <a href="{{ route('login') }}">Entrar</a>
        </div>
    </div>
@endsection
