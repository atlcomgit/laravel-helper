<?php

namespace Atlcom\LaravelHelper\Providers;

use Atlcom\Helper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * Подключение макросов
 */
class LaravelHelperMacroServiceProvider extends ServiceProvider
{
    public function register(): void {}


    public function boot(): void
    {
        if (!config('laravel-helper.str.macros-enabled')) {
            return;
        }

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