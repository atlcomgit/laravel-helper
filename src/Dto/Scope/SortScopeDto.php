<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\Scope;

use Atlcom\LaravelHelper\Defaults\DefaultDto;

class SortScopeDto extends DefaultDto
{
    public string $field;
    public string $direction;
}