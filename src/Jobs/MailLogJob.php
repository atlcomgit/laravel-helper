<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Jobs;

use Atlcom\LaravelHelper\Defaults\DefaultJob;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\MailLogService;

/**
 * Задача сохранения лога отправки письма
 */
class MailLogJob extends DefaultJob
{
    public $tries = 1;


    public function __construct(
        public MailLogDto $dto,
    ) {
        $this->onQueue(Lh::config(ConfigEnum::MailLog, 'queue'));
    }


    public function handle(): void
    {
        match ($this->dto->status) {
            MailLogStatusEnum::Process => app(MailLogService::class)->create($this->dto),
            MailLogStatusEnum::Success => app(MailLogService::class)->update($this->dto),
            MailLogStatusEnum::Failed => app(MailLogService::class)->update($this->dto),

            default => null,
        };
    }
}
