<?php

namespace App\Models;

use App\Enums\FunilGatilhoTipo;
use Database\Factories\FunilGatilhoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How contacts enter a funnel. Settings that only one type uses live in
 * config (formulario: pagina_obrigado, link_ebook, msg_confirmacao, msg_ebook).
 */
#[Fillable(['funil_id', 'tipo', 'product_id', 'config', 'ativo'])]
class FunilGatilho extends Model
{
    /** @use HasFactory<FunilGatilhoFactory> */
    use HasFactory;

    protected $table = 'funil_gatilhos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => FunilGatilhoTipo::class,
            'config' => 'array',
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
     * Product whose purchase starts the funnel (compra_produto).
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function opcao(string $chave): ?string
    {
        return $this->config[$chave] ?? null;
    }

    public function descricao(): string
    {
        return match ($this->tipo) {
            FunilGatilhoTipo::Formulario => 'Formulário da landing',
            FunilGatilhoTipo::CompraProduto => 'Comprou '.($this->product?->name ?? '(produto removido)'),
        };
    }
}
