<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataCommandDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetMyCommandsDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetWebhookDto;
use Atlcom\LaravelHelper\Services\QueueLogService;

/**
 * Консольная команда для бота телеграм
 * @example php artisan lh:telegram:bot setWebhook --telegram --log --cls
 */
class TelegramBotCommand extends DefaultCommand
{
    protected $signature = 'lh:telegram:bot
        {cmd : Команда бота }
    ';
    protected $description = 'Настройка телеграм бота';
    protected $isolated = true;
    protected ?bool $withConsoleLog = false;
    protected ?bool $withTelegramLog = false;


    public function __construct(protected QueueLogService $queueLogService)
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

        $command = $this->argument('cmd');
        switch ($command) {
            case 'setWebhook':
                $dto = TelegramBotOutSetWebhookDto::create()->dispatch();

                $this->telegramComment = "Установлен webhook: {$dto->url}";
                break;

            case 'setMyCommands':
                $dto = TelegramBotOutSetMyCommandsDto::create()
                    ->addCommand(
                        TelegramBotOutDataCommandDto::create(command: '/start', description: 'Главное меню'),
                    )
                    ->dispatch();

                $this->telegramComment = "Установлены команды: "
                    . implode(', ', $dto->commands->pluck('commands')->toArrayRecursive());
                break;

            default:
                $this->telegramComment = "Команда не найдена";

        }

        $this->telegramLog = isLocal() || isProd();

        $this->outputEol($this->telegramComment, 'fg=green');

        return self::SUCCESS;
    }
}
