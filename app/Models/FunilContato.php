<?php

namespace App\Models;

use Database\Factories\FunilContatoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One person (one WhatsApp number) shared by every funnel, linked to the
 * student account once there is one.
 */
#[Fillable(['telefone', 'nome', 'email', 'user_id', 'ultima_interacao_em', 'descadastrado_em'])]
class FunilContato extends Model
{
    /** @use HasFactory<FunilContatoFactory> */
    use HasFactory;

    protected $table = 'funil_contatos';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ultima_interacao_em' => 'datetime',
            'descadastrado_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<FunilInscricao, $this>
     */
    public function inscricoes(): HasMany
    {
        return $this->hasMany(FunilInscricao::class, 'contato_id');
    }

    public function isDescadastrado(): bool
    {
        return $this->descadastrado_em !== null;
    }

    /**
     * Approved purchase of the product on the platform (order bumps included).
     */
    public function temProduto(int $productId): bool
    {
        return $this->user_id !== null && $this->user?->hasPurchased($productId);
    }

    /**
     * "Maria" from "MARIA da silva"; empty when there is no name.
     */
    public function primeiroNome(): string
    {
        $primeiro = strtok(trim((string) $this->nome), ' ');

        return $primeiro === false ? '' : mb_convert_case($primeiro, MB_CASE_TITLE);
    }
}
