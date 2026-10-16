@extends('layouts.app')

@section('title', 'Minha conta')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Início</a></li>
    <li class="breadcrumb-item active">Minha conta</li>
@endsection

@section('content')
    <h1 class="h2">Minha conta</h1>

    <form method="POST" action="{{ route('account.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="list-group list-group-fit">
                <div class="list-group-item">
                    <div class="form-group m-0">
                        <div class="form-row">
                            <label for="name" class="col-md-3 col-form-label form-label">Nome</label>
                            <div class="col-md-9">
                                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                                       class="form-control @error('name') is-invalid @enderror" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="form-group m-0">
                        <div class="form-row">
                            <label for="email" class="col-md-3 col-form-label form-label">E-mail</label>
                            <div class="col-md-9">
                                <input id="email" type="email" value="{{ $user->email }}" class="form-control" disabled>
                                <small class="form-text text-muted">O e-mail é usado para liberar suas compras e não pode ser alterado aqui.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="form-group m-0">
                        <div class="form-row">
                            <label for="phone" class="col-md-3 col-form-label form-label">Telefone / WhatsApp</label>
                            <div class="col-md-9">
                                <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                                       class="form-control @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h4>Alterar senha</h4>
        <div class="card">
            <div class="list-group list-group-fit">
                <div class="list-group-item">
                    <div class="form-group m-0">
                        <div class="form-row">
                            <label for="current_password" class="col-md-3 col-form-label form-label">Senha atual</label>
                            <div class="col-md-9">
                                <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                                       class="form-control @error('current_password') is-invalid @enderror">
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="form-group m-0">
                        <div class="form-row">
                            <label for="password" class="col-md-3 col-form-label form-label">Nova senha</label>
                            <div class="col-md-9">
                                <input id="password" name="password" type="password" autocomplete="new-password"
                                       class="form-control @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="list-group-item">
                    <div class="form-group m-0">
                        <div class="form-row">
                            <label for="password_confirmation" class="col-md-3 col-form-label form-label">Confirme a nova senha</label>
                            <div class="col-md-9">
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success mb-4">Salvar</button>
    </form>
@endsection
