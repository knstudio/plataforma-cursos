@extends('layouts.guest')

@section('title', 'Nova senha')

@section('content')
    <div class="card navbar-shadow">
        <div class="card-header text-center">
            <h4 class="card-title">Criar nova senha</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('password.store') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label class="form-label" for="email">E-mail:</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required
                           class="form-control @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Nova senha:</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirme a nova senha:</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                           class="form-control">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Salvar nova senha</button>
                </div>
            </form>
        </div>
    </div>
@endsection
