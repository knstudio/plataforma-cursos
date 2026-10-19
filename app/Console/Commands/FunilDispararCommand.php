<?php

namespace App\Console\Commands;

use App\Services\Funil\EvolutionException;
use App\Services\Funil\FunilDisparador;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('funil:disparar {--limite=30 : Máximo de contatos por rodada}')]
#[Description('Envia as mensagens vencidas dos funis de WhatsApp (agendado a cada 10 segundos)')]
class FunilDispararCommand extends Command
{
    public function handle(FunilDisparador $disparador): int
    {
        try {
            $total = $disparador->processar((int) $this->option('limite'));
        } catch (EvolutionException $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$total} contato(s) processado(s).");

        return self::SUCCESS;
    }
}
