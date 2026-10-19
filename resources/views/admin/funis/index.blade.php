@extends('layouts.app')

@section('title', 'Funis WhatsApp')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Painel</a></li>
    <li class="breadcrumb-item active">Funis WhatsApp</li>
@endsection

@section('content')
    <div class="d-flex flex-column flex-sm-row flex-wrap mb-headings align-items-start align-items-sm-center">
        <div class="flex mb-2 mb-sm-0">
            <h1 class="h2">Funis WhatsApp</h1>
            <p class="text-muted mb-0">Sequências de mensagens com ofertas dos produtos. Cada contato recebe de um funil por vez; o pós-venda tem prioridade.</p>
        </div>
        <a href="{{ route('admin.funis.create') }}" class="btn btn-success">Novo funil</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-nowrap m-0">
                <thead class="thead-light">
                    <tr>
                        <th>Funil</th>
                        <th>Entrada</th>
                        <th>Instância</th>
                        <th class="text-center">Blocos</th>
                        <th class="text-center">Aguardando</th>
                        <th class="text-center">No funil</th>
                        <th class="text-center">Em espera</th>
                        <th class="text-center">Concluídos</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($funis as $funil)
                        <tr>
                            <td>
                                <a href="{{ route('admin.funis.edit', $funil) }}"><strong>{{ $funil->nome }}</strong></a>
                                <br><small class="text-muted">{{ $funil->slug }} · envia das {{ $funil->hora_inicio_envio }}h às {{ $funil->hora_fim_envio }}h</small>
                            </td>
                            <td style="white-space: normal">
                                {{ $funil->gatilhos->where('ativo', true)->map->descricao()->implode(', ') ?: '—' }}
                            </td>
                            <td>{{ $funil->instancia }}</td>
                            <td class="text-center">{{ $funil->blocos_count }}</td>
                            <td class="text-center">{{ $funil->aguardando_count }}</td>
                            <td class="text-center">{{ $funil->no_funil_count }}</td>
                            <td class="text-center">{{ $funil->em_espera_count }}</td>
                            <td class="text-center">{{ $funil->concluido_count }}</td>
                            <td>
                                @if ($funil->ativo)
                                    <span class="badge badge-success">Ativo</span>
                                @else
                                    <span class="badge badge-light">Inativo</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.funis.edit', $funil) }}" class="btn btn-white btn-sm"><i class="material-icons">edit</i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Nenhum funil cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
