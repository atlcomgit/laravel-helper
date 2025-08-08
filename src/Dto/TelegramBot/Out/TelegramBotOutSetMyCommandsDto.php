<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataCommandDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\TelegramBotOutDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutCommandScopeDto;
use Atlcom\LaravelHelper\Enums\TelegramBotLanguageEnum;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService;
use Illuminate\Support\Collection;

/**
 * DTO для установки команд бота (setMyCommands)
 */
class TelegramBotOutSetMyCommandsDto extends TelegramBotOutDto
{
    /** @var Collection<array|TelegramBotOutDataCommandDto> $commands */
    public ?Collection $commands;
    public ?TelegramBotOutCommandScopeDto $scope;
    public ?TelegramBotLanguageEnum $language;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'scope' => TelegramBotOutCommandScopeDto::class,
            'language' => TelegramBotLanguageEnum::class,
        ];
    }


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


    /**
     * Добавляет несколько inline кнопок к сообщению
     *
     * @param array<TelegramBotOutDataCommandDto>|TelegramBotOutDataCommandDto $commands
     * @return static
     */
    public function setCommands(array|TelegramBotOutDataCommandDto $commands): static
    {
        $this->commands = collect([]);
        $this->addCommands($commands);

        return $this;
    }


    /**
     * Добавляет inline кнопку к сообщению
     *
     * @param TelegramBotOutDataCommandDto $command
     * @return static
     */
    public function addCommand(TelegramBotOutDataCommandDto $command): static
    {
        $this->addCommands($command);

        return $this;
    }


    /**
     * Добавляет несколько inline кнопок к сообщению
     *
     * @param array|TelegramBotOutDataCommandDto $commands
     * @return static
     */
    public function addCommands(array|TelegramBotOutDataCommandDto $commands): static
    {
        $this->commands ??= collect([]);
        !($commands instanceof TelegramBotOutDataCommandDto) ?: $commands = [$commands];
        !isset($commands['command']) ?: $commands = [$commands];

        $commands = app(TelegramBotMessageService::class)->prepareCommands($commands);

        foreach ($commands as $command) {
            $this->commands->push($command);
        }

        return $this;
    }
}
