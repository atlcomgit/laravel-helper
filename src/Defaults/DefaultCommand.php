<?php

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Helper;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Enums\ConsoleLogStatusEnum;
use Atlcom\LaravelHelper\Enums\TelegramTypeEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Абстрактный класс для консольных команд
 */
abstract class DefaultCommand extends Command
{
    protected bool $isTesting;
    protected bool $telegramEnabled = true;
    protected mixed $telegramComment = null;
    protected ?string $outputBuffer = null;
    protected ConsoleLogDto $consoleLogDto;


    public function __construct()
    {
        // Добавляем в команду флаг отправки в телеграм
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
            $this->isTesting = isTesting();

            $this->consoleLogDto = ConsoleLogDto::create(
                class: Helper::pathClassName($this::class),
                name: $this->name,
            );

            !config('laravel-helper.console_log.store_on_start', true) ?: $this->consoleLogDto->store(true);

            // Запускаем команду
            $this->consoleLogDto->result = parent::execute($input, $output) ?? self::SUCCESS;
            $this->consoleLogDto->status = $this->consoleLogDto->result === self::SUCCESS
                ? ConsoleLogStatusEnum::Success
                : ConsoleLogStatusEnum::Failed;

            return $this->consoleLogDto->result;

        } catch (Throwable $exception) {
            $this->consoleLogDto->status = ConsoleLogStatusEnum::Exception;
            $this->consoleLogDto->exception = Hlp::exceptionToString($exception);

            // app(DefaultExceptionHandler::class)->report($exception);

            throw $exception;

        } finally {
            $info = [
                'duration' => $duration = $this->consoleLogDto->getDuration(),
                'memory' => $memory = $this->consoleLogDto->getMemory(),
                'hidden' => $this->hidden,
                'isolated' => $this->isolated,
                'isolated_exit_code' => $this->isolatedExitCode,
                'aliases' => $this->aliases,
                ...(!is_null($this->telegramComment) ? ['comment' => $this->telegramComment] : []),
            ];

            // Отправляем результат в телеграм
            if (
                $this->telegramEnabled
                && $this->hasOption('telegram')
                && Helper::castToBool($this->option('telegram'))
            ) {

                telegram([
                    'Событие' => 'Консольная команда',
                    'Название' => $this->consoleLogDto->class,
                    'Описание' => $this->description,
                    'Результат' => $this->consoleLogDto->status->label(),
                    'Время' => $duration,
                    'Память' => $memory,
                    ...(!is_null($this->telegramComment) ? ['Комментарий' => $this->telegramComment] : []),
                ], TelegramTypeEnum::Info);
            }

            $this->consoleLogDto->info($info)->store(true);
        }
    }


    /**
     * Очистка консоли
     *
     * @return void
     */
    public function consoleClear()
    {
        if (!$this->isTesting) {
            echo "\033\143";
            $this->consoleLogDto->output('')->store(false);
        }
    }


    /**
     * Вывод в консоль
     *
     * @param string $message
     * @return void
     */
    public function output(string $message = '', ?string $style = '', bool $withEol = false): void
    {
        $message = __($message);

        // $lines = explode(PHP_EOL, $message);
        // $lines = array_map(
        //     fn ($line) => trim($line) . ' ',
        //     $lines,
        // );
        // $message = implode(PHP_EOL, $lines);

        if (!$this->isTesting) {
            $this->output->write(
                ($style ? "<$style>" : '') . $message . ($style ? '</>' : '')
                . ($withEol ? PHP_EOL : '')
            );

            $this->consoleLogDto->output ??= '';
            $this->consoleLogDto->output($this->consoleLogDto->output .= $message . ($withEol ? PHP_EOL : ''))
                ->store(false);
        }
    }


    /**
     * Вывод в консоль
     *
     * @param string $message
     * @return void
     */
    public function outputEol(string $message = '', ?string $style = ''): void
    {
        $this->output($message, $style, true);
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
        $this->output($message, 'options=bold');
    }
}
