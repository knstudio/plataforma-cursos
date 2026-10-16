@extends('layouts.app')

@section('title', 'Painel')

@section('breadcrumb')
    <li class="breadcrumb-item active">Painel</li>
@endsection

@section('content')
    <h1 class="h2">Painel</h1>

    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body flex-row align-items-center">
                <i class="material-icons text-primary mr-3" style="font-size: 36px">people</i>
                <div>
                    <div class="h2 mb-0">{{ $studentsCount }}</div>
                    <small class="text-muted text-uppercase">Alunos</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body flex-row align-items-center">
                <i class="material-icons text-primary mr-3" style="font-size: 36px">school</i>
                <div>
                    <div class="h2 mb-0">{{ $coursesCount }}</div>
                    <small class="text-muted text-uppercase">Cursos</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body flex-row align-items-center">
                <i class="material-icons text-success mr-3" style="font-size: 36px">shopping_cart</i>
                <div>
                    <div class="h2 mb-0">{{ $salesThisMonth }}</div>
                    <small class="text-muted text-uppercase">Vendas no mês</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body flex-row align-items-center">
                <i class="material-icons text-success mr-3" style="font-size: 36px">attach_money</i>
                <div>
                    <div class="h2 mb-0">R$ {{ number_format($revenueThisMonth, 2, ',', '.') }}</div>
                    <small class="text-muted text-uppercase">Faturamento no mês</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Últimos pedidos</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-nowrap m-0">
                <thead class="thead-light">
                    <tr>
                        <th>Aluno</th>
                        <th>Produto</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th class="text-right">Valor</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($latestOrders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->user->name }}</a><br><small class="text-muted">{{ $order->user->email }}</small></td>
                            <td>{{ $order->product->name }}</td>
                            <td>{{ $order->gateway->label() }}</td>
                            <td>@include('admin.orders._status', ['status' => $order->status])</td>
                            <td class="text-right">R$ {{ number_format($order->amount, 2, ',', '.') }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum pedido ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
