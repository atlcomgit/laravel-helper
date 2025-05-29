<?php

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Helper;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
// use Override;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Абстрактный класс для консольных команд
 */
abstract class DefaultCommand extends Command
{
    protected bool $isTest;
    protected bool $telegramEnabled = true;
    protected mixed $telegramComment = null;
    protected ?string $outputBuffer = null;


    public function __construct()
    {
        // Добавляем флаг отправки в телеграм
        if (!Str::contains($this->signature, '--telegram')) {
            $this->signature .= '
                { --telegram : Флаг отправки события в телеграм }
            ';
        }

        parent::__construct();
    }


    /**
     * Запуск консольной команды
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    // #[Override()]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            $this->isTest = isTesting();

            // Запускаем команду
            $result = parent::execute($input, $output) ?? self::SUCCESS;

            // Отправляем результат в телеграм
            if (
                $this->telegramEnabled
                && $this->hasOption('telegram')
                && Helper::castToBool($this->option('telegram'))
            ) {

                telegram([
                    'Событие' => 'Консольная команда',
                    'Название' => Helper::pathClassName($this::class),
                    'Описание' => $this->description,
                    'Результат' => $result === self::SUCCESS ? 'Успешно' : $result,
                    'Время' => Helper::timeSecondsToString(microtime(true) - $startTime),
                    'Память' => Helper::sizeBytesToString(memory_get_usage() - $startMemory),
                    ...(!is_null($this->telegramComment) ? ['Комментарий' => $this->telegramComment] : []),
                ], TelegramTypeEnum::Info);
            }

            return $result;

        } catch (Throwable $e) {
            // app(DefaultExceptionHandler::class)->report($e);
            //?!? сохранить в ConsoleLog через ConsoleLogJob ($this->outputBuffer)

            throw $e;
        }
    }


    /**
     * Очистка консоли
     *
     * @return void
     */
    public function consoleClear()
    {
        if (!$this->isTest) {
            echo "\033\143";
            $this->outputBuffer = '';
        }
    }


    /**
     * Вывод в консоль
     *
     * @param string $message
     * @return void
     */
    public function output(string $message = '', ?string $style = ''): void
    {
        $message = __($message);

        $lines = explode(PHP_EOL, $message);
        $lines = array_map(
            fn ($line) => trim($line) . ' ',
            $lines,
        );
        $message = implode(PHP_EOL, $lines);

        $this->isTest ?: $this->output->write(
            $this->outputBuffer .= ($style ? "<$style>" : '') . $message . ($style ? '</>' : '')
        );
    }


    /**
     * Вывод в консоль
     *
     * @param string $message
     * @return void
     */
    public function outputEol(string $message = '', ?string $style = ''): void
    {
        $this->output($message, $style);

        $this->isTest ?: $this->output($this->outputBuffer .= PHP_EOL);
    }


    /**
     * Возвращает сообщение в жирном стиле
     *
     * @param string $message
     * @return void
     */
    public function outputBold(?string $message = ''): void
    {
        $message = __($message);
        $this->output("<options=bold>{$message}</>");
    }
}
