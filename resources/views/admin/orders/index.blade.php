@extends('layouts.app')

@section('title', 'Pedidos')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active">Pedidos</li>
@endsection

@section('content')
    <h1 class="h2">Pedidos</h1>

    <div class="card card-body border-left-3 border-left-primary navbar-shadow mb-4">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="form-row align-items-center">
            <div class="col-md-5 mb-2 mb-md-0">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="E-mail, nome ou ID da transação">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <select name="status" class="form-control custom-select">
                    <option value="">Todos os status</option>
                    @foreach (\App\Enums\OrderStatus::cases() as $option)
                        <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <select name="gateway" class="form-control custom-select">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\PaymentGateway::cases() as $option)
                        <option value="{{ $option->value }}" @selected($gateway === $option)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-nowrap m-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Aluno</th>
                        <th>Produto</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th class="text-right">Valor</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->id }}</a></td>
                            <td>
                                <a href="{{ route('admin.students.show', $order->user_id) }}">{{ $order->user->name }}</a><br>
                                <small class="text-muted">{{ $order->user->email }}</small>
                            </td>
                            <td>
                                {{ $order->product->name }}
                                @if ($order->offer)
                                    <br><small class="text-muted">Oferta: {{ $order->offer->name }}</small>
                                @endif
                            </td>
                            <td>{{ $order->gateway->label() }}</td>
                            <td>@include('admin.orders._status', ['status' => $order->status])</td>
                            <td class="text-right">R$ {{ number_format($order->amount, 2, ',', '.') }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum pedido encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $orders->links() }}
@endsection
