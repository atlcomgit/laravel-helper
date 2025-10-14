<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Services\RouteLogService;

/**
 * Консольная команда очистки логов роутов
 */
class RouteLogCleanupCommand extends DefaultCommand
{
    protected $signature = 'lh:cleanup:route_log';
    protected $description = 'Очистка логов роутов от laravel-helper';
    protected $isolated = true;
    protected ?bool $withConsoleLog = false;
    protected ?bool $withTelegramLog = false;


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
        $this->outputBold($this->description);
        $this->outputEol();

        $cleanup = $this->routeLogService->cleanup();

        $this->telegramLog = (isLocal() || isDev() || isProd()) && $cleanup > 0;
        $this->telegramComment = 'Зарегистрировано ' . Hlp::stringPlural($cleanup, ['роутов', 'роут', 'роута']);

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
