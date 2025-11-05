<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutCommandScopeDto;
use Atlcom\LaravelHelper\Enums\TelegramBotLanguageEnum;

/**
 * DTO для получения команд бота (getMyCommands)
 */
class TelegramBotOutGetMyCommandsDto extends TelegramBotOutDto
{
    public ?TelegramBotOutCommandScopeDto $scope;
    public ?TelegramBotLanguageEnum $language;


    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'scope' => TelegramBotOutCommandScopeDto::create(),
            'language' => null,
        ];
    }
}
