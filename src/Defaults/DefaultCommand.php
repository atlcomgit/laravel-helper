<?php

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Dto\ApplicationDto;
use Atlcom\LaravelHelper\Dto\ConsoleLogDto;
use Atlcom\LaravelHelper\Enums\ApplicationTypeEnum;
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
    /** Флаг включения отправки сообщения в телеграм */
    protected ?bool $withTelegramLog = null;
    /** Комментарий для отправки сообщения в телеграм */
    protected mixed $telegramComment = null;
    /** Флаг включения логирования консольной команды */
    protected bool $withConsoleLog = false;
    /** Буфер вывода stdout для логирования */
    private ?string $outputBuffer = null;
    /** Dto логирования консольной команды */
    private ConsoleLogDto $consoleLogDto;


    public function __construct()
    {
        // Добавляем в команду флаг отправки в телеграм
        if (!Str::contains($this->signature, '--telegram')) {
            $this->signature .= '
                { --telegram : Флаг отправки события в телеграм }
            ';
        }

        // Добавляем в команду флаг логирования консольной команды
        if (!Str::contains($this->signature, '--log')) {
            $this->signature .= '
                { --log : Флаг включения логирования команды }
            ';
        }

        // Добавляем в команду флаг логирования консольной команды
        if (!Str::contains($this->signature, '--cls')) {
            $this->signature .= '
                { --cls : Флаг включения очистки консоли stdout }
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
            ApplicationDto::create(type: ApplicationTypeEnum::Command, class: $this::class);
            $this->isTesting = isTesting();
            $this->consoleLogDto = ConsoleLogDto::create(
                name: Hlp::pathClassName($this::class),
                command: $this->name,
                withConsoleLog: ($this->hasOption('log') && $this->option('log')) || $this->withConsoleLog,
            );

            !config('laravel-helper.console_log.store_on_start', true) ?: $this->consoleLogDto->store(true);

            // Очищаем консоль stdout
            $this->outputClear();

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
            $this->consoleLogDto->duration = $this->consoleLogDto->getDuration();
            $this->consoleLogDto->memory = $this->consoleLogDto->getMemory();
            $info = [
                'class' => $this::class,
                'duration' => $duration = Hlp::timeSecondsToString(
                    value: $this->consoleLogDto->duration,
                    withMilliseconds: true,
                ),
                'memory' => $memory = Hlp::sizeBytesToString($this->consoleLogDto->memory),
                'hidden' => $this->hidden,
                'isolated' => $this->isolated,
                'isolated_exit_code' => $this->isolatedExitCode,
                'aliases' => $this->aliases,
                ...(!is_null($this->telegramComment) ? ['comment' => $this->telegramComment] : []),
            ];

            // Отправляем результат в телеграм
            if (
                $this->withTelegramLog === true
                || ($this->withTelegramLog !== false && config('laravel-helper.console_log.global'))
                || (
                    is_null($this->withTelegramLog)
                    && $this->hasOption('telegram')
                    && Hlp::castToBool($this->option('telegram'))
                )
            ) {
                $this->output->write(
                    PHP_EOL
                    . '<fg=green>'
                    . '┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓' . PHP_EOL
                    . '┃ 🕊  Отправка сообщения в телеграм ┃' . PHP_EOL
                    . '┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛' . PHP_EOL
                    . '</>' . PHP_EOL
                );

                telegram([
                    'Событие' => 'Консольная команда',
                    'Название' => $this->consoleLogDto->name,
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
     * @param bool $isForce
     * @return void
     */
    public function outputClear(bool $isForce = false)
    {
        if (
            !$this->isTesting && (
                $isForce
                || ($this->hasOption('cls') && $this->option('cls')))
        ) {
            echo "\033\143";
            $this->consoleLogDto->output('')->store(false);
        }
    }


    /**
     * Вывод в консоль
     *
     * @param mixed $message
     * @return void
     */
    public function output(mixed $message = '', ?string $style = '', bool $withEol = false): void
    {
        $message = __(Hlp::castToString($message));

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
     * @param mixed $message
     * @return void
     */
    public function outputEol(mixed $message = '', ?string $style = ''): void
    {
        $this->output(Hlp::castToString($message), $style, true);
    }


    /**
     * Возвращает сообщение в жирном стиле
     *
     * @param mixed $message
     * @return void
     */
    public function outputBold(mixed $message = ''): void
    {
        $message = __(Hlp::castToString($message));
        $this->output($message, 'options=bold');
    }
}
