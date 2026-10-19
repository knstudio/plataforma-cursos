<?php

namespace Tests\Unit;

use App\Enums\EsperaUnidade;
use App\Services\Funil\Telefone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FunilTelefoneTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function telefones(): array
    {
        return [
            'mascarado' => ['(82) 99999-1234', '5582999991234'],
            'sem o 9' => ['82 9999-1234', '5582999991234'],
            'com 55' => ['5582999991234', '5582999991234'],
            'jid sem o 9' => ['558299991234', '5582999991234'],
            'zero na frente' => ['082999991234', '5582999991234'],
            'curto' => ['99991234', null],
            'longo demais' => ['55 82 99999-12345', null],
            'sem o 9 de celular' => ['5582899991234', null],
            'vazio' => [null, null],
        ];
    }

    #[DataProvider('telefones')]
    public function test_normalizes_brazilian_mobile_numbers(?string $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, Telefone::normalizar($entrada));
    }

    public function test_wait_times_use_the_largest_exact_unit(): void
    {
        $this->assertSame([30, EsperaUnidade::Segundos], EsperaUnidade::separar(30));
        $this->assertSame([3, EsperaUnidade::Minutos], EsperaUnidade::separar(180));
        $this->assertSame([20, EsperaUnidade::Horas], EsperaUnidade::separar(72000));
        $this->assertSame([1, EsperaUnidade::Dias], EsperaUnidade::separar(86400));
        $this->assertSame([90, EsperaUnidade::Segundos], EsperaUnidade::separar(90));
        $this->assertSame([0, EsperaUnidade::Minutos], EsperaUnidade::separar(0));
        $this->assertSame('30 seg', EsperaUnidade::descrever(30));
        $this->assertSame('1 dia', EsperaUnidade::descrever(86400));
        $this->assertSame('20 horas', EsperaUnidade::descrever(72000));
        $this->assertSame(172800, EsperaUnidade::Dias->paraSegundos(2));
    }
}
