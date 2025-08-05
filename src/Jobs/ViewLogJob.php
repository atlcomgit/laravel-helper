<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\ViewLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Events\ViewLogEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\ViewLogService;

/**
 * @internal
 * Задача сохранения логирования рендеринга blade шаблонов через очередь
 */
class ViewLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(protected ViewLogDto $dto)
    {
        $this->onQueue(Lh::config(ConfigEnum::ViewLog, 'queue'));
    }


    /**
     * Обработка задачи логирования рендеринга blade шаблонов
     *
     * @return void
     */
    public function __invoke()
    {
        app(ViewLogService::class)->log($this->dto);

        event(new ViewLogEvent($this->dto));
    }
}
