<?php

namespace App\Models;

use App\Enums\FunilGatilhoTipo;
use Carbon\CarbonInterface;
use Database\Factories\FunilFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A WhatsApp funnel: a queue of blocks (one product each) sent through one
 * Evolution instance, only inside its sending hours.
 */
#[Fillable(['slug', 'nome', 'instancia', 'prioridade', 'hora_inicio_envio', 'hora_fim_envio', 'ativo'])]
class Funil extends Model
{
    /** @use HasFactory<FunilFactory> */
    use HasFactory;

    protected $table = 'funil_funis';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prioridade' => 'integer',
            'hora_inicio_envio' => 'integer',
            'hora_fim_envio' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<FunilGatilho, $this>
     */
    public function gatilhos(): HasMany
    {
        return $this->hasMany(FunilGatilho::class);
    }

    /**
     * @return HasMany<FunilBloco, $this>
     */
    public function blocos(): HasMany
    {
        return $this->hasMany(FunilBloco::class)->orderBy('posicao')->orderBy('id');
    }

    /**
     * @return HasMany<FunilInscricao, $this>
     */
    public function inscricoes(): HasMany
    {
        return $this->hasMany(FunilInscricao::class);
    }

    /**
     * The landing form posts the funnel slug; this is the entry it uses.
     */
    public function gatilhoFormulario(): ?FunilGatilho
    {
        return $this->gatilhos()
            ->where('tipo', FunilGatilhoTipo::Formulario)
            ->where('ativo', true)
            ->first();
    }

    public function primeiroBloco(): ?FunilBloco
    {
        return $this->blocos()->where('ativo', true)->first();
    }

    public function dentroDoHorario(CarbonInterface $momento): bool
    {
        return $momento->hour >= $this->hora_inicio_envio && $momento->hour < $this->hora_fim_envio;
    }
}
