<p class="checkout-help {{ $class ?? '' }}">
    Precisa de ajuda? {{ \App\Models\Setting::value('support_email', config('checkout.support_email')) }}<br>
    {{ \App\Models\Setting::value('company_name', config('checkout.company_name')) }}<br>
    CNPJ: {{ \App\Models\Setting::value('company_document', config('checkout.company_document')) }}
</p>
