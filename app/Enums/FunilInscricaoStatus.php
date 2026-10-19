<?php

namespace App\Enums;

enum FunilInscricaoStatus: string
{
    /** Filled the form and got "posso te enviar?"; waiting for the reply. */
    case AguardandoConfirmacao = 'aguardando_confirmacao';

    /** Another funnel of the same contact is sending; this one waits its turn. */
    case EmEspera = 'em_espera';

    /** Walking through the blocks (the only status the dispatcher sends to). */
    case NoFunil = 'no_funil';

    case Concluido = 'concluido';

    /** Asked to stop (SAIR): never receives anything again. */
    case Descadastrado = 'descadastrado';

    public function label(): string
    {
        return match ($this) {
            self::AguardandoConfirmacao => 'Aguardando confirmação',
            self::EmEspera => 'Em espera',
            self::NoFunil => 'No funil',
            self::Concluido => 'Concluído',
            self::Descadastrado => 'Descadastrado',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::AguardandoConfirmacao => 'badge-warning',
            self::EmEspera => 'badge-secondary',
            self::NoFunil => 'badge-success',
            self::Concluido => 'badge-primary',
            self::Descadastrado => 'badge-light',
        };
    }

    /**
     * Statuses that still count as "inside" the funnel.
     *
     * @return list<self>
     */
    public static function abertos(): array
    {
        return [self::AguardandoConfirmacao, self::EmEspera, self::NoFunil];
    }
}
