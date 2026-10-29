@extends('layouts.app')

@section('title', 'Pedido #'.$order->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Pedidos</a></li>
    <li class="breadcrumb-item active">#{{ $order->id }}</li>
@endsection

@section('content')
    <div class="media align-items-center mb-headings">
        <div class="media-body">
            <h1 class="h2 mb-0">Pedido #{{ $order->id }} @include('admin.orders._status', ['status' => $order->status])</h1>
        </div>
        @if ($order->gateway === \App\Enums\PaymentGateway::MercadoPago && $order->external_id)
            <div class="media-right">
                <form method="POST" action="{{ route('admin.orders.sync', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-light">
                        <i class="material-icons btn__icon--left">sync</i> Consultar no Mercado Pago
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <ul class="list-group list-group-fit">
                    <li class="list-group-item d-flex"><span class="text-muted flex">Aluno</span>
                        <a href="{{ route('admin.students.show', $order->user) }}">{{ $order->user->name }}</a></li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">E-mail</span> {{ $order->user->email }}</li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">Telefone</span> {{ $order->user->phone ?? '—' }}</li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">Oferta</span> {{ $order->offer?->name ?? 'Checkout padrão' }}</li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">Gateway</span> {{ $order->gateway->label() }}</li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">Forma de pagamento</span> {{ $order->payment_method ?? '—' }}</li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">ID da transação</span> {{ $order->external_id ?? '—' }}</li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">Criado em</span> {{ $order->created_at->format('d/m/Y H:i:s') }}</li>
                    <li class="list-group-item d-flex"><span class="text-muted flex">Pago em</span> {{ $order->paid_at?->format('d/m/Y H:i:s') ?? '—' }}</li>
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Alterar status manualmente</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Use quando o gateway não avisou (reembolso, chargeback) ou para um pedido de teste.
                        Reembolsado, chargeback e cancelado tiram o acesso aos cursos deste pedido. Aprovado libera o acesso,
                        manda o e-mail ao aluno e o aviso de compra (WhatsApp).</p>
                    @foreach ($manualStatuses as $manualStatus)
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="d-inline"
                              onsubmit="return confirm('Marcar o pedido #{{ $order->id }} como {{ $manualStatus->label() }}?')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $manualStatus->value }}">
                            <button type="submit" class="btn {{ $manualStatus === \App\Enums\OrderStatus::Approved ? 'btn-success' : 'btn-outline-danger' }} mb-1">
                                Marcar como {{ $manualStatus->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Itens</h4>
                </div>
                <ul class="list-group list-group-fit">
                    @forelse ($order->items as $item)
                        <li class="list-group-item d-flex">
                            <span class="flex">
                                {{ $item->description }}
                                @if ($item->is_order_bump)
                                    <span class="badge badge-warning ml-1">Order bump</span>
                                @endif
                            </span>
                            R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Sem itens registrados.</li>
                    @endforelse
                    <li class="list-group-item d-flex">
                        <strong class="flex">Total</strong>
                        <strong>R$ {{ number_format($order->amount, 2, ',', '.') }}</strong>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Cursos liberados por este pedido</h4>
                </div>
                <ul class="list-group list-group-fit">
                    @forelse ($order->enrollments as $enrollment)
                        <li class="list-group-item d-flex">
                            <span class="flex">{{ $enrollment->course->title }}</span>
                            @if ($enrollment->isActive())
                                <span class="badge badge-success">Ativo</span>
                            @else
                                <span class="badge badge-danger">Inativo</span>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Nenhum curso liberado por este pedido.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Notificações recebidas</h4>
                </div>
                <ul class="list-group list-group-fit">
                    @forelse ($order->webhookCalls as $call)
                        <li class="list-group-item">
                            <div class="d-flex">
                                <strong class="flex">{{ $call->event ?? '—' }}</strong>
                                <small class="text-muted">{{ $call->created_at->format('d/m/Y H:i:s') }}</small>
                            </div>
                            <span class="badge {{ $call->result === 'processed' ? 'badge-success' : ($call->result === 'failed' ? 'badge-danger' : 'badge-light') }}">{{ $call->result }}</span>
                            <small class="text-muted">{{ $call->message }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Nenhuma notificação vinculada a este pedido.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Dados do pagamento (gateway)</h4>
                </div>
                <div class="card-body">
                    @if ($order->payload)
                        <pre class="mb-0" style="max-height: 480px; overflow: auto; font-size: 12px">{{ json_encode($order->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <p class="text-muted mb-0">Nenhum dado registrado.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
