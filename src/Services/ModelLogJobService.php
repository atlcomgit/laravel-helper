<?php

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Dto\ModelLogDto;
use Atlcom\LaravelHelper\Enums\ModelLogDriverEnum;
use Atlcom\LaravelHelper\Models\ModelLog;
use Exception;
use Throwable;

/**
 * Сервис сохранения логов из очереди
 */
class ModelLogJobService
{
    protected array $drivers;


    public function __construct(protected ModelLogDto $dto)
    {
        $this->drivers = config('laravel-helper.model_log.drivers', []);
    }


    /**
     * Запуск сохранения логов
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->drivers as $driver) {
            try {
                !is_string($driver) ?: $driver = trim($driver);

                switch (ModelLogDriverEnum::enumFrom($driver)) {
                    case ModelLogDriverEnum::File:
                        if ($file = config('laravel-helper.model_log.file')) {
                            file_put_contents(
                                $file,
                                now()->format('d-m-Y H:i:s') . ' '
                                . json_encode($this->dto, Helper::jsonFlags())
                                . PHP_EOL,
                                FILE_APPEND,
                            );
                        }
                        break;

                    case ModelLogDriverEnum::Database:
                        $modelLogClass = config('laravel-helper.model_log.model') ?? ModelLog::class;

                        if ($this->dto->modelType !== $modelLogClass && class_exists($modelLogClass)) {
                            $modelLogClass::make()
                                ->setConnection(config('laravel-helper.model_log.connection'))
                                ->setTable(config('laravel-helper.model_log.table'))
                                ->create($this->dto->toArray());
                        }
                        break;

                    case ModelLogDriverEnum::Telegram:
                        throw new Exception('Драйвер не реализован');

                    default:
                        !$driver ?: throw new Exception('Драйвер лога не найден');
                }

            } catch (Throwable $e) {
            }
        }
    }
}
