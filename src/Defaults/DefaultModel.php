<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\LaravelHelper\Traits\ModelCacheTrait;
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Atlcom\LaravelHelper\Traits\ModelResourceTrait;
use Atlcom\LaravelHelper\Traits\ModelScopeTrait;
use Atlcom\LaravelHelper\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Абстрактный класс моделей
 *
 * @property array $config
 * @property string $morph_name
 */
abstract class DefaultModel extends Model
{
    use ModelLogTrait;
    use ModelCacheTrait;
    use ModelTrait;
    use ModelResourceTrait;
    use ModelScopeTrait;
    // use ModelHasFilesTrait;
}
