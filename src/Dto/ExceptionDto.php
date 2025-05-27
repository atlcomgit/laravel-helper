<?php

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
// use Override;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class ExceptionDto extends Dto
{
    /**
     * Публичные свойства для сериализации
     */
    public int|string $code;
    public bool $status;
    public ?string $exception;
    public string $message;

    /**
     * Скрытые свойства для сериализации
     */
    public bool $isDebug;
    public ExceptionDebugDto $debugInfo;
    public string $uuid;
    public bool $isTelegram;


    /**
     * Создаёт dto из ошибки
     *
     * @param Throwable|HttpResponseException|QueryException $exception
     * @param Request|null $request
     * @return static
     */
    public static function createFromException(Throwable $exception, ?Request $request = null): static
    {
        // Формируем dto об ошибке
        $code = $exception->getCode() ?: match ($exception::class) {
            HttpException::class => $exception->getStatusCode(),
            HttpResponseException::class => $exception->getResponse()->getStatusCode(),

            default => 0,
        };
        $message = match ($exception::class) {
            HttpResponseException::class => json_decode($exception->getResponse()->getContent())?->message
            ?: ($exception->getResponse()::$statusTexts[$exception->getResponse()->getStatusCode()] ?? ''),
            QueryException::class => Hlp::stringDeleteMultiples(
                Hlp::stringReplace(Hlp::stringConcat(', ', $exception->errorInfo), [PHP_EOL => ' ']),
                ' ',
            ),
            ValidationException::class => (isDebug() || isLocal() || isTesting())
            ? Hlp::stringConcat(': ', Hlp::cacheRuntimeGet('ValidationRequest'), $exception->getMessage())
            : $exception->getMessage(),

            default => '',
        } ?: $exception->getMessage();

        $thisDto = self::create(
            exception: $exception::class,
            code: $code,
            message: $message,
            debugInfo: [
                'file' => $exception->getFile() . ':' . $exception->getLine(),
                'trace' => $exception->getTrace(),
                'request' => $request ?? request(),
                'throw' => $exception,
                'data' => [] ?: null,
            ],
        );

        // Отправляем в телеграм, кроме указанных ошибок
        if (
            !in_array($exception::class, [
                    // ModelNotFoundException::class,
                MaxAttemptsExceededException::class,
            ])
            && !is_subclass_of($exception, WithoutTelegramException::class)
            && !($exception instanceof WithoutTelegramException)
            && ($thisDto->code < 100 || $thisDto->code >= 400)
        ) {
            $route = ($request ??= request())?->route();

            telegram(
                $thisDto->response(isTelegram: true)->getContent(),
                Hlp::intervalBetween($thisDto->code, [400, 499]) ? TelegramTypeEnum::Warning : TelegramTypeEnum::Error,
                [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'uuid' => $thisDto->uuid,
                    'debugData' => [
                        'uri' => TelegramLogDto::getMethod() . ' ' . TelegramLogDto::getUri(),
                        'controller' => $route
                            ? class_basename($route->getControllerClass()) . '::' . $route->getActionMethod() . '()'
                            : null,
                        'project' => config('app.name'),
                        'env' => config('app.env'),
                        'exception' => $exception::class,
                        'message' => $thisDto->message,
                        'file' => $thisDto->debugInfo->file,
                        'trace' => $thisDto->debugInfo->trace,
                        ...TelegramLogDto::getDebugData(),
                        ...($exception instanceof HttpResponseException
                            ? ['content' => $exception->getResponse()->getContent()]
                            : []
                        ),
                        ...($exception instanceof QueryException
                            ? [
                                'sql' => Hlp::stringDeleteMultiples(
                                    Hlp::stringReplace($exception->getRawSql(), ['"' => '']),
                                    ' ',
                                ),
                            ]
                            : []
                        ),
                        'user_id' => user()?->id,
                    ],
                ],
            );
        }

        return $thisDto;
    }


    /**
     * Возвращает массив преобразований свойств
     *
     * @return array
     */
    // #[Override()]
    protected function mappings(): array
    {
        return [
            'isDebug' => 'is_debug',
            'debugInfo' => 'debug_info',
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
        return [
            'code' => 400,
            'status' => false,
            'message' => 'Undefined message',
            'isDebug' => isDebug(),
            'uuid' => Str::uuid()->toString(),
            'isTelegram' => false,
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
        return [
            'code' => static fn ($v) => is_numeric($v) ? (int)$v : (string)$v,
            'debugInfo' => ExceptionDebugDto::class,
        ];
    }


    /**
     * Метод вызывается после заполнения dto
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onFilled(array $array): void
    {
        $this->code = match ($this->exception) {
            HttpResponseException::class, ValidationException::class => 400,

            AuthenticationException::class => 401,

            RouteNotFoundException::class,
            NotFoundHttpException::class,
            MethodNotAllowedException::class,
            MethodNotAllowedHttpException::class
            => 404,

            QueryException::class => 500,

            'Error' => 500,

            default => $this->code,
        };
        (is_integer($this->code) && $this->code >= 100 && $this->code < 600) ?: $this->code = 500;

        $this->message =
            match ($this->exception) {
                ClientException::class,
                RequestException::class => $this->getMessage(
                    $this->debugInfo?->throw?->getResponse()->getBody()->getContents(),
                ),
                MethodNotAllowedHttpException::class => $this->getMessage('Маршрут :route не поддерживает :method'),
                NotFoundHttpException::class => $this->getMessage('Маршрут :route не найден'),
                AuthenticationException::class => $this->getMessage('Маршрут :route требует аутентификацию'),
                // HttpResponseException::class => $this->getMessage('Маршрут :route требует аутентификацию'),

                default => $this->getMessage($this->message),
            }
            ?: match (true) {
                $this->code === 403 => $this->getMessage('Требуется аутентификация'),

                default => '',
            }
        ;

        $this->exception = $this->toBasename($this->exception);
    }


    /**
     * Возвращает локализованное сообщение
     *
     * @param string $localeKey
     * @return string
     */
    protected function getMessage(string $localeKey): string
    {
        $replaces = [
            'route' => ($this->debugInfo->request ?? null)?->getRequestUri(),
            'method' => ($this->debugInfo->request ?? null)?->getMethod(),
            'class' => $this->toBasename($this::class),
            'property' => ':attribute',
        ];

        return __($localeKey, $replaces);
    }


    /**
     * Метод вызывается во время исключения при заполнении dto
     *
     * @param Throwable $exception
     * @return void
     * @throws \Exception
     */
    // #[Override()]
    protected function onException(Throwable $exception): void
    {
        throw $exception; // DtoException::except('');
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
        $this->debugInfo->customOptions(['isTelegram' => $this->isTelegram]);
        $this->mappingKeys($this->mappings())
            ->onlyNotNull()
            ->serializeKeys(true)
            ->excludeKeys((isDebug() || $this->isTelegram || isLocal()) ? [] : ['exception'])
            ->excludeKeys((isDebug() || $this->isTelegram) ? [] : ['isDebug'])
            ->excludeKeys((isDebug() || isDebugTrace() || $this->isTelegram) ? [] : ['debugInfo'])
            ->excludeKeys(['isTelegram'])
        ;
    }


    /**
     * Возвращает json ответ
     *
     * @param bool $isRender
     * @return JsonResponse
     */
    public function response(bool $isRender = false, bool $isTelegram = false): JsonResponse
    {
        $this->isTelegram = $isTelegram;
        $array = $this->toArray();
        $code = $this->code ?: 400;
        !$isRender ?: $array['message'] = $this->getResponseMessage($array['message']);
        $this->isTelegram = false;

        return response()->json($array, $code, [], Hlp::jsonFlags());
    }


    /**
     * Возвращает текст сообщения об ошибке
     *
     * @param string $message
     * @return string
     */
    public function getResponseMessage(string $message): string
    {
        $code = $this->code ?: 400;

        // Если ошибка не 500, то отдаем текст ошибки на фронт
        // Если ошибка 500, то отдаем 'exceptions.Unknown', а для телеги отдаем текст ошибки
        return match (true) {
            $code < 500 => $message,

            default =>
            "Ошибка: {$this->uuid}" . PHP_EOL
            . 'Причина: ' . ((isDebug() || $this->isTelegram) ? $message : 'Непредвиденная ошибка') . PHP_EOL
            . "Код: {$code}"
        };
        ;
    }
}
