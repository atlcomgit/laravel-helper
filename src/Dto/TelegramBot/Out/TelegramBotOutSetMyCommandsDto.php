<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutCommandScopeDto;
use Atlcom\LaravelHelper\Enums\TelegramBotLanguageEnum;

/**
 * DTO для установки команд бота (setMyCommands)
 */
class TelegramBotOutSetMyCommandsDto extends TelegramBotOutDto
{
    /** @var array<int, array{command:string, description:string}> */
    public array $commands;
    public ?TelegramBotOutCommandScopeDto $scope;
    public ?TelegramBotLanguageEnum $language;

    /**
     * @inheritDoc
     */
    protected function defaults(): array
    {
        return [
            ...parent::defaults(),
            'commands' => [],
            'scope' => TelegramBotOutCommandScopeDto::create(),
            'language' => null,
        ];
    }
}
