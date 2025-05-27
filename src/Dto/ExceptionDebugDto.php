<?php

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Error;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
// use Override;

class ExceptionDebugDto extends Dto
{
    /**
     * Публичные свойства для сериализации
     */
    public ?string $class;
    public ?string $func;
    public ?string $file;

    /**
     * Скрытые свойства для сериализации
     */
    public ?array $trace;
    public Exception|ClientException|Error|null $throw;
    public ?Request $request;
    public ?array $data = [];


    /**
     * Метод вызывается до заполнения dto
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onFilling(array &$array): void
    {
        $ignoreFiles = [
            base_path('/vendor/'),
        ];
        $errorFile = null;
        $trace = [];
        !($array['trace'] ?? null) ?: array_unshift(
            $array['trace'],
            ($array['file'] ?? null) ? ['file' => $array['file']] : []
        );

        foreach ($array['trace'] ?? [] as $item) {
            if (config('app.debug_trace_vendor') || !Str::startsWith($item['file'] ?? '', $ignoreFiles)) {
                $file = str_replace(base_path('/'), '', $item['file'] ?? '')
                    . (($item['line'] ?? '') ? ':' . ($item['line'] ?? '') : '');
                $trace[] = [
                    'file' => $file,
                    'func' => ltrim(
                        $this->toBasename($item['class'] ?? '')
                        . ($item['type'] ?? '')
                        . ($item['function'] ?? '')
                        . '()',
                        '()',
                    ),
                ];
                Str::startsWith($file, [
                    'app/Helpers/helpers.php',
                    'app/Defaults/DefaultException.php',
                ]) ?: $errorFile ??= $file;
            }
        }

        $array['trace'] = $trace;
        $array['file'] = $errorFile ?? str_replace(base_path('/'), '', $array['file'] ?? '');
    }


    /**
     * Метод вызывается после заполнения dto
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onFilled(array $array): void
    {
        !($this->throw instanceof ClientException)
            ?: $this->data[] = $this->throw->getResponse()->getBody()->getContents();
    }


    /**
     * Метод вызывается до преобразования dto в массив
     *
     * @param array $array
     * @return void>
     */
    // #[Override()]
    protected function onSerializing(array &$array): void
    {
        $isTelegram = $this->getOption('customOptions')['isTelegram'] ?? false;
        $this->mappingKeys($this->mappings())
            ->onlyNotNull()
            ->onlyKeys([
                'class',
                'func',
                'file',
                ...(isDebug() ? ['data'] : []),
                ...((isDebugTrace() && !$isTelegram) ? ['trace'] : []),
            ])
            ->includeArray([
                ...(isDebug()
                    ? [
                        'request' => [
                            // 'server' => $this->request?->server->all(),
                            // 'headers' => $this->request?->headers->all(),
                            'params' => $this->request?->all(),
                        ],
                    ]
                    : []),
            ])
        ;
    }


    /**
     * Метод вызывается после преобразования dto в массив
     *
     * @param array $array
     * @return void
     */
    // #[Override()]
    protected function onSerialized(array &$array): void
    {
        $isTelegram = $this->getOption('customOptions')['isTelegram'] ?? false;
        !($isTelegram && is_array($array['request'] ?? null)) ?: array_walk_recursive(
            $array['request'],
            static fn (&$value) => $value = Str::limit($value, 100, '...')
        );
    }
}
