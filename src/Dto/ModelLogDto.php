<?php

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\ModelLogTypeEnum;
use Atlcom\LaravelHelper\Jobs\ModelLogJob;
use Atlcom\LaravelHelper\Models\ModelLog;
use Atlcom\LaravelHelper\Services\LaravelHelperService;
use Carbon\Carbon;

/**
 * Dto лога модели
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
            ->mappingKeys($this->mappings())
            ->onlyNotNull();
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return void
     */
    public function dispatch()
    {
        if (app(LaravelHelperService::class)->canDispatch($this)) {
            isTesting()
                ? ModelLogJob::dispatchSync($this)
                : ModelLogJob::dispatch($this);
        }
    }
}
