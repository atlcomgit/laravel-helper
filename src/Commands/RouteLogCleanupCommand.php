<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\RouteLogService;

/**
 * Консольная команда очистки логов роутов
 */
class RouteLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'cleanup:route_logs';
    protected $description = 'Очистка логов роутов';
    protected $isolated = true;


    public function __construct(protected RouteLogService $routeLogService)
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
        $this->consoleClear();
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->routeLogService->cleanup();

        $this->telegramEnabled = (isLocal() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Зарегистрировано ' . Helper::stringPlural($cleanup, ['роутов', 'роут', 'роута']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
