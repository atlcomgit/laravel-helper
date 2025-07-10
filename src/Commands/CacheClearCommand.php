<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Services\QueryCacheService;
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


    public function __construct(protected QueryCacheService $queryCacheService)
    {
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

        !lhConfig(ConfigEnum::QueryCache, 'enabled') ?: $this->queryCacheService->flushQueryCacheAll();
        !lhConfig(ConfigEnum::ViewCache, 'enabled') ?: Cache::flush();

        return self::SUCCESS;
    }
}
