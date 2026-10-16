<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') - {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css?family=Montserrat&display=swap" rel="stylesheet">
    {{-- Plain Bootstrap 4.0 (as the original checkout), not the Learnplus theme. --}}
    <link type="text/css" href="{{ asset('assets/vendor/bootstrap-4.0.0.min.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/css/fontawesome.css') }}" rel="stylesheet">
    <link type="text/css" href="{{ asset('assets/css/checkout.css') }}" rel="stylesheet">

    @stack('head')
</head>
<body class="checkout-page">

    @yield('top')

    <main class="page payment-page">
        <section class="payment-form dark">
            <div class="container">
                <div class="mx-auto pt-3" style="max-width: 800px">
                    @if (session('status'))
                        <div class="alert alert-success mb-0" role="alert">{{ session('status') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger mb-0" role="alert">{{ session('error') }}</div>
                    @endif
                </div>

                @yield('content')
            </div>

            <div class="container" style="max-width: 800px">
                <div class="row">
                    <div class="col-sm">
                        <div class="checkout-badge">
                            <i class="fas fa-shield-alt"></i>
                            <div class="checkout-badge__title">Compra Segura</div>
                            <div class="checkout-badge__text">Ambiente seguro e autenticado</div>
                        </div>
                    </div>
                    <div class="col-sm">
                        <div class="checkout-badge">
                            <i class="fas fa-envelope"></i>
                            <div class="checkout-badge__title">Entrega via Email</div>
                            <div class="checkout-badge__text">Acesso ao produto<br>entregue por e-mail</div>
                        </div>
                    </div>
                    <div class="col-sm">
                        <div class="checkout-badge">
                            <i class="far fa-check-square"></i>
                            <div class="checkout-badge__title">Conteúdo Aprovado</div>
                            <div class="checkout-badge__text">100% revisado e aprovado</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @stack('scripts')
</body>
</html>
