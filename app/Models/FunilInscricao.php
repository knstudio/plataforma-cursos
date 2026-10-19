<?php

namespace App\Models;

use App\Enums\FunilGatilhoTipo;
use App\Enums\FunilInscricaoStatus;
use Database\Factories\FunilInscricaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contact X in funnel Y. Pointer: bloco_atual_id + ultima_ordem_enviada
 * (0 = nothing of the block sent yet); proximo_envio_em = when the dispatcher acts.
 */
#[Fillable([
    'contato_id', 'funil_id', 'gatilho_id', 'origem_order_id', 'status', 'bloco_atual_id',
    'ultima_ordem_enviada', 'proximo_envio_em', 'processando_desde', 'confirmado_em', 'finalizado_em',
])]
class FunilInscricao extends Model
{
    /** @use HasFactory<FunilInscricaoFactory> */
    use HasFactory;

    protected $table = 'funil_inscricoes';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FunilInscricaoStatus::class,
            'ultima_ordem_enviada' => 'integer',
            'proximo_envio_em' => 'datetime',
            'processando_desde' => 'datetime',
            'confirmado_em' => 'datetime',
            'finalizado_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FunilContato, $this>
     */
    public function contato(): BelongsTo
    {
        return $this->belongsTo(FunilContato::class, 'contato_id');
    }

    /**
     * @return BelongsTo<Funil, $this>
     */
    public function funil(): BelongsTo
    {
        return $this->belongsTo(Funil::class);
    }

    /**
     * @return BelongsTo<FunilGatilho, $this>
     */
    public function gatilho(): BelongsTo
    {
        return $this->belongsTo(FunilGatilho::class, 'gatilho_id');
    }

    /**
     * @return BelongsTo<FunilBloco, $this>
     */
    public function blocoAtual(): BelongsTo
    {
        return $this->belongsTo(FunilBloco::class, 'bloco_atual_id');
    }

    /**
     * Purchase that started a post-sale enrollment.
     *
     * @return BelongsTo<Order, $this>
     */
    public function origemOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'origem_order_id');
    }

    /**
     * @return HasMany<FunilEnvio, $this>
     */
    public function envios(): HasMany
    {
        return $this->hasMany(FunilEnvio::class, 'inscricao_id');
    }

    /**
     * Post-sale enrollments go before the others of the same contact.
     */
    public function isPosVenda(): bool
    {
        return $this->gatilho?->tipo === FunilGatilhoTipo::CompraProduto;
    }
}
