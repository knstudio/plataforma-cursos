@extends('layouts.app')

@section('title', 'Produtos')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active">Produtos</li>
@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row flex-wrap mb-headings align-items-start align-items-sm-center">
        <div class="flex mb-2 mb-sm-0">
            <h1 class="h2">Produtos</h1>
            <p class="text-muted mb-0">O que é vendido. Cada produto libera um ou mais cursos quando o pagamento é aprovado.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-success">Novo produto</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-nowrap m-0">
                <thead class="thead-light">
                    <tr>
                        <th>Produto</th>
                        <th>Cursos liberados</th>
                        <th class="text-right">Preço</th>
                        <th>Acesso</th>
                        <th>Hotmart</th>
                        <th class="text-center">Vendas</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <a href="{{ route('admin.products.edit', $product) }}"><strong>{{ $product->name }}</strong></a>
                                @if ($product->orderBumpProduct)
                                    <br><small class="text-muted">Order bump: {{ $product->orderBumpProduct->name }}</small>
                                @endif
                            </td>
                            <td style="white-space: normal">{{ $product->courses->pluck('title')->implode(', ') }}</td>
                            <td class="text-right">R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                            <td>{{ $product->access_days ? $product->access_days.' dias' : 'Vitalício' }}</td>
                            <td>{{ $product->hotmart_product_id ?? '—' }}</td>
                            <td class="text-center">{{ $product->approved_orders_count }}</td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge badge-success">Ativo</span>
                                @else
                                    <span class="badge badge-light">Inativo</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('checkout.show', $product) }}" target="_blank" class="btn btn-white btn-sm" title="Abrir checkout"><i class="material-icons">shopping_cart</i></a>
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-white btn-sm"><i class="material-icons">edit</i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Nenhum produto cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $products->links() }}
@endsection
