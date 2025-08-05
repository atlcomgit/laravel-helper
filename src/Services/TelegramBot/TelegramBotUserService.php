<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotUserDto;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotUserRepository;

/**
 * @internal
 * Сервис пользователя телеграм бота
 */
class TelegramBotUserService extends DefaultService
{
    public function __construct(private TelegramBotUserRepository $telegramBotUserRepository) {}


    /**
     * Сохраняет пользователя телеграм бота
     *
     * @param TelegramBotUserDto $dto
     * @return TelegramBotUser
     */
    public function save(TelegramBotUserDto $dto): TelegramBotUser
    {
        return $this->telegramBotUserRepository->updateOrCreate($dto);
    }
}
