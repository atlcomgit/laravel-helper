<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\QueryLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\QueryLogEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\QueryLogService;
use Throwable;

/**
 * @internal
 * Задача сохранения логирования query запросов через очередь
 */
class QueryLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected QueryLogDto $dto)
    {
        $this->onQueue(Lh::config(ConfigEnum::QueryLog, 'queue'));

        !($connection = Lh::config(ConfigEnum::QueryLog, 'queue_connection'))
            ?: $this->onConnection($connection);
    }


    /**
     * Обработка задачи логирования query запросов
     *
     * @return void
     */
    public function __invoke()
    {
        try {
            app(QueryLogService::class)->log($this->dto);

            event(new QueryLogEvent($this->dto));
        } catch (Throwable) {
            // Сбой query-log repository или listener не должен завершать бизнес-задачу ошибкой.
        }
    }
}
