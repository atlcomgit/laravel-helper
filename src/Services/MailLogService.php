<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\MailLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Events\MailFailed;
use Atlcom\LaravelHelper\Events\MailLogEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Repositories\MailLogRepository;

/**
 * Сервис логирования отправки писем
 */
//?!? check
class MailLogService extends DefaultService
{
    /**
     * Сохраняет лог отправки письма
     *
     * @param MailLogDto $dto
     * @return void
     */
    public function create(MailLogDto $dto): void
    {
        app(MailLogRepository::class)->create($dto);

        MailLogEvent::dispatch($dto);
    }


    /**
     * Обновляет лог отправки письма
     *
     * @param MailLogDto $dto
     * @return void
     */
    public function update(MailLogDto $dto): void
    {
        app(MailLogRepository::class)->update($dto);
    }


    /**
     * Логирует успешную отправку
     *
     * @param MailLogDto $dto
     * @return void
     */
    public function success(MailLogDto $dto): void
    {
        $dto->status = MailLogStatusEnum::Success;

        if (Lh::config(ConfigEnum::MailLog, 'store_on_start')) {
            $dto->dispatch();
        } else {
            // Если не сохраняли в начале, то создаем сейчас
            // Но update требует существования записи.
            // Если store_on_start=false, то мы должны создать запись сейчас.
            // Но метод update в репозитории ищет по uuid.
            // Если записи нет, update ничего не сделает.
            // Поэтому лучше использовать create или updateOrCreate логику, но в репозитории update просто обновляет.
            // Если store_on_start=false, то мы просто создаем запись со статусом Success.

            // Проверим логику. Обычно store_on_start создает запись со статусом Process.
            // Если мы тут, значит отправка завершена.
            // Если store_on_start было true, то запись есть.
            // Если false, то записи нет.

            // В HttpLogService логика такая:
            // create вызывается если store_on_start (через middleware или событие RequestSending)
            // update вызывается при ResponseReceived.

            // Здесь мы будем вызывать success из макроса.
            // Если store_on_start включен, то макрос должен был вызвать create перед отправкой.
            // Но макрос PendingMail::send() оборачивает отправку.

            // Реализуем в макросе:
            // 1. create (Process)
            // 2. send
            // 3. success (Success)
            // catch -> failed (Failed)

            // Если store_on_start выключен, то create не вызываем, а вызываем сразу success (который создаст запись).
            // Но у нас методы create/update в сервисе просто делегируют репозиторию.
            // Репозиторий update делает update.

            // Давайте сделаем так:
            // success вызывает dispatch. Job вызывает update.
            // Если записи нет, update вернет null.
            // Нам нужно чтобы Job понимал, создавать или обновлять.
            // В Job:
            // match ($this->dto->status) {
            //    Process => create
            //    Success, Failed => update
            // }

            // Если store_on_start=false, то мы не вызывали create(Process).
            // Значит при вызове success(Success) Job вызовет update, и ничего не обновит.
            // Это проблема.

            // Исправим Job или Service.
            // Лучше в Service.

            if (!Lh::config(ConfigEnum::MailLog, 'store_on_start')) {
                // Если не сохраняли в начале, то создаем запись сразу со статусом Success
                // Но нам нужно чтобы Job вызвал create.
                // Но Job смотрит на status.
                // Если status=Success, Job вызывает update.

                // Вариант 1: Изменить Job, чтобы он делал updateOrCreate.
                // Вариант 2: В Service, если !store_on_start, то вызываем create, но с правильным статусом.

                // В HttpLogService:
                // Lh::config(ConfigEnum::HttpLog, 'only_response') ? $this->create($dto) : ...

                // Сделаем аналогично.
                $this->create($dto);
            } else {
                $dto->dispatch();
            }
        }

        MailLogEvent::dispatch($dto);
    }


    /**
     * Логирует ошибку отправки
     *
     * @param MailLogDto $dto
     * @return void
     */
    public function failed(MailLogDto $dto): void
    {
        $dto->status = MailLogStatusEnum::Failed;

        if (!Lh::config(ConfigEnum::MailLog, 'store_on_start')) {
            $this->create($dto);
        } else {
            $dto->dispatch();
        }

        MailLogEvent::dispatch($dto);
    }


    /**
     * Очищает логи
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!Lh::config(ConfigEnum::MailLog, 'enabled')) {
            return 0;
        }

        return app(MailLogRepository::class)->cleanup($days);
    }
}
