<?php

declare(strict_types=1);
namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Defaults\DefaultExceptionHandler;
use Atlcom\LaravelHelper\Tests\PackageTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты стандартных HTTP-ответов обработчика исключений пакета.
 */
final class DefaultExceptionHandlerTest extends PackageTestCase
{
    /**
     * Проверяет сохранение статуса 422 и errors bag при ошибке Laravel-валидации.
     */
    #[Test]
    public function renderReturnsValidationErrorsBag(): void
    {
        $request = Request::create('/parameters', 'PUT', server: ['HTTP_ACCEPT' => 'application/json']);
        $exception = $this->validationException();

        $response = app(DefaultExceptionHandler::class)->render($request, $exception);
        $content = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(422, $content['code']);
        $this->assertFalse($content['status']);
        $this->assertArrayHasKey('name', $content['errors']);
    }

    /**
     * Создает ошибку Laravel-валидации с одним обязательным полем.
     */
    private function validationException(): ValidationException
    {
        try {
            Validator::make(['name' => ''], ['name' => ['required']])->validate();
        } catch (ValidationException $exception) {
            return $exception;
        }

        throw new \RuntimeException('Ожидаемая ошибка валидации не создана.');
    }
}
