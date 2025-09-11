<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\ModelLogJob;
use Atlcom\LaravelHelper\Models\ModelLog;
use Carbon\Carbon;

/**
 * @internal
 * Dto лога модели
 * @see ModelLog
 */
class ModelLogDto extends DefaultDto
{
    public int|string|null $userId;
    public string $modelType;
    public ?string $modelId;
    public ModelLogTypeEnum $type;
    public array $attributes;
    public ?array $changes;
    public ?Carbon $createdAt;


    /**
     * @inheritDoc
     * @see parent::mappings()
     *
     * @return array
     */
    // #[Override()]
    protected function mappings(): array
    {
        return [
            'userId' => 'user_id',
            'modelType' => 'model_type',
            'modelId' => 'model_id',
            'createdAt' => 'created_at',
        ];
    }


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        static $now = now();

        return [
            'userId' => user(returnOnlyId: true),
            'createdAt' => $now,
        ];
    }


    /**
     * @inheritDoc
     * @see parent::casts()
     *
     * @return array
     */
    // #[Override()]
    protected function casts(): array
    {
        return ModelLog::getModelCasts();
    }


    /**
     * @inheritDoc
     * @see parent::onFilling()
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onFilling(array &$array): void
    {
        // array_walk_recursive(
        //     $array,
        //     fn (&$value) => $value = is_string($value) ? addslashes($value) : $value,
        // );
    }


    /**
     * @inheritDoc
     * @see parent::onSerializing()
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onSerializing(array &$array): void
    {
        $this->onlyKeys(ModelLog::getModelKeys())
            ->serializeKeys(['attributes', 'changes'])
            ->mappingKeys($this->mappings())
            ->onlyNotNull();
    }


    /**
     * @inheritDoc
     */
    // #[Override()]
    protected function onSerialized(array &$array): void
    {
        $array['attributes'] = Hlp::castToString($array['attributes'] ?? []);
        $array['changes'] = Hlp::castToString($array['changes'] ?? null);
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return static
     */
    public function dispatch(): static
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::ModelLog, 'queue_dispatch_sync') ?? isTesting())
                ? ModelLogJob::dispatchSync($this)
                : ModelLogJob::dispatch($this);
        }

        return $this;
    }
}
