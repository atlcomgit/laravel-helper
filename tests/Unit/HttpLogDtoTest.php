<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\HttpLogDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\HttpLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\HttpLogJob;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Request as RequestOut;
use Illuminate\Http\Client\Response as ResponseOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты классификации статусов, размера и ограничения данных HttpLogDto
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
     * Проверяет, что большое тело ответа обрезается до data_max_length без разрыва символов UTF-8
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::createByResponse()
     *
     * @return void
     */
    #[Test]
    public function createByResponseTruncatesLargeResponseData(): void
    {
        Config::set('laravel-helper.' . ConfigEnum::HttpLog->value . '.data_max_length', 15);
        $content = str_repeat('Ж', 20);

        $dto = HttpLogDto::createByResponse(
            uuid: 'test-uuid',
            request: $this->makeRequest(),
            response: new Response(content: $content, status: 200),
        );

        $this->assertSame(str_repeat('Ж', 7), $dto->responseData);
        $this->assertTrue($dto->info['response_data_truncated']);
        $this->assertSame(20, $dto->size);
    }


    /**
     * Проверяет, что при data_max_length = 0 тело ответа не обрезается
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::createByResponse()
     *
     * @return void
     */
    #[Test]
    public function createByResponseKeepsResponseDataWithoutLimit(): void
    {
        Config::set('laravel-helper.' . ConfigEnum::HttpLog->value . '.data_max_length', 0);
        $content = str_repeat('Ж', 20);

        $dto = HttpLogDto::createByResponse(
            uuid: 'test-uuid',
            request: $this->makeRequest(),
            response: new Response(content: $content, status: 200),
        );

        $this->assertSame($content, $dto->responseData);
        $this->assertArrayNotHasKey('response_data_truncated', $dto->info);
    }


    /**
     * Проверяет, что большое тело входящего запроса обрезается до data_max_length, а размер считается по полному
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::createByRequest()
     *
     * @return void
     */
    #[Test]
    public function createByRequestTruncatesLargeRequestData(): void
    {
        Config::set('laravel-helper.' . ConfigEnum::HttpLog->value . '.data_max_length', 10);
        $request = Request::create(
            uri: 'https://example.test/api/upload',
            method: 'POST',
            content: str_repeat('a', 100),
        );

        $dto = HttpLogDto::createByRequest(uuid: 'test-uuid', request: $request);

        $this->assertSame(str_repeat('a', 10), $dto->requestData);
        $this->assertTrue($dto->info['request_data_truncated']);
        $this->assertSame(Hlp::sizeBytesToString(100), $dto->info['request_data_size']);
    }


    /**
     * Проверяет, что ответ-файл, удалённый после отправки, логируется по заголовкам без исключения
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::createByResponse()
     *
     * @return void
     */
    #[Test]
    public function createByResponseUsesHeadersWhenBinaryFileIsDeleted(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'http-log-dto-');
        file_put_contents($path, 'vin;price');
        $request = $this->makeRequest();
        $response = (new BinaryFileResponse($path, 200, ['Content-Type' => 'text/csv']))->deleteFileAfterSend();
        $response->prepare($request);
        // Отправка ответа удаляет файл до записи лога в terminate
        unlink($path);

        $dto = HttpLogDto::createByResponse(uuid: 'test-uuid', request: $request, response: $response);

        $this->assertSame(9, $dto->size);
        $this->assertStringContainsString('text/csv', (string)$dto->responseData);
        $this->assertSame(HttpLogStatusEnum::Success, $dto->status);
    }


    /**
     * Проверяет, что без ключа data_max_length в конфиге приложения действует размер по умолчанию
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::createByResponse()
     *
     * @return void
     */
    #[Test]
    public function createByResponseUsesDefaultLimitWithoutConfigKey(): void
    {
        $config = config('laravel-helper.' . ConfigEnum::HttpLog->value);
        unset($config['data_max_length']);
        Config::set('laravel-helper.' . ConfigEnum::HttpLog->value, $config);

        $dto = HttpLogDto::createByResponse(
            uuid: 'test-uuid',
            request: $this->makeRequest(),
            response: new Response(content: str_repeat('a', HttpLogDto::DATA_MAX_LENGTH + 10), status: 200),
        );

        $this->assertSame(HttpLogDto::DATA_MAX_LENGTH, strlen((string)$dto->responseData));
        $this->assertTrue($dto->info['response_data_truncated']);
    }


    /**
     * Проверяет обрезку тела исходящего запроса и ответа
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::createByResponse()
     *
     * @return void
     */
    #[Test]
    public function createByResponseTruncatesOutgoingRequestAndResponse(): void
    {
        Config::set('laravel-helper.' . ConfigEnum::HttpLog->value . '.data_max_length', 10);

        $dto = HttpLogDto::createByResponse(
            uuid: 'test-uuid',
            request: new RequestOut(new PsrRequest('POST', 'https://example.test/api', [], str_repeat('a', 100))),
            response: new ResponseOut(new PsrResponse(200, [], str_repeat('b', 100))),
        );

        $this->assertSame(str_repeat('a', 10), $dto->requestData);
        $this->assertSame(str_repeat('b', 10), $dto->responseData);
        $this->assertTrue($dto->info['request_data_truncated']);
        $this->assertTrue($dto->info['response_data_truncated']);
    }


    /**
     * Проверяет, что обрезанный JSON при сериализации лога сохраняется началом тела не длиннее 2048 символов
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::__serialize()
     *
     * @return void
     */
    #[Test]
    public function serializeKeepsPrefixOfTruncatedJson(): void
    {
        Config::set('laravel-helper.' . ConfigEnum::HttpLog->value . '.data_max_length', 5000);
        $content = json_encode(['items' => array_fill(0, 1000, 'значение')], JSON_UNESCAPED_UNICODE);

        $dto = HttpLogDto::createByResponse(
            uuid: 'test-uuid',
            request: $this->makeRequest(),
            response: new Response(content: $content, status: 200),
        );
        serialize($dto);

        $this->assertStringStartsWith('{"items":["значение"', (string)$dto->responseData);
        $this->assertLessThanOrEqual(2048, mb_strlen((string)$dto->responseData));
    }


    /**
     * Проверяет, что для существующего файла ответа размер и тип берутся из файла
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::createByResponse()
     *
     * @return void
     */
    #[Test]
    public function createByResponseUsesExistingBinaryFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'http-log-dto-');
        file_put_contents($path, 'vin;price');

        try {
            $dto = HttpLogDto::createByResponse(
                uuid: 'test-uuid',
                request: $this->makeRequest(),
                response: new BinaryFileResponse($path, 200),
            );
        } finally {
            unlink($path);
        }

        $this->assertSame(9, $dto->size);
        $this->assertStringContainsString('text/plain', (string)$dto->responseData);
    }


    /**
     * Проверяет исключение по маркеру за лимитом без сохранения полного тела и отправки задания.
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::notFoundConfigExclude()
     *
     * @param string $direction
     * @param string $body
     * @param bool $withResponse
     * @return void
     */
    #[Test]
    #[DataProvider('bodyExclusionScenarios')]
    public function truncatedBodyPreservesExclusion(string $direction, string $body, bool $withResponse): void
    {
        // Полный текст существует только в синтетическом HTTP-сообщении, очередь подменена.
        Bus::fake();
        Config::set('laravel-helper.http_log.enabled', true);
        Config::set("laravel-helper.http_log.{$direction}.enabled", true);
        Config::set("laravel-helper.http_log.{$direction}.exclude", ['/DO_NOT_LOG/']);
        Config::set('laravel-helper.http_log.data_max_length', HttpLogDto::DATA_MAX_LENGTH);
        $content = str_repeat('a', HttpLogDto::DATA_MAX_LENGTH + 10) . 'DO_NOT_LOG';
        $dto = $this->makeLogWithBody($direction, $body, $content, $withResponse);

        $this->assertSame(HttpLogDto::DATA_MAX_LENGTH, strlen($dto->{$body . 'Data'}));
        $this->assertFalse(Lh::canDispatch($dto));
        $dto->dispatch();
        Bus::assertNotDispatched(HttpLogJob::class);

        // Сериализация очереди не возвращает полное тело; восстановление сохраняет рабочие поля DTO.
        $serialized = serialize($dto);
        $restored = unserialize($serialized);
        $this->assertStringNotContainsString('DO_NOT_LOG', $serialized);
        $this->assertInstanceOf(HttpLogDto::class, $restored);
        $this->assertSame($dto->requestData, $restored->requestData);
        $this->assertSame($dto->responseData, $restored->responseData);
        $this->assertFalse(Lh::canDispatch($restored));
    }


    /**
     * Проверяет, что обрезанный префикс не создаёт совпадение, которого не было в полном теле.
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::notFoundConfigExclude()
     *
     * @param string $direction
     * @param string $body
     * @param bool $withResponse
     * @return void
     */
    #[Test]
    #[DataProvider('bodyExclusionScenarios')]
    public function truncatedPrefixDoesNotCreateExclusion(string $direction, string $body, bool $withResponse): void
    {
        // Шаблон совпадает ровно с префиксом в 32 байта, но не с исходными 64 байтами.
        Bus::fake();
        Config::set('laravel-helper.http_log.enabled', true);
        Config::set("laravel-helper.http_log.{$direction}.enabled", true);
        Config::set("laravel-helper.http_log.{$direction}.exclude", ["/^{$body}Data=a{32}$/"]);
        Config::set('laravel-helper.http_log.data_max_length', 32);
        $dto = $this->makeLogWithBody($direction, $body, str_repeat('a', 64), $withResponse);

        $this->assertTrue(Lh::canDispatch($dto));
        $dto->dispatch();
        Bus::assertDispatched(HttpLogJob::class);
        $this->assertTrue(Lh::canDispatch(unserialize(serialize($dto))));
    }


    /**
     * Проверяет сброс результата исключения при замене тела через merge и прямое присвоение.
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::notFoundConfigExclude()
     *
     * @param string $direction
     * @param string $body
     * @param bool $withResponse
     * @return void
     */
    #[Test]
    #[DataProvider('bodyExclusionScenarios')]
    public function truncatedBodyRechecksReplacement(string $direction, string $body, bool $withResponse): void
    {
        // Сначала заменяем разрешённое тело на исключённое, затем проверяем обратный переход.
        Config::set('laravel-helper.http_log.enabled', true);
        Config::set("laravel-helper.http_log.{$direction}.enabled", true);
        Config::set("laravel-helper.http_log.{$direction}.exclude", ['/DO_NOT_LOG/']);
        Config::set('laravel-helper.http_log.data_max_length', 32);
        foreach ([true, false] as $useMerge) {
            $dto = $this->makeLogWithBody($direction, $body, str_repeat('a', 64), $withResponse);
            if ($useMerge) {
                $dto->merge([$body . '_data' => 'DO_NOT_LOG']);
            } else {
                $dto->{$body . 'Data'} = 'DO_NOT_LOG';
            }
            $this->assertFalse(Lh::canDispatch($dto));

            $dto = $this->makeLogWithBody($direction, $body, str_repeat('a', 64) . 'DO_NOT_LOG', $withResponse);
            $dto->merge([$body . 'Data' => $dto->{$body . 'Data'}]);
            $this->assertFalse(Lh::canDispatch($dto), 'Повторное присвоение префикса не отменяет прежнее решение.');
            if ($useMerge) {
                $dto->merge([$body . 'Data' => 'safe']);
            } else {
                $dto->{$body . 'Data'} = 'safe';
            }
            $this->assertTrue(Lh::canDispatch($dto));
        }
    }


    /**
     * Проверяет, что после усечения по-прежнему учитываются позднее добавленные сведения об ошибке.
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::notFoundConfigExclude()
     *
     * @return void
     */
    #[Test]
    public function truncatedBodyKeepsExclusionsForUpdatedMetadata(): void
    {
        // Обработчик ConnectionFailed добавляет сообщение уже после создания DTO запроса.
        Config::set('laravel-helper.http_log.enabled', true);
        Config::set('laravel-helper.http_log.out.enabled', true);
        Config::set('laravel-helper.http_log.out.exclude', ['/responseMessage=Connection failed/']);
        Config::set('laravel-helper.http_log.data_max_length', 32);
        $dto = $this->makeLogWithBody('out', 'request', str_repeat('a', 64), false);

        $this->assertTrue(Lh::canDispatch($dto));
        $dto->merge(['responseMessage' => 'Connection failed']);
        $this->assertFalse(Lh::canDispatch($dto));
    }


    /**
     * Проверяет прежнюю фильтрацию неусечённого тела, в том числе при выключенном лимите.
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::notFoundConfigExclude()
     *
     * @return void
     */
    #[Test]
    public function untruncatedBodyKeepsExistingExclusion(): void
    {
        // Обе настройки оставляют короткое тело без изменений; используются прежние wildcard-исключения.
        Config::set('laravel-helper.http_log.enabled', true);
        Config::set('laravel-helper.http_log.in.enabled', true);
        Config::set('laravel-helper.http_log.in.exclude', ['*DO_NOT_LOG*']);

        foreach ([0, HttpLogDto::DATA_MAX_LENGTH] as $limit) {
            Config::set('laravel-helper.http_log.data_max_length', $limit);
            $dto = $this->makeLogWithBody('in', 'request', 'DO_NOT_LOG', true);
            $this->assertSame('DO_NOT_LOG', $dto->requestData);
            $this->assertFalse(Lh::canDispatch($dto));
            $this->assertFalse(Lh::canDispatch(HttpLogDto::create($dto->toArray())));
        }
    }


    /**
     * Проверяет сериализацию большого JSON с настроенными исключениями без удержания исходного тела.
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::rememberDataExclusion()
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::__serialize()
     *
     * @return void
     */
    #[Test]
    public function largeResponseKeepsOnlyBoundedPayload(): void
    {
        // Формируем около 14 МиБ валидного JSON без промежуточного массива и внешних ресурсов.
        Config::set('laravel-helper.http_log.enabled', true);
        Config::set('laravel-helper.http_log.in.enabled', true);
        Config::set('laravel-helper.http_log.in.exclude', ['/DO_NOT_LOG/']);
        Config::set('laravel-helper.http_log.data_max_length', HttpLogDto::DATA_MAX_LENGTH);
        $content = '[' . str_repeat('{"value":"aaaaaaaaaaaa"},', 610000) . '{}]';
        $dto = $this->makeLogWithBody('in', 'response', $content, true);

        $this->assertGreaterThan(14 * 1024 * 1024, strlen($content));
        $this->assertSame(HttpLogDto::DATA_MAX_LENGTH, strlen($dto->responseData));
        $this->assertTrue(Lh::canDispatch($dto));
        $serialized = serialize($dto);
        $this->assertLessThanOrEqual(2048, mb_strlen($dto->responseData));
        $this->assertLessThan(strlen($content), strlen($serialized));
    }


    /**
     * Проверяет, что выключенный журнал не выполняет сопоставление большого тела с wildcard-шаблонами.
     * @see \Atlcom\LaravelHelper\Dto\HttpLogDto::rememberDataExclusion()
     *
     * @return void
     */
    #[Test]
    public function disabledLoggingDoesNotEvaluateBodyExclusions(): void
    {
        // На PHP 8.5 fnmatch ограничен 4096 символами; отключённое логирование не должно вызывать его.
        Config::set('laravel-helper.http_log.out.exclude', ['*DO_NOT_LOG*']);
        Config::set('laravel-helper.http_log.data_max_length', 32);

        foreach ([[false, true], [true, false]] as [$enabled, $outEnabled]) {
            Config::set('laravel-helper.http_log.enabled', $enabled);
            Config::set('laravel-helper.http_log.out.enabled', $outEnabled);
            $dto = $this->makeLogWithBody('out', 'request', str_repeat('a', 8192), true);
            $this->assertSame(32, strlen($dto->requestData));
            $this->assertFalse(Lh::canDispatch($dto));
        }
    }


    /**
     * Возвращает направления HTTP и этапы, на которых необходимо учитывать полное тело.
     *
     * @return array<string, array{0:string, 1:string, 2:bool}>
     */
    public static function bodyExclusionScenarios(): array
    {
        return [
            'inbound request before response' => ['in', 'request', false],
            'outbound request before response' => ['out', 'request', false],
            'inbound request after response' => ['in', 'request', true],
            'outbound request after response' => ['out', 'request', true],
            'inbound response' => ['in', 'response', true],
            'outbound response' => ['out', 'response', true],
        ];
    }


    /**
     * Создаёт DTO из синтетических HTTP-сообщений без сети; заголовки заданы для обоих направлений.
     *
     * @param string $direction
     * @param string $body
     * @param string $content
     * @param bool $withResponse
     * @return HttpLogDto
     */
    private function makeLogWithBody(
        string $direction,
        string $body,
        string $content,
        bool $withResponse,
    ): HttpLogDto {
        // Заполняем проверяемое тело, оставляя второе коротким и не совпадающим с исключениями.
        $requestData = $body === 'request' ? $content : 'request';
        $responseData = $body === 'response' ? $content : 'response';
        $headers = ['Content-Type' => 'text/plain'];
        $request = $direction === 'in'
            ? Request::create('https://example.test/api', 'POST', content: $requestData)
            : new RequestOut(new PsrRequest('POST', 'https://example.test/api', $headers, $requestData));

        if (!$withResponse) {
            return HttpLogDto::createByRequest('test-uuid', $request);
        }

        $response = $direction === 'in'
            ? new Response($responseData)
            : new ResponseOut(new PsrResponse(200, $headers, $responseData));

        return HttpLogDto::createByResponse('test-uuid', $request, $response);
    }


    /**
     * Возвращает набор успешных входящих статусов
     *
     * @return array<string, array{0:int}>
     */
    public static function successfulInboundStatusCodes(): array
    {
        return [
            'ok'         => [200],
            'created'    => [201],
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
            'redirect'             => [302],
            'unprocessable_entity' => [422],
            'server_error'         => [500],
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
