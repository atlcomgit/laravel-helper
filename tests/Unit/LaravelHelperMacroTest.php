<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Tests\TestCase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты макросов
 */
final class LaravelHelperMacroTest extends TestCase
{
    #[Test]
    public function intervalBetween(): void
    {
        $this->assertSame([2], Str::intervalBetween(2, 1, 2, 3));
        $this->assertSame(['1..10'], Str::intervalBetween(2, [1, 10]));
        $this->assertSame(['1..10'], Str::intervalBetween(2, [1, 10], [11, 20]));
        $this->assertSame(['11..20'], Str::intervalBetween(12, [1, 10], [11, 20]));
        $this->assertSame(['11..20'], Str::intervalBetween(12, '1..10, 11..20'));
        $this->assertSame(['2025.01.01..2025.01.04'], Str::of('2025.01.02')->intervalBetween(['2025.01.01', '2025.01.04']));
        $this->assertSame(['2025.01.01..2025.01.04'], str('2025.01.02')->intervalBetween(['2025.01.01', '2025.01.04']));
    }
}
