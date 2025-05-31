<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Dto\HttpLogCreateDto;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Dto\HttpLogFailedDto;
use Atlcom\LaravelHelper\Dto\HttpLogUpdateDto;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Enums\HttpLogTypeEnum;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Repositories\HttpLogRepository;

/**
 * Сервис логирования исходящих http запросов
 */
class HttpLogService
{
    public const HTTP_HEADER_UUID = 'X-UUID';
    public const HTTP_HEADER_NAME = 'X-NAME';
    public const HTTP_HEADER_TIME = 'X-TIME';


    /**
     * Возвращает идентификатор сообщения для http заголовка
     *
     * @param HttpLogHeaderEnum $headerName
     * @return array
     */
    public static function getLogHeaders(HttpLogHeaderEnum $headerName): array
    {
        return config('laravel-helper.http_log.out.enabled')
            ? ($headerName === HttpLogHeaderEnum::None
                ? [
                    self::HTTP_HEADER_UUID => '',
                    self::HTTP_HEADER_NAME => '',
                    self::HTTP_HEADER_TIME => '',
                ]
                : [
                    self::HTTP_HEADER_UUID => uuid(),
                    self::HTTP_HEADER_NAME => $headerName->value,
                    self::HTTP_HEADER_TIME => (string)now()->getTimestampMs(),
                ]
            )
            : [];
    }


    /**
     * Возвращает uuid из запроса
     *
     * @param HttpLogDto $dto
     * @return string|null
     */
    public function getUuid(HttpLogDto $dto): ?string
    {
        return $dto->uuid ?? (request()?->header(self::HTTP_HEADER_UUID) ?? [])[0] ?? null;
    }


    /**
     * Сохраняет http запрос в таблицу лога
     *
     * @param HttpLogDto $dto
     * @return void
     */
    public function create(HttpLogDto $dto): void
    {
        !$this->getUuid($dto) ?: app(HttpLogRepository::class)->create(HttpLogCreateDto::create($dto));
    }


    /**
     * Сохраняет ответ на http запрос в таблицу лога
     *
     * @param HttpLogDto $dto
     * @return void
     */
    public function update(HttpLogDto $dto): void
    {
        config('laravel-helper.http_log.only_response')
            ? $this->create($dto)
            : (
                !($dto->uuid = $this->getUuid($dto))
                ?: app(HttpLogRepository::class)->update(HttpLogUpdateDto::create($dto))
            );

        $this->telegram($dto);
    }


    /**
     * Сохраняет ошибку на http запрос в таблицу лога
     *
     * @param HttpLogDto $dto
     * @return void
     */
    public function failed(HttpLogDto $dto): void
    {
        config('laravel-helper.http_log.only_response')
            ? $this->create($dto->merge([
                'responseCode' => $dto->responseCode ?? 0,
                'responseMessage' => $dto->responseMessage ?? 'Connection error',
            ]))
            : (
                !($dto->uuid = $this->getUuid($dto))
                ?: app(HttpLogRepository::class)->update(HttpLogFailedDto::create($dto))
            );

        $this->telegram($dto);
    }


    /**
     * Отправка сообщения в телеграм
     *
     * @param HttpLogDto $dto
     * @return void
     */
    public function telegram(HttpLogDto $dto): void
    {
        !(
            $dto->type === HttpLogTypeEnum::Out
            && $dto->status === HttpLogStatusEnum::Failed
            && !in_array($dto->name, [HttpLogHeaderEnum::Unknown->value])
        ) ?: telegram(
            [
                'Описание' => 'Проблема в исходящем запросе',
                'Макрос' => $dto->name,
                'Адрес' => $dto->url,
                'Код' => $dto->responseCode,
                'Сообщение' => $dto->responseMessage,
                'Ответ' => json_decode($dto->responseData ?? '', true) ?? $dto->responseData,
                'Инфо' => $dto->info,
                'uuid' => $dto->uuid,
            ],
            TelegramTypeEnum::Warning,
        );
    }


    /**
     * Очищает логи http запросов
     *
     * @param int $days
     * @return int
     */
    public function cleanup(int $days): int
    {
        if (!config('laravel-helper.http_log.in.enabled') && !config('laravel-helper.http_log.out.enabled')) {
            return 0;
        }

        return app(HttpLogRepository::class)->cleanup($days);
    }
}
