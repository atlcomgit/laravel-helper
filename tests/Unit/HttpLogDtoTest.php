<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты классификации статусов HttpLogDto
 */
final class HttpLogDtoTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelHelperServiceProvider::class,
        ];
    }


    /**
     * Проверяет, что входящие успешные коды 2xx помечаются как success
     *
     * @param int $statusCode
     * @return void
     */
    #[Test]
    #[DataProvider('successfulInboundStatusCodes')]
    public function createByResponseMarksInbound2xxAsSuccess(int $statusCode): void
    {
        $dto = HttpLogDto::createByResponse(
            uuid: 'test-uuid',
            request: $this->makeRequest(),
            response: new Response(
                content: json_encode(['status' => $statusCode], JSON_THROW_ON_ERROR),
                status: $statusCode,
                headers: ['Content-Type' => 'application/json'],
            ),
        );

        $this->assertSame($statusCode, $dto->responseCode);
        $this->assertSame(HttpLogStatusEnum::Success, $dto->status);
    }


    /**
     * Проверяет, что входящие неуспешные коды помечаются как failed
     *
     * @param int $statusCode
     * @return void
     */
    #[Test]
    #[DataProvider('failedInboundStatusCodes')]
    public function createByResponseMarksInboundNon2xxAsFailed(int $statusCode): void
    {
        $dto = HttpLogDto::createByResponse(
            uuid: 'test-uuid',
            request: $this->makeRequest(),
            response: new Response(
                content: json_encode(['status' => $statusCode], JSON_THROW_ON_ERROR),
                status: $statusCode,
                headers: ['Content-Type' => 'application/json'],
            ),
        );

        $this->assertSame($statusCode, $dto->responseCode);
        $this->assertSame(HttpLogStatusEnum::Failed, $dto->status);
    }


    /**
     * Возвращает набор успешных входящих статусов
     *
     * @return array<string, array{0:int}>
     */
    public static function successfulInboundStatusCodes(): array
    {
        return [
            'ok' => [200],
            'created' => [201],
            'no_content' => [204],
        ];
    }


    /**
     * Возвращает набор неуспешных входящих статусов
     *
     * @return array<string, array{0:int}>
     */
    public static function failedInboundStatusCodes(): array
    {
        return [
            'redirect' => [302],
            'unprocessable_entity' => [422],
            'server_error' => [500],
        ];
    }


    /**
     * Создаёт входящий HTTP-запрос для тестов классификации ответа
     *
     * @return Request
     */
    private function makeRequest(): Request
    {
        return Request::create(
            uri: 'https://example.test/api/auth/recommendation',
            method: 'POST',
            content: json_encode(['title' => 'Test'], JSON_THROW_ON_ERROR),
            server: ['CONTENT_TYPE' => 'application/json'],
        );
    }
}