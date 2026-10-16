@extends('layouts.app')

@section('title', $student->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Alunos</a></li>
    <li class="breadcrumb-item active">{{ $student->name }}</li>
@endsection

@section('content')
    <div class="media align-items-center mb-headings">
        <div class="media-body">
            <h1 class="h2 mb-0">{{ $student->name }}</h1>
            <small class="text-muted">Cadastrado em {{ $student->created_at->format('d/m/Y H:i') }}</small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Cursos liberados</h4>
                </div>
                <div class="table-responsive">
                    <table class="table m-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Curso</th>
                                <th>Origem</th>
                                <th>Expira</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($student->enrollments as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->course->title }}</td>
                                    <td>
                                        {{ $enrollment->source->label() }}
                                        @if ($enrollment->order_id)
                                            <a href="{{ route('admin.orders.show', $enrollment->order_id) }}">#{{ $enrollment->order_id }}</a>
                                        @endif
                                    </td>
                                    <td>{{ $enrollment->expires_at?->format('d/m/Y') ?? 'Vitalício' }}</td>
                                    <td>
                                        @if ($enrollment->revoked_at)
                                            <span class="badge badge-danger">Bloqueado</span>
                                        @elseif (! $enrollment->isActive())
                                            <span class="badge badge-warning">Expirado</span>
                                        @else
                                            <span class="badge badge-success">Ativo</span>
                                        @endif
                                    </td>
                                    <td class="text-right" style="white-space: nowrap">
                                        @if ($enrollment->revoked_at)
                                            <form method="POST" action="{{ route('admin.enrollments.restore', $enrollment) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-white btn-sm" title="Restaurar acesso"><i class="material-icons">lock_open</i></button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.enrollments.revoke', $enrollment) }}" class="d-inline"
                                                  onsubmit="return confirm('Bloquear o acesso a este curso?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-white btn-sm" title="Bloquear acesso"><i class="material-icons">lock</i></button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.enrollments.destroy', $enrollment) }}" class="d-inline"
                                              onsubmit="return confirm('Remover esta matrícula?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-white btn-sm text-danger" title="Remover"><i class="material-icons">delete</i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Nenhum curso liberado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($availableCourses->isNotEmpty())
                    <div class="card-body border-top">
                        <form method="POST" action="{{ route('admin.enrollments.store', $student) }}" class="form-row align-items-end">
                            @csrf
                            <div class="form-group col-md-6 mb-md-0">
                                <label class="form-label" for="course_id">Liberar curso</label>
                                <select id="course_id" name="course_id" class="form-control custom-select @error('course_id') is-invalid @enderror" required>
                                    @foreach ($availableCourses as $course)
                                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4 mb-md-0">
                                <label class="form-label" for="expires_at">Expira em (opcional)</label>
                                <input type="date" id="expires_at" name="expires_at" value="{{ old('expires_at') }}" class="form-control @error('expires_at') is-invalid @enderror">
                            </div>
                            <div class="form-group col-md-2 mb-0">
                                <button type="submit" class="btn btn-primary btn-block">Liberar</button>
                            </div>
                        </form>
                        @error('course_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @error('expires_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Pedidos</h4>
                </div>
                <div class="table-responsive">
                    <table class="table m-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Produto</th>
                                <th>Gateway</th>
                                <th>Status</th>
                                <th class="text-right">Valor</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($student->orders as $order)
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->id }}</a></td>
                                    <td>{{ $order->product->name }}</td>
                                    <td>{{ $order->gateway->label() }}</td>
                                    <td>@include('admin.orders._status', ['status' => $order->status])</td>
                                    <td class="text-right">R$ {{ number_format($order->amount, 2, ',', '.') }}</td>
                                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nenhum pedido.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <form method="POST" action="{{ route('admin.students.update', $student) }}">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Dados</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="name">Nome</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $student->name) }}" required class="form-control @error('name') is-invalid @enderror">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $student->email) }}" required class="form-control @error('email') is-invalid @enderror">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Telefone / WhatsApp</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $student->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="role">Perfil</label>
                            <select id="role" name="role" class="form-control custom-select @error('role') is-invalid @enderror">
                                @foreach (\App\Enums\UserRole::cases() as $role)
                                    <option value="{{ $role->value }}" @selected(old('role', $student->role->value) === $role->value)>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="password">Nova senha</label>
                            <input type="text" id="password" name="password" autocomplete="off" class="form-control @error('password') is-invalid @enderror" placeholder="Deixe vazio para não alterar">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-success">Salvar</button>
                    </div>
                </div>
            </form>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.students.password-link', $student) }}">
                        @csrf
                        <button type="submit" class="btn btn-light btn-block">
                            <i class="material-icons btn__icon--left">mail_outline</i> Enviar link para criar/redefinir senha
                        </button>
                    </form>
                </div>
            </div>

            @unless ($student->is(auth()->user()))
                <div class="card border-danger">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                              onsubmit="return confirm('Excluir {{ addslashes($student->name) }}?\n\nApaga também os acessos, {{ $student->orders->count() }} pedido(s), o progresso e as perguntas. Não dá para desfazer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-block">
                                <i class="material-icons btn__icon--left">delete</i> Excluir aluno
                            </button>
                        </form>
                        <small class="text-muted d-block mt-2">
                            Apaga o aluno com acessos, pedidos, progresso e perguntas.
                            Para só tirar o acesso a um curso, use o cadeado na lista de cursos acima.
                        </small>
                    </div>
                </div>
            @endunless
        </div>
    </div>
@endsection
