<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;

/**
 * @internal
 * Dto кеша рендеринга blade шаблона
 */
class ViewCacheDto extends DefaultDto
{
    public array $tags;
    public ?string $key;
    public int|bool|null $ttl;
    public string $view;
    public array $data;
    public array $mergeData;
    public array $ignoreData;
    public ?string $render;


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        return [
            'tags' => [],
            'ttl' => (int)Lh::config(ConfigEnum::ViewCache, 'ttl'),
            'data' => [],
            'mergeData' => [],
            'ignoreData' => [],
        ];
    }
}
