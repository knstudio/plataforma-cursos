@extends('layouts.guest')

@section('title', 'Entrar')

@section('content')
    <div class="card navbar-shadow">
        <div class="card-header text-center">
            <h4 class="card-title">Entrar</h4>
            <p class="card-subtitle">Acesse sua área de aluno</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">E-mail:</label>
                    <div class="input-group input-group-merge">
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
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
                    <label class="form-label" for="password">Senha:</label>
                    <div class="input-group input-group-merge">
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="form-control form-control-prepended @error('password') is-invalid @enderror" placeholder="Sua senha">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><span class="fas fa-key"></span></div>
                        </div>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember" value="1" @checked(old('remember'))>
                        <label class="custom-control-label" for="remember">Manter conectado</label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                </div>

                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="text-black-70" style="text-decoration: underline;">Esqueceu a senha?</a>
                </div>
            </form>
        </div>
    </div>
@endsection
