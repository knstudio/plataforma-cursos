<?php

namespace Tests\Unit;

use App\Support\Duration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function test_it_converts_text_to_seconds(): void
    {
        $this->assertSame(346, Duration::toSeconds('05:46'));
        $this->assertSame(346, Duration::toSeconds('5:46'));
        $this->assertSame(3725, Duration::toSeconds('1:02:05'));
        $this->assertNull(Duration::toSeconds(''));
        $this->assertNull(Duration::toSeconds(null));
    }

    public function test_it_rejects_invalid_text(): void
    {
        $this->assertFalse(Duration::toSeconds('5 minutos'));
        $this->assertFalse(Duration::toSeconds('05:75'));
    }

    public function test_it_formats_seconds(): void
    {
        $this->assertSame('05:46', Duration::format(346));
        $this->assertSame('1:02:05', Duration::format(3725));
        $this->assertNull(Duration::format(null));
    }
}
