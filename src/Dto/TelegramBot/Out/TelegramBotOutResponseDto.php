<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInMessageDto;

/**
 * Dto бота telegram
 */
class TelegramBotOutResponseDto extends DefaultDto
{
    public bool $status;
    public ?bool $result;
    public ?string $description;
    public ?TelegramBotInMessageDto $message;
    /** @var \Atlcom\LaravelHelper\Dto\TelegramBot\In\TelegramBotInDeletedMessageDto[] $deletedMessages */
    public ?array $deletedMessages;


    /**
     * @inheritDoc
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'result' => 'boolean',
            'description' => 'string',
            'message' => static fn ($v) => match (true) {
                $v instanceof TelegramBotInMessageDto => $v,
                is_array($v) => TelegramBotInMessageDto::create($v),

                default => null,
            },
            'deletedMessages' => 'array',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'status' => ['status', 'ok'],
            'message' => ['message', 'edited_message', 'callback_query.message'],
        ];
    }


    /**
     * @inheritDoc
     */
    protected function onFilling(array &$array): void
    {
        $result = $array['result'] ?? null;
        $array['result'] = (bool)$result;
        !(is_array($result) && !isset($array['message'])) ?: $array['message'] = [
            ...Hlp::objectToArrayRecursive($array),
            ...$result,
        ];
    }
}
