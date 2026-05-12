<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Enums\TelegramBotButtonStyleEnum;

/**
 * @method self text(string $text)
 * @method self callback(string|int $callback)
 * @method self style(?TelegramBotButtonStyleEnum $style)
 * @method self iconCustomEmojiId(?string $iconCustomEmojiId)
 * @method self url(?string $url)
 * @method self webApp(?TelegramBotOutDataWebAppInfoDto|array $webApp)
 * @method self loginUrl(?TelegramBotOutDataLoginUrlDto|array $loginUrl)
 * @method self switchInlineQuery(?string $switchInlineQuery)
 * @method self switchInlineQueryCurrentChat(?string $switchInlineQueryCurrentChat)
 * @method self switchInlineQueryChosenChat(?TelegramBotOutDataSwitchInlineQueryChosenChatDto|array $switchInlineQueryChosenChat)
 * @method self copyText(?TelegramBotOutDataCopyTextButtonDto|array $copyText)
 * @method self callbackGame(array|object|null $callbackGame)
 * @method self pay(?bool $pay)
 */
class TelegramBotOutDataButtonCallbackDto extends DefaultDto
{
    public const AUTO_CASTS_ENABLED = true;

    public string                                            $text;
    public ?string                                           $callback                     = null;
    public ?TelegramBotButtonStyleEnum                       $style                        = null;
    public ?string                                           $iconCustomEmojiId            = null;
    public ?string                                           $url                          = null;
    public ?TelegramBotOutDataWebAppInfoDto                  $webApp                       = null;
    public ?TelegramBotOutDataLoginUrlDto                    $loginUrl                     = null;
    public ?string                                           $switchInlineQuery            = null;
    public ?string                                           $switchInlineQueryCurrentChat = null;
    public ?TelegramBotOutDataSwitchInlineQueryChosenChatDto $switchInlineQueryChosenChat  = null;
    public ?TelegramBotOutDataCopyTextButtonDto              $copyText                     = null;
    public array|object|null                                 $callbackGame                 = null;
    public ?bool                                             $pay                          = null;


    /**
     * Возвращает правила приведения типов для вложенных DTO кнопки.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'callback'                        => 'string',
            'style'                           => TelegramBotButtonStyleEnum::class,
            'webApp'                          => TelegramBotOutDataWebAppInfoDto::class,
            'web_app'                         => TelegramBotOutDataWebAppInfoDto::class,
            'loginUrl'                        => TelegramBotOutDataLoginUrlDto::class,
            'login_url'                       => TelegramBotOutDataLoginUrlDto::class,
            'switchInlineQueryChosenChat'     => TelegramBotOutDataSwitchInlineQueryChosenChatDto::class,
            'switch_inline_query_chosen_chat' => TelegramBotOutDataSwitchInlineQueryChosenChatDto::class,
            'copyText'                        => TelegramBotOutDataCopyTextButtonDto::class,
            'copy_text'                       => TelegramBotOutDataCopyTextButtonDto::class,
        ];
    }


    /**
     * @inheritDoc
     */
    protected function mappings(): array
    {
        return [
            'callback'                     => ['callback', 'callbackData', 'callback_data'],
            'iconCustomEmojiId'            => ['iconCustomEmojiId', 'icon_custom_emoji_id'],
            'webApp'                       => ['webApp', 'web_app'],
            'loginUrl'                     => ['loginUrl', 'login_url'],
            'switchInlineQuery'            => ['switchInlineQuery', 'switch_inline_query'],
            'switchInlineQueryCurrentChat' => [
                'switchInlineQueryCurrentChat',
                'switch_inline_query_current_chat',
            ],
            'switchInlineQueryChosenChat'  => [
                'switchInlineQueryChosenChat',
                'switch_inline_query_chosen_chat',
            ],
            'copyText'                     => ['copyText', 'copy_text'],
            'callbackGame'                 => ['callbackGame', 'callback_game'],
        ];
    }


    /**
     * Возвращает соответствия имен полей для сериализации в Telegram API.
     *
     * @return array
     */
    protected function serializationMappings(): array
    {
        return [
            'callback'                     => 'callback_data',
            'iconCustomEmojiId'            => 'icon_custom_emoji_id',
            'webApp'                       => 'web_app',
            'loginUrl'                     => 'login_url',
            'switchInlineQuery'            => 'switch_inline_query',
            'switchInlineQueryCurrentChat' => 'switch_inline_query_current_chat',
            'switchInlineQueryChosenChat'  => 'switch_inline_query_chosen_chat',
            'copyText'                     => 'copy_text',
            'callbackGame'                 => 'callback_game',
        ];
    }


    /**
     * @inheritDoc
     */
    protected function onSerializing(array &$array): void
    {
        if ($this->callbackGame === []) {
            $this->callbackGame = (object)[];
        }

        $this->serializeKeys(true)
            ->onlyNotNull()
            ->mappingKeys($this->serializationMappings());
    }
}
