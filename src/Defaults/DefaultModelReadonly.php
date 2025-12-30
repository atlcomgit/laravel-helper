<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\ModelReadonlyTrait;

/**
 * Абстрактный класс моделей только для чтения
 */
abstract class DefaultModelReadonly extends DefaultModel
{
    use ModelReadonlyTrait;


    protected $guarded      = ['*'];
    protected $fillable     = [];
    public    $incrementing = false;
    public    $timestamps   = false;
}
