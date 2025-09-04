<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Commands;

use Atlcom\LaravelHelper\Defaults\DefaultCommand;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Services\SwaggerService;

/**
 * Команда генерации swagger, делегирует всю логику сервису SwaggerService
 */
class SwaggerCommand extends DefaultCommand
{
    protected $signature = 'lh:swagger
        {--output= : Путь к итоговому swagger.json}
        {--routes= : Список файлов роутов через запятую}
        {--controllers= : Путь к папке с контроллерами}
        {--title= : Заголовок API}
        {--api-version= : Версия API (конфликтует с глобальной --version)}
        {--description= : Описание API}
    ';

    protected $description = 'Генерация swagger файла (OpenAPI 3.0)';


    /**
     * Выполнение команды
     */
    public function handle(): int
    {
        // Безопасная загрузка конфига (может быть null при отсутствии/кеше)
        $cfgLoaded = Lh::config(ConfigEnum::Swagger);
        $cfg = array_replace_recursive([
            'title' => env('APP_NAME', 'API'),
            'version' => '1.0.0',
            'description' => '',
            'output' => storage_path('app/swagger/swagger.json'),
            'servers' => [],
            'scan' => [
                'routes' => [
                    base_path('routes/api.php'),
                    base_path('routes/api-auth.php'),
                ],
                'controllers_path' => base_path('app/Domains/Crm'),
            ],
            'cleanup_snapshots' => true,
            'snapshots_path' => storage_path('app/swagger/snapshots'),
            'snapshots_test_file' => 'tests/Feature/SwaggerSnapshots/SwaggerSnapshotsTest.php',
        ], is_array($cfgLoaded) ? $cfgLoaded : []);

        $cfg['output'] = $this->option('output') ?: $cfg['output'];
        $cfg['scan']['routes'] = $this->option('routes')
            ? array_map('trim', explode(',', (string)$this->option('routes')))
            : $cfg['scan']['routes'];
        $cfg['scan']['controllers_path'] = $this->option('controllers') ?: $cfg['scan']['controllers_path'];
        $cfg['title'] = $this->option('title') ?: $cfg['title'];
        $cfg['version'] = $this->option('api-version') ?: $cfg['version'];
        $cfg['description'] = $this->option('description') ?: $cfg['description'];

        $this->info('Генерация OpenAPI...');

        // Авто-генерация снапшотов перед сборкой через make phpunit (если тест существует)
        $snapshotTestPath = base_path($cfg['snapshots_test_file']);
        if (is_file($snapshotTestPath)) {
            $this->info('Запуск генерации снапшотов (make phpunit)...');

            // Предпочитаем make phpunit, fallback на прямой phpunit
            $makeCmd = 'make phpunit FILTER=SwaggerSnapshotsTest FILE=' . $cfg['snapshots_test_file'];
            exec("{$makeCmd} 2>&1", $out, $rc);

            // Если make отсутствует или упал — пробуем прямой вызов
            if ($rc !== 0) {
                $this->warn("make phpunit завершился с кодом {$rc}, пробую vendor/bin/phpunit");
                $directCmd = base_path('vendor/bin/phpunit') . ' --filter=SwaggerSnapshotsTest ' . $cfg['snapshots_test_file'];
                $out = [];
                exec("{$directCmd} 2>&1", $out, $rc);
            }

            if ($rc !== 0) {
                $this->warn("Снапшоты не сгенерированы (код {$rc}). Продолжаю без снапшотов.");
                if (!empty($out)) {
                    $this->line(collect($out)->take(20)->implode("\n"));
                }
            } else {
                $this->info('Снапшоты обновлены.');
            }
        }
        $service = app(SwaggerService::class);
        $openapi = $service->generate($cfg);

        if (!is_dir(dirname($cfg['output']))) {
            @mkdir(dirname($cfg['output']), 0775, true);
        }
        file_put_contents($cfg['output'], json_encode($openapi, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info("Swagger файл сгенерирован: {$cfg['output']}");

        // Очистка снапшотов, если включено
        if (($cfg['cleanup_snapshots'] ?? false) === true) {
            $snapDir = $cfg['snapshots_path'] ?? storage_path('app/swagger/snapshots');
            if (is_dir($snapDir)) {
                foreach (glob("{$snapDir}/*.json") as $f) {
                    @unlink($f);
                }
                $this->info('Снапшоты удалены после генерации.');
            }
        }

        return self::SUCCESS;
    }
}
