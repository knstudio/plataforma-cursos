<?php

namespace App\Models;

use Database\Factories\FunilMensagemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message of a block. espera_seg counts from the previous message (the
 * first one, from the block start). The offer gives {preco} and {link}.
 */
#[Fillable(['bloco_id', 'ordem', 'espera_seg', 'product_offer_id', 'texto', 'ativo'])]
class FunilMensagem extends Model
{
    /** @use HasFactory<FunilMensagemFactory> */
    use HasFactory;

    protected $table = 'funil_mensagens';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
            'espera_seg' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<FunilBloco, $this>
     */
    public function bloco(): BelongsTo
    {
        return $this->belongsTo(FunilBloco::class, 'bloco_id');
    }

    /**
     * @return BelongsTo<ProductOffer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class, 'product_offer_id');
    }
}
