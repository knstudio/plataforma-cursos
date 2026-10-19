@extends('layouts.checkout')

@section('title', 'Pagamento '.$product->name)

@php
    $price = (float) $product->priceFor($offer);
    $regularPrice = (float) $product->price;
    $chosenBumps = collect(old('order_bumps', []))->map(fn ($id) => (int) $id);
    $initialTotal = $price + $bumps->whereIn('id', $chosenBumps)->sum(fn ($bump) => (float) $bump->priceFor($offer));
    $timerMinutes = (int) $product->checkout_timer_minutes;
    $brl = fn (float $value) => 'R$ '.number_format($value, 2, ',', '.');
@endphp

@push('head')
    @include('partials.facebook-pixel', [
        'events' => [['InitiateCheckout', ['value' => $price, 'currency' => 'BRL', 'content_name' => $product->name]]],
    ])
@endpush

@section('top')
    @if ($timerMinutes > 0)
        <div class="checkout-timer fixed-top">
            <span class="checkout-timer__clock" id="checkout-timer"
                  data-minutes="{{ $timerMinutes }}" data-key="checkout-timer-{{ $product->id }}-{{ $offer?->id ?? 0 }}">
                00 : {{ str_pad((string) $timerMinutes, 2, '0', STR_PAD_LEFT) }} : 00
            </span>
            <span class="checkout-timer__icon"><i class="far fa-clock"></i></span>
            <span class="checkout-timer__label">OFERTA POR TEMPO LIMITADO!</span>
        </div>
    @endif

    <div class="container checkout-banner {{ $timerMinutes > 0 ? 'checkout-banner--below-timer' : '' }}">
        <img src="{{ $product->checkoutBannerUrl() }}" alt="{{ $product->name }}">
    </div>
@endsection

