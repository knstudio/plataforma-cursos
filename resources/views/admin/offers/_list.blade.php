{{-- Offers card on the product edit page. Expects $product with offers loaded. --}}
<div class="card" id="ofertas">
    <div class="card-header d-flex align-items-center">
        <div class="flex">
            <h4 class="card-title">Ofertas (preços especiais)</h4>
            <p class="card-subtitle">Cada oferta tem o seu link de checkout com preço próprio. Ideal para campanhas de WhatsApp.</p>
        </div>
        <a href="{{ route('admin.offers.create', $product) }}" class="btn btn-success ml-3">Nova oferta</a>
    </div>

    @if ($product->offers->isNotEmpty())
        <div class="table-responsive">
            <table class="table m-0">
                <thead class="thead-light">
                    <tr>
                        <th>Oferta</th>
                        <th>Link</th>
                        <th class="text-right">Preço</th>
                        <th>Validade</th>
                        <th>Status</th>
                        <th class="text-center">Vendas</th>
                        <th class="text-right">Faturamento</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($product->offers as $offer)
                        <tr>
                            <td>
                                <strong>{{ $offer->name }}</strong>
                                @if ($offer->without_order_bump)
                                    <br><small class="text-muted">sem order bump</small>
                                @elseif ($offer->order_bump_price !== null)
                                    <br><small class="text-muted">bump R$ {{ number_format($offer->order_bump_price, 2, ',', '.') }}</small>
                                @endif
                            </td>
                            <td style="min-width: 260px">
                                <input type="text" readonly class="form-control form-control-sm" value="{{ $offer->checkoutUrl() }}" onclick="this.select()">
                            </td>
                            <td class="text-right">R$ {{ number_format($offer->price, 2, ',', '.') }}</td>
                            <td>{{ $offer->expires_at?->format('d/m/Y') ?? 'Sem prazo' }}</td>
                            <td>
                                @if (! $offer->is_active)
                                    <span class="badge badge-light">Inativa</span>
                                @elseif ($offer->isExpired())
                                    <span class="badge badge-warning">Expirada</span>
                                @else
                                    <span class="badge badge-success">Ativa</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $offer->approved_orders_count }}</td>
                            <td class="text-right">R$ {{ number_format((float) $offer->approved_revenue, 2, ',', '.') }}</td>
                            <td class="text-right" style="white-space: nowrap">
                                <a href="{{ $offer->checkoutUrl() }}" target="_blank" class="btn btn-white btn-sm" title="Abrir checkout"><i class="material-icons">shopping_cart</i></a>
                                <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-white btn-sm" title="Editar"><i class="material-icons">edit</i></a>
                                <form method="POST" action="{{ route('admin.offers.destroy', $offer) }}" class="d-inline" onsubmit="return confirm('Excluir esta oferta? Se ela já tiver vendas, será apenas desativada.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-white btn-sm text-danger" title="Excluir"><i class="material-icons">delete</i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($product->offers->isEmpty())
        <div class="card-body text-muted">Nenhuma oferta ainda. O checkout padrão usa o preço do produto.</div>
    @endif
</div>
