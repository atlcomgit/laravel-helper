<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use ArgumentCountError;
use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultException;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Atlcom\LaravelHelper\Events\ExceptionEvent;
use Atlcom\LaravelHelper\Exceptions\WithoutTelegramException;
use Atlcom\LaravelHelper\Facades\Lh;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

/**
 * @internal
 * Dto исключений
 */
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
        (($code = $exception->getCode()) >= 100)
            ?: $code = match ($exception::class) {
                HttpException::class => $exception->getStatusCode(),
                HttpResponseException::class => $exception->getResponse()->getStatusCode(),
                'League\OAuth2\Server\Exception\OAuthServerException' => $exception->getHttpStatusCode(),

                default => 0,
            };
        $message = match ($exception::class) {
            HttpResponseException::class => json_decode($exception->getResponse()->getContent())?->message
            ?: ($exception->getResponse()::$statusTexts[$exception->getResponse()->getStatusCode()] ?? ''),
            QueryException::class => Hlp::stringDeleteMultiples(
                Hlp::stringReplace(Hlp::stringConcat(', ', $exception->errorInfo), [PHP_EOL => ' ']),
                ' ',
            ),
            ValidationException::class => (isDebug() || isLocal() || isDev() || isTesting())
            ? Hlp::stringConcat(': ', Hlp::cacheRuntimeGet('ValidationRequest'), $exception->getMessage())
            : $exception->getMessage(),

            FatalError::class,
            ArgumentCountError::class => Hlp::stringSplit($exception->getMessage(), 'Stack trace:', 0),

            default => '',
        } ?: match (true) {
            $exception->getPrevious() instanceof ModelNotFoundException
            => ($exception = $exception->getPrevious())->getMessage(),

            default => $exception->getMessage(),
        };

        $thisDto = static::create(
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
        $exclude = Lh::config(ConfigEnum::TelegramLog, TelegramTypeEnum::Error->value . '.exclude');
        if (
            (
                isLocal()
                || isDev()
                || !in_array($exception::class, [
                        // ModelNotFoundException::class,
                    MaxAttemptsExceededException::class,
                    'League\OAuth2\Server\Exception\OAuthServerException',
                ])
            )
            && !(is_array($exclude) && in_array($exception::class, $exclude))
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
                            ? class_basename($route?->getControllerClass()) . '::' . $route?->getActionMethod() . '()'
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
                                    sql($exception->getSql(), $exception->getBindings()),
                                    ' ',
                                ),
                            ]
                            : []
                        ),
                        'user_id' => (string)user(returnOnlyId: true),
                    ],
                ],
            );
        }

        event(new ExceptionEvent($thisDto));

        return $thisDto;
    }


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
            'isDebug' => 'is_debug',
            'debugInfo' => 'debug_info',
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
        return [
            'code' => 400,
            'status' => false,
            'message' => 'Undefined message',
            'isDebug' => isDebug(),
            'uuid' => uuid(),
            'isTelegram' => false,
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
        return [
            'code' => static fn ($v) => is_numeric($v) ? (int)$v : (string)$v,
            'debugInfo' => ExceptionDebugDto::class,
        ];
    }


    /**
     * @inheritDoc
     * @see parent::onFilled()
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

            '\League\OAuth2\Server\Exception\OAuthServerException'
            => (
                ($this->debugInfo->throw && method_exists($this->debugInfo->throw, 'getHttpStatusCode'))
                ? $this->debugInfo->throw->getHttpStatusCode()
                : 0
            ) ?: 401,

            RouteNotFoundException::class,
            ModelNotFoundException::class,
            MethodNotAllowedException::class,
            MethodNotAllowedHttpException::class,
            NotFoundHttpException::class
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
                MethodNotAllowedHttpException::class
                => $this->getMessage('Маршрут :route не поддерживает метод :method'),
                ModelNotFoundException::class => $this->getMessage('Запись :model не найдена'),
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
            'model' => ($this->debugInfo->throw && method_exists($this->debugInfo->throw, 'getModel'))
                ? Hlp::pathClassName($this->debugInfo->throw->getModel() ?? '')
                : '',
            'route' => ($this->debugInfo->request && method_exists($this->debugInfo->request, 'getRequestUri'))
                ? ($this->debugInfo->request->getRequestUri() ?? '')
                : '',
            'method' => ($this->debugInfo->request && method_exists($this->debugInfo->request, 'getMethod'))
                ? ($this->debugInfo->request->getMethod() ?? '')
                : '',
            'class' => $this->toBasename($this::class),
            'property' => ':attribute',
        ];

        return __($localeKey, $replaces);
    }


    /**
     * @inheritDoc
     * @see parent::onException()
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
     * @inheritDoc
     * @see parent::onSerializing()
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
            ->excludeKeys((isDebug() || $this->isTelegram || isLocal() || isDev()) ? [] : ['exception'])
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
            . 'Причина: ' . (
                (isDebug() || isTesting() || $this->isTelegram) ? $message : DefaultException::MESSAGE
            ) . PHP_EOL
            . "Код: {$code}"
        };
        ;
    }
}