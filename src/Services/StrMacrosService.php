<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * Сервис регистрации str макросов
 */
class StrMacrosService
{
    /**
     * Добавляет макросы в строковый помощник
     *
     * @return void
     */
    public static function setMacros(): void
    {
        if (method_exists(Helper::class, 'intervalBetween')) {
            /**
             * @see \Tests\Unit\Helpers\StrMacrosTest::inInterval()
             * @example Str::intervalBetween('2025.01.02', ['2025.01.01', '2025.01.03'])
             */
            Str::macro(
                'intervalBetween',
                fn (mixed $value, mixed ...$intervals): array
                => Helper::intervalBetween($value, ...$intervals)
            );

            /** 
             * @see \Tests\Unit\Helpers\StrMacrosTest::inInterval()
             * @example Str::of('2025.01.02')->intervalBetween(['2025.01.01', '2025.01.03'])
             */
            Stringable::macro(
                'intervalBetween',
                function (mixed ...$intervals): array {
                    /** @var Stringable $this */
                    return Helper::intervalBetween($this->value, ...$intervals);
                }
            );
        }

        // Stringable::macro(
        //     'xxx',
        //     function (mixed $param): Stringable {
        //         /** @var Stringable $this */
        //         return new Stringable((string)Helper::xxx($this->value, $param));
        //     }
        // );
    }
}
