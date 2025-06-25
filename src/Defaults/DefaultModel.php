<?php

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\ModelCacheTrait;
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Atlcom\LaravelHelper\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Абстрактный класс моделей
 *
 * @property int $id
 * @property array $config
 * @property string $morph_name
 */
abstract class DefaultModel extends Model
{
    use ModelLogTrait;
    use ModelCacheTrait;
    use ModelTrait;
    // use ModelHasFilesTrait;
}
