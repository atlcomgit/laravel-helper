<?php

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ExceptionDto;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\MaxAttemptsExceededException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Класс обработки исключений
 */
class DefaultExceptionHandler extends Handler
{
    protected ExceptionDto $exceptionDto;
    protected bool $isStorageRoute;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     * This is a great spot to send exceptions to Sentry.
     * @see parent::report()
     *
     * @param Throwable $e
     * @return void
     */
    public function report(Throwable $e)
    {
        // Формируем Dto об ошибке, если это не ошибка роута
        in_array($e::class, [
            NotFoundHttpException::class,
            MaxAttemptsExceededException::class,
        ])
            ?: $this->exceptionDto ??= ExceptionDto::createFromException(exception: $e);

        parent::report($e);
    }


    /**
     * Обработчик общих исключений
     * @see parent::render()
     *
     * @param Request $request
     * @param Throwable|HttpResponseException $e
     * @return JsonResponse|Response|StreamedResponse
     */
    public function render($request, Throwable $e)
    {
        try {
            // Если запрос картинки и она не найдена
            if (
                !$request->wantsJson()
                && str_contains($request->url(), 'storage/images')
                && $e instanceof NotFoundHttpException
            ) {
                return response()->stream(
                    static function () {
                        $fileNotFound = resource_path('images/image_not_found.png');
                        echo file_exists($fileNotFound)
                            ? file_get_contents($fileNotFound)
                            : '';
                    },
                    isProd() ? 404 : 200,
                    ['Content-type' => 'image/png'],
                );
            }

            // Формируем Dto об ошибке
            $this->exceptionDto ??= ExceptionDto::createFromException($e, $request);
            $response = $this->exceptionDto->response(isRender: true);

        } catch (Throwable $e) {
            $isAppDebug = isDebug();
            $isAppTrace = isDebugTrace();
            $debugInfo = [
                ...($isAppDebug ? ['file' => $e->getFile() . ':' . $e->getLine()] : []),
                ...($isAppTrace ? ['trace' => $e->getTrace()] : []),
            ];
            $response = response()->json([
                'code' => 500,
                'message' => __($e->getMessage()),
                ...($debugInfo ?: $debugInfo),
            ], 500, [], Hlp::jsonFlags());
        }

        return (true || $request->wantsJson())
            ? $response
            : parent::render($request, $e);
    }
}
