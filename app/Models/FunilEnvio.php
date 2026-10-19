<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * History of the funnel messages sent (one row per enrollment and message).
 */
#[Fillable(['inscricao_id', 'bloco_id', 'mensagem_id', 'product_offer_id', 'status', 'detalhe'])]
class FunilEnvio extends Model
{
    public const STATUS_ENVIADO = 'enviado';

    public const STATUS_ERRO = 'erro';

    protected $table = 'funil_envios';

    /**
     * @return BelongsTo<FunilInscricao, $this>
     */
    public function inscricao(): BelongsTo
    {
        return $this->belongsTo(FunilInscricao::class, 'inscricao_id');
    }

    /**
     * @return BelongsTo<FunilMensagem, $this>
     */
    public function mensagem(): BelongsTo
    {
        return $this->belongsTo(FunilMensagem::class, 'mensagem_id');
    }
}