@section('content')
    <form method="POST" novalidate
          action="{{ $offer ? route('checkout.offer.store', [$product, $offer]) : route('checkout.store', $product) }}">
        @csrf
        @if (old('fi', $funilInscricaoId))
            <input type="hidden" name="fi" value="{{ old('fi', $funilInscricaoId) }}">
        @endif

        <div class="products">
            <div class="form-group">
                <label for="name">Digite Seu Nome</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                       value="{{ old('name') }}" placeholder="Seu Nome" autocomplete="name" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="email">Digite Seu Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                       value="{{ old('email') }}" placeholder="Seu Email" autocomplete="email" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label for="email_confirmation">Confirme Seu Email</label>
                <input type="email" class="form-control" id="email_confirmation" name="email_confirmation"
                       value="{{ old('email_confirmation') }}" placeholder="Digite o email novamente" autocomplete="off" required>
                <div class="invalid-feedback" id="email-mismatch" hidden>Os e-mails não são iguais. Confira e digite novamente.</div>
            </div>
            <div class="form-group mb-0">
                <label for="phone">Digite Seu WhatsApp com DDD</label>
                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                       value="{{ old('phone') }}" placeholder="WhatsApp com DDD" maxlength="15" autocomplete="tel" required>
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <br>

        <div class="products">
            <div class="item">
                <span class="price">
                    @if ($offer && $regularPrice > $price)
                        <del>{{ $brl($regularPrice) }}</del>
                    @endif
                    {{ $brl($price) }}
                </span>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="main-product" checked disabled>
                    <label class="form-check-label text-dark" for="main-product">
                        <strong>{{ $product->name }}</strong>
                    </label>
                </div>
                @if ($product->description)
                    <p class="item-description">{{ $product->description }}</p>
                @endif
            </div>

            @foreach ($bumps as $bump)
                @php
                    $bumpPrice = (float) $bump->priceFor($offer);
                    $bumpRegularPrice = (float) $bump->bumpProduct->price;
                @endphp
                <div class="item order-bump-box">
                    <p class="order-bump-box__title">
                        <strong>+ {{ $bump->bumpProduct->name }} <span>- {{ $brl($bumpPrice) }} 😱</span></strong>
                    </p>
                    <label class="order-bump-box__offer" for="order_bump_{{ $bump->id }}">
                        <span class="order-bump-box__pointer">
                            <img src="{{ asset('assets/images/checkout/seta.png') }}" alt="">
                            <input class="order-bump-box__check js-order-bump" type="checkbox" id="order_bump_{{ $bump->id }}" name="order_bumps[]"
                                   value="{{ $bump->id }}" data-price="{{ $bumpPrice }}" @checked($chosenBumps->contains($bump->id))>
                        </span>
                        <span class="order-bump-box__text">
                            <strong class="order-bump-box__headline">
                                {{ $bump->headlineHtml() }}
                                @if ($bumpRegularPrice > $bumpPrice)
                                    <span class="text-dark">De <del>{{ $brl($bumpRegularPrice) }}</del> por</span>
                                @endif
                                <span class="order-bump-box__price">{{ $brl($bumpPrice) }}</span>
                            </strong>
                            <span class="order-bump-box__hint">MARQUE AQUI DO LADO ESQUERDO PARA INCLUIR ESSA OFERTA RELÂMPAGO E ACESSE IMEDIATAMENTE!</span>
                        </span>
                    </label>
                </div>
            @endforeach

            <div class="total">
                Total
                <span class="price" id="checkout-total" data-base="{{ $price }}">{{ $brl($initialTotal) }}</span>
            </div>
        </div>

        <div class="card-details">
            <button type="submit" class="btn btn-success btn-block">CONTINUAR <i class="fas fa-arrow-right"></i></button>
            <br>
            @include('checkout._help')
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            var bumps = document.querySelectorAll('.js-order-bump');
            var total = document.getElementById('checkout-total');
            var phone = document.getElementById('phone');
            var timer = document.getElementById('checkout-timer');
            var formatMoney = function (value) {
                return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            };
            var pad = function (number) {
                return (number < 10 ? '0' : '') + number;
            };

            // Typing the e-mail twice catches typos; pasting would copy the typo along.
            var email = document.getElementById('email');
            var emailConfirmation = document.getElementById('email_confirmation');
            var emailMismatch = document.getElementById('email-mismatch');
            var checkEmails = function () {
                var mismatch = emailConfirmation.value !== ''
                    && email.value.trim().toLowerCase() !== emailConfirmation.value.trim().toLowerCase();

                emailConfirmation.classList.toggle('is-invalid', mismatch);
                emailMismatch.hidden = !mismatch;

                return !mismatch;
            };

            emailConfirmation.addEventListener('paste', function (event) {
                event.preventDefault();
            });
            emailConfirmation.addEventListener('blur', checkEmails);
            email.addEventListener('blur', checkEmails);
            emailConfirmation.closest('form').addEventListener('submit', function (event) {
                if (!checkEmails()) {
                    event.preventDefault();
                    emailConfirmation.focus();
                }
            });

            var updateTotal = function () {
                var sum = parseFloat(total.dataset.base);

                bumps.forEach(function (bump) {
                    if (bump.checked) {
                        sum += parseFloat(bump.dataset.price);
                    }
                });

                total.textContent = formatMoney(sum);
            };

            bumps.forEach(function (bump) {
                bump.addEventListener('change', updateTotal);
            });

            phone.addEventListener('input', function () {
                var digits = phone.value.replace(/\D/g, '').slice(0, 11);
                var masked = digits;

                if (digits.length > 2) {
                    masked = '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
                }
                if (digits.length > 7) {
                    masked = '(' + digits.slice(0, 2) + ') ' + digits.slice(2, digits.length - 4) + '-' + digits.slice(-4);
                }

                phone.value = masked;
            });

            /*
             * Countdown kept per browser, so reloading the page doesn't restart it.
             * After it reaches zero, the next visit starts a new countdown.
             */
            if (timer) {
                var duration = parseInt(timer.dataset.minutes, 10) * 60 * 1000;
                var deadline = null;

                try {
                    deadline = parseInt(localStorage.getItem(timer.dataset.key), 10) || null;
                } catch (error) {}

                if (!deadline || deadline <= Date.now()) {
                    deadline = Date.now() + duration;
                    try {
                        localStorage.setItem(timer.dataset.key, String(deadline));
                    } catch (error) {}
                }

                var tick = function () {
                    var left = Math.max(0, Math.round((deadline - Date.now()) / 1000));
                    var hours = Math.floor(left / 3600);
                    var minutes = Math.floor((left % 3600) / 60);
                    var seconds = left % 60;

                    timer.textContent = pad(hours) + ' : ' + pad(minutes) + ' : ' + pad(seconds);

                    if (left > 0) {
                        setTimeout(tick, 1000);
                    }
                };

                tick();
            }
        })();
    </script>
@endpush
