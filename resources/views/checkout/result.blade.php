@extends('layouts.checkout')

@section('title', 'Seu pedido')

@if ($order->status === \App\Enums\OrderStatus::Approved)
    @push('head')
        @include('partials.facebook-pixel', [
            'product' => $order->product,
            'events' => [['Purchase', ['value' => (float) $order->amount, 'currency' => 'BRL']]],
            'onceKey' => 'fb-purchase-'.$order->id,
        ])
    @endpush
@endif

@section('top')
    <div class="container checkout-banner text-center">
        <img src="{{ asset('assets/images/logo/balloon.png') }}" alt="{{ config('app.name') }}" style="width: 64px; border-radius: 0">
        <div class="h4 mt-2 mb-0">{{ config('app.name') }}</div>
    </div>
@endsection

@section('content')
    <form onsubmit="return false">
        <div class="products text-center">
            @switch($order->status)
                @case(\App\Enums\OrderStatus::Approved)
                    <i class="fas fa-check-circle text-success" style="font-size: 64px"></i>
                    <h1 class="h3 mt-3">Pagamento aprovado!</h1>
                    <p class="text-muted">
                        Enviamos para <strong>{{ $order->user->email }}</strong> o link para você criar sua senha e acessar.
                        Confira também a caixa de spam.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-success">Acessar a área do aluno</a>
                    @break

                @case(\App\Enums\OrderStatus::Pending)
                    <i class="far fa-clock text-warning" style="font-size: 64px"></i>
                    <h1 class="h3 mt-3">Aguardando pagamento</h1>
                    <p class="text-muted mb-0">
                        Assim que o Mercado Pago confirmar (Pix costuma ser na hora, boleto em até 3 dias úteis),
                        você recebe o acesso em <strong>{{ $order->user->email }}</strong>.
                    </p>
                    @break

                @default
                    <i class="fas fa-times-circle text-danger" style="font-size: 64px"></i>
                    <h1 class="h3 mt-3">Pagamento não concluído</h1>
                    <p class="text-muted">O pagamento não foi aprovado. Você pode tentar novamente com outro meio de pagamento.</p>
                    <a href="{{ $order->offer?->isAvailable() ? $order->offer->checkoutUrl() : route('checkout.show', $order->product) }}" class="btn btn-success">Tentar novamente</a>
            @endswitch
        </div>

        <br>

        <div class="products">
            @foreach ($order->items as $item)
                <div class="item">
                    <span class="price">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</span>
                    <strong>{{ $item->description }}</strong>
                </div>
            @endforeach
            <div class="total">
                Total
                <span class="price">R$ {{ number_format($order->amount, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="card-details">
            @include('checkout._help', ['class' => 'mb-0'])
        </div>
    </form>
@endsection
