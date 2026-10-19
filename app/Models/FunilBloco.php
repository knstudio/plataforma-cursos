<?php

namespace App\Models;

use Database\Factories\FunilBlocoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One product of the platform inside a funnel, with its messages.
 * espera_inicio_seg: wait after the previous block (first block: after entry).
 * espera_se_comprou_seg: wait when the previous block's product was bought (null = espera_inicio_seg).
 */
#[Fillable(['funil_id', 'posicao', 'product_id', 'espera_inicio_seg', 'espera_se_comprou_seg', 'ativo'])]
class FunilBloco extends Model
{
    /** @use HasFactory<FunilBlocoFactory> */
    use HasFactory;

    protected $table = 'funil_blocos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posicao' => 'integer',
            'espera_inicio_seg' => 'integer',
            'espera_se_comprou_seg' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Funil, $this>
     */
    public function funil(): BelongsTo
    {
        return $this->belongsTo(Funil::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<FunilMensagem, $this>
     */
    public function mensagens(): HasMany
    {
        return $this->hasMany(FunilMensagem::class, 'bloco_id')->orderBy('ordem')->orderBy('id');
    }

    /**
     * @return HasMany<FunilInscricao, $this>
     */
    public function inscricoes(): HasMany
    {
        return $this->hasMany(FunilInscricao::class, 'bloco_atual_id');
    }

    /**
     * The first message's own wait (seconds), added when a contact arrives at the block.
     */
    public function esperaPrimeiraMensagem(): int
    {
        return (int) $this->mensagens()->where('ativo', true)->value('espera_seg');
    }
}
