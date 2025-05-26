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
use Atlcom\LaravelHelper\Models\HttpLog;
use Illuminate\Support\Str;

/**
 * Сервис логирования исходящих http запросов
 * @covers EUS
 */
class HttpLogService
{
    public const HTTP_QUEUE = 'http-log';
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
                    self::HTTP_HEADER_UUID => Str::uuid()->toString(),
                    self::HTTP_HEADER_NAME => $headerName->value,
                    self::HTTP_HEADER_TIME => (string)now()->getTimestampMs(),
                ]
            )
            : [];
    }


    /**
     * Возвращает uuid из запроса
     *
     * @param HttpLogDto $httpLogDto
     * @return string
     */
    public function getUuid(HttpLogDto $httpLogDto): string
    {
        return $httpLogDto->uuid ?? ($httpLogDto->request?->header(self::HTTP_HEADER_UUID) ?? [])[0] ?? null;
    }


    /**
     * Сохраняет http запрос в таблицу лога
     *
     * @param HttpLogDto $httpLogDto
     * @return void
     */
    public function create(HttpLogDto $httpLogDto): void
    {
        !$this->getUuid($httpLogDto) ?: HttpLog::create(HttpLogCreateDto::create($httpLogDto)->toArray());
    }


    /**
     * Сохраняет ответ на http запрос в таблицу лога
     *
     * @param HttpLogDto $httpLogDto
     * @return void
     */
    public function update(HttpLogDto $httpLogDto): void
    {
        config('laravel-helper.http_log.only_response')
            ? $this->create($httpLogDto)
            : (!($uuid = $this->getUuid($httpLogDto))
                ?: HttpLog::where('uuid', $uuid)->update(HttpLogUpdateDto::create($httpLogDto)->toArray())
            );

        $this->telegram($httpLogDto);
    }


    /**
     * Сохраняет ошибку на http запрос в таблицу лога
     *
     * @param HttpLogDto $httpLogDto
     * @return void
     */
    public function failed(HttpLogDto $httpLogDto): void
    {
        config('laravel-helper.http_log.only_response')
            ? $this->create($httpLogDto->merge([
                'responseCode' => $httpLogDto->responseCode ?? 0,
                'responseMessage' => $httpLogDto->responseMessage ?? 'Connection error',
            ]))
            : (!($uuid = $this->getUuid($httpLogDto))
                ?: HttpLog::where('uuid', $uuid)->update(HttpLogFailedDto::create($httpLogDto)->toArray())
            );

        $this->telegram($httpLogDto);
    }


    /**
     * Отправка сообщения в телеграм
     *
     * @param HttpLogDto $httpLogDto
     * @return void
     */
    public function telegram(HttpLogDto $httpLogDto): void
    {
        !(
            $httpLogDto->type === HttpLogTypeEnum::Out
            && $httpLogDto->status === HttpLogStatusEnum::Failed
            && !in_array($httpLogDto->name, [HttpLogHeaderEnum::Unknown->value])
        ) ?: telegram(
            [
                'Warning' => 'Проблема в исходящем запросе',
                'Macro' => $httpLogDto->name,
                'Url' => $httpLogDto->url,
                'Code' => $httpLogDto->responseCode,
                'Message' => $httpLogDto->responseMessage,
                'Response' => json_decode($httpLogDto->responseData ?? '', true) ?? $httpLogDto->responseData,
                'Info' => $httpLogDto->info,
                'uuid' => $httpLogDto->uuid,
            ],
            TelegramTypeEnum::Warning,
        );
    }
}
