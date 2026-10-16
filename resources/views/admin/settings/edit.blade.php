@extends('layouts.app')

@section('title', 'Configurações')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active">Configurações</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="media align-items-center mb-headings">
            <div class="media-body">
                <h1 class="h2">Configurações</h1>
            </div>
            <div class="media-right">
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Pixels do Facebook</h4>
                        <p class="card-subtitle">Disparam em todos os checkouts: PageView e InitiateCheckout na página de pagamento, Purchase quando o pagamento é aprovado.</p>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label class="form-label" for="facebook_pixel_ids">IDs dos pixels (um por linha)</label>
                            <textarea id="facebook_pixel_ids" name="facebook_pixel_ids" rows="4"
                                      class="form-control @error('facebook_pixel_ids') is-invalid @enderror"
                                      placeholder="2395963517103497">{{ old('facebook_pixel_ids', implode("\n", $pixelIds)) }}</textarea>
                            <small class="form-text text-muted">Deixe vazio para não usar pixel. Pixels de um curso específico são cadastrados no próprio produto.</small>
                            @error('facebook_pixel_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Rodapé do checkout</h4>
                        <p class="card-subtitle">Aparece abaixo do botão de pagamento.</p>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label" for="support_email">E-mail de suporte</label>
                            <input type="email" id="support_email" name="support_email" value="{{ old('support_email', $supportEmail) }}" required
                                   class="form-control @error('support_email') is-invalid @enderror">
                            @error('support_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="company_name">Nome da empresa</label>
                            <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $companyName) }}" required
                                   class="form-control @error('company_name') is-invalid @enderror">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="company_document">CNPJ</label>
                            <input type="text" id="company_document" name="company_document" value="{{ old('company_document', $companyDocument) }}"
                                   class="form-control @error('company_document') is-invalid @enderror">
                            @error('company_document')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $secretField = function (string $key, string $label, string $help) use ($secrets) {
                return compact('key', 'label', 'help') + $secrets[$key];
            };
        @endphp

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Mercado Pago</h4>
                        <p class="card-subtitle">
                            Em <a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank" rel="noopener">Suas integrações</a>
                            → sua aplicação → Credenciais. Use as de teste para testar e troque pelas de produção quando for vender.
                        </p>
                    </div>
                    <div class="card-body">
                        @include('admin.settings._secret', $secretField('mercadopago_access_token', 'Access Token', 'Começa com APP_USR-. A Public Key não é necessária.'))
                        @include('admin.settings._secret', $secretField('mercadopago_webhook_secret', 'Assinatura secreta do webhook', 'Em Webhooks → Configurar notificações, marque o evento "Pagamentos" e cadastre a URL abaixo. Depois copie a assinatura secreta para cá.'))
                        <div class="form-group mb-0">
                            <label class="form-label">URL do webhook</label>
                            <input type="text" class="form-control" value="{{ route('webhooks.mercadopago') }}" readonly onclick="this.select()">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Hotmart</h4>
                        <p class="card-subtitle">Ferramentas → Webhook (versão 2.0.0). Cadastre a URL abaixo e copie o hottok para cá.</p>
                    </div>
                    <div class="card-body">
                        @include('admin.settings._secret', $secretField('hotmart_hottok', 'Hottok', 'Também informe o ID do produto Hotmart no cadastro de cada produto.'))
                        <div class="form-group mb-0">
                            <label class="form-label">URL do webhook</label>
                            <input type="text" class="form-control" value="{{ route('webhooks.hotmart') }}" readonly onclick="this.select()">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <button type="submit" class="btn btn-success">Salvar</button>
        </div>
    </form>
@endsection
