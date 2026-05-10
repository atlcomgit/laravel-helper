<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit\TelegramBotMessageService;

use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutDataButtonCallbackDto;
use Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Тест подготовки inline-кнопок Telegram
 */
final class PrepareButtonsTest extends TestCase
{
    /**
     * Тест метода сервиса
     * @see \Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotMessageService::prepareButtons()
     *
     * @return void
     */
    #[Test]
    public function prepareButtonsCreatesInlineDtosForNewTelegramActionKeys(): void
    {
        $service = (new ReflectionClass(TelegramBotMessageService::class))
            ->newInstanceWithoutConstructor();

        $buttons = $service->prepareButtons([
            [
                'text'  => 'Url',
                'url'   => 'https://example.com',
                'style' => 'primary',
            ],
            [
                'text'      => 'Copy',
                'copy_text' => [
                    'text' => 'copy me',
                ],
                'style'     => 'success',
            ],
        ]);

        $this->assertCount(2, $buttons);
        $this->assertInstanceOf(TelegramBotOutDataButtonCallbackDto::class, $buttons[0]);
        $this->assertInstanceOf(TelegramBotOutDataButtonCallbackDto::class, $buttons[1]);
        $this->assertSame('https://example.com', $buttons[0]->toArray()['url']);
        $this->assertSame('primary', $buttons[0]->toArray()['style']);
        $this->assertSame(['text' => 'copy me'], $buttons[1]->toArray()['copy_text']);
        $this->assertSame('success', $buttons[1]->toArray()['style']);
    }
}
