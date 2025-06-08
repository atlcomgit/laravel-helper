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
    public ?string $userId;
    public string $modelType;
    public ?string $modelId;
    public ModelLogTypeEnum $type;
    public array $attributes;
    public ?array $changes;
    public ?Carbon $createdAt;


    /**
     * Возвращает массив преобразований свойств
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
     * Возвращает массив значений по умолчанию
     *
     * @return array
     */
    // #[Override()]
    protected function defaults(): array
    {
        static $now = now();

        return [
            'userId' => user()?->id ?? null,
            'createdAt' => $now,
        ];
    }


    /**
     * Возвращает массив преобразований типов
     *
     * @return array
     */
    // #[Override()]
    protected function casts(): array
    {
        return ModelLog::getModelCasts();
    }


    /**
     * Метод вызывается до заполнения dto
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
     * Метод вызывается до преобразования dto в массив
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
        if (
            !config('laravel-helper.model_log.enabled')
            || app(LaravelHelperService::class)->checkIgnoreTables([ModelLog::getTableName()])
            || app(LaravelHelperService::class)
                ->checkExclude('laravel-helper.model_log.exclude', $this->serializeKeys(true)->toArray())
        ) {
            return;
        }


        isTesting()
            ? ModelLogJob::dispatchSync($this)
            : ModelLogJob::dispatch($this);
    }
}
