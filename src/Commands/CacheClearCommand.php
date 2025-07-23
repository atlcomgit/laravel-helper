<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\HttpCacheService;
use Atlcom\LaravelHelper\Services\QueryCacheService;
use Atlcom\LaravelHelper\Services\ViewCacheService;
use Illuminate\Support\Facades\Cache;

/**
 * Консольная команда cache:clear (очистка кеша laravel-helper)
 */
class CacheClearCommand extends DefaultCommand
{
    protected $signature = 'lh:clear:cache';
    protected $description = 'Очистка кеша';
    protected $isolated = true;
    protected ?bool $withConsoleLog = false;
    protected ?bool $withTelegramLog = false;


    public function __construct(
        protected HttpCacheService $httpCacheService,
        protected QueryCacheService $queryCacheService,
        protected ViewCacheService $viewCacheService,
    ) {
        parent::__construct();
    }


    /**
     * Обработчик команды
     *
     * @return int
     */
    public function handle(): int
    {
        $this->outputBold($this->description);
        $this->outputEol();

        !Lh::config(ConfigEnum::HttpCache, 'enabled') ?: $this->httpCacheService->flushHttpCacheAll();
        !Lh::config(ConfigEnum::QueryCache, 'enabled') ?: $this->queryCacheService->flushQueryCacheAll();
        !Lh::config(ConfigEnum::ViewCache, 'enabled') ?: $this->viewCacheService->flushViewCacheAll();

        return self::SUCCESS;
    }
}
