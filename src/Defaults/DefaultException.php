<?php

namespace Atlcom\LaravelHelper\Defaults;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Atlcom\LaravelHelper\Defaults\DefaultExceptionHandler;

/**
 * Абстрактный класс исключений по умолчанию
 */
abstract class DefaultException extends Exception
{
    public const CODE = 400;
    public const MESSAGE = 'Непредвиденная ошибка';


    /**
     * Обработка исключения
     *
     * @param Request $request
     * @return Response|bool
     */
    public function render(Request $request): Response|bool
    {
        return (isDebug() || isProd() || $request->isJson())
            ? app(DefaultExceptionHandler::class)->render($request, $this)
            : false;
    }


    /**
     * Выбрасывает исключение
     *
     * @param string|null $message
     * @param int|null $code
     * @return void
     * @throws Exception
     */
    public static function except(?string $message = null, ?int $code = 0): void
    {
        throw new static($message ?? static::MESSAGE, $code ?? static::CODE);
    }
}
