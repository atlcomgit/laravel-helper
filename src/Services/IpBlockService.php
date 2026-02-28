<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\IpBlockEventDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\IpBlockRuleEnum;
use Atlcom\LaravelHelper\Events\IpBlockEvent;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Сервис блокировки ip адресов
 */
class IpBlockService extends DefaultService
{
    private const int WINDOW_SECONDS = 60;

    private ?array $state = null;


    /**
     * Проверяет включен ли сервис блокировки
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)Lh::config(ConfigEnum::IpBlock, 'enabled');
    }


    /**
     * Возвращает http статус ответа при блокировке
     *
     * @return int
     */
    public function getResponseStatus(): int
    {
        return (int)Lh::config(ConfigEnum::IpBlock, 'response_status', 403);
    }


    /**
     * Возвращает клиентский ip адрес с учетом trusted proxies
     *
     * @param Request $request
     * @return string
     */
    public function resolveClientIp(Request $request): string
    {
        $remoteIp = (string)($request->server->get('REMOTE_ADDR') ?: $request->ip() ?: '0.0.0.0');

        if (!$this->isTrustedProxy($remoteIp)) {
            return $remoteIp;
        }

        $forwardedFor = trim((string)$request->headers->get('x-forwarded-for', ''));

        if ($forwardedFor) {
            foreach (explode(',', $forwardedFor) as $part) {
                $ip = trim($part);

                if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        $realIp = trim((string)$request->headers->get('x-real-ip', ''));

        return $realIp && filter_var($realIp, FILTER_VALIDATE_IP) ? $realIp : $remoteIp;
    }


    /**
     * Проверяет заблокирован ли ip адрес
     *
     * @param string $ip
     * @return bool
     */
    public function isBlockedIp(string $ip): bool
    {
        !$this->isEnabled() ?: $this->cleanupExpired();

        $ip = trim($ip);

        if (!$ip) {
            return false;
        }

        if ($this->isIpInList($ip, $this->getManualAllowIps())) {
            return false;
        }

        if ($this->isIpInList($ip, $this->getManualDenyIps())) {
            return true;
        }

        $blocked = $this->getState()['blocked'][$ip] ?? null;

        return (bool)$blocked && ((int)($blocked['expiresAt'] ?? 0) > now()->timestamp);
    }


    /**
     * Проверяет находится ли ip адрес в ручном allow списке
     *
     * @param string $ip
     * @return bool
     */
    public function isAllowListedIp(string $ip): bool
    {
        $ip = trim($ip);

        return $ip !== '' && $this->isIpInList($ip, $this->getManualAllowIps());
    }


    /**
     * Регистрирует входящий запрос для ip адреса
     *
     * @param Request $request
     * @return void
     */
    public function registerIncomingRequest(Request $request): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $ip = $this->resolveClientIp($request);

        if (!$this->canApplyAutoRules($ip)) {
            return;
        }

        $metrics = $this->touchMetrics($ip);
        $metrics['requests'] = (int)($metrics['requests'] ?? 0) + 1;

        $this->saveMetrics($ip, $metrics);

        $this->checkRequestRateRule($ip, $metrics);
        $this->checkSuspiciousPayloadRule($request, $ip);
    }


    /**
     * Регистрирует ответ запроса для ip адреса
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function registerRequestResponse(Request $request, Response $response): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $ip = $this->resolveClientIp($request);

        if (!$this->canApplyAutoRules($ip)) {
            return;
        }

        $metrics = $this->touchMetrics($ip);
        $status = $response->getStatusCode();

        if ($status === 404) {
            $metrics['notFound'] = (int)($metrics['notFound'] ?? 0) + 1;
            $this->saveMetrics($ip, $metrics);
            $this->checkNotFoundRule($ip, $metrics);
        }

        $isUnauthorized = in_array($status, [401, 403], true) && !$request->user();

        if ($isUnauthorized) {
            $metrics['unauthorized'] = (int)($metrics['unauthorized'] ?? 0) + 1;
            $this->saveMetrics($ip, $metrics);
            $this->checkUnauthorizedRule($ip, $metrics);
        }
    }


    /**
     * Блокирует ip адрес вручную
     *
     * @param string $ip
     * @param string $reason
     * @param string $source
     * @param string $description
     * @return void
     */
    public function blockIp(
        string $ip,
        string $reason = IpBlockRuleEnum::ManualBlock->value,
        string $source = 'manual',
        string $description = '',
    ): void {
        $ip = trim($ip);

        if (!$ip || $this->isIpInList($ip, $this->getManualAllowIps())) {
            return;
        }

        $state = $this->getState();
        $now = now()->timestamp;
        $ttl = max(60, (int)Lh::config(ConfigEnum::IpBlock, 'block_ttl_seconds', 3600));

        $state['blocked'][$ip] = [
            'ip'           => $ip,
            'reason'       => $reason,
            'source'       => $source,
            'description'  => $description,
            'blockedAt'    => $now,
            'expiresAt'    => $now + $ttl,
            'blockedAtIso' => Carbon::createFromTimestamp($now)->toIso8601String(),
            'expiresAtIso' => Carbon::createFromTimestamp($now + $ttl)->toIso8601String(),
        ];

        $this->setState($state);

        // Отправка события блокировки ip адреса
        event(new IpBlockEvent(IpBlockEventDto::create(
            ip: $ip,
            reason: $reason,
            source: $source,
            description: $description,
        )));
    }


    /**
     * Разблокирует ip адрес
     *
     * @param string $ip
     * @return bool
     */
    public function unblockIp(string $ip): bool
    {
        $ip = trim($ip);
        $state = $this->getState();

        if (!isset($state['blocked'][$ip])) {
            return false;
        }

        unset($state['blocked'][$ip]);
        unset($state['metrics'][$ip]);

        $this->setState($state);

        return true;
    }


    /**
     * Очищает устаревшие блокировки
     *
     * @return int
     */
    public function cleanupExpired(): int
    {
        $state = $this->getState();
        $now = now()->timestamp;
        $deleted = 0;

        foreach ((array)($state['blocked'] ?? []) as $ip => $item) {
            if ((int)($item['expiresAt'] ?? 0) > $now) {
                continue;
            }

            unset($state['blocked'][$ip]);
            unset($state['metrics'][$ip]);
            $deleted++;
        }

        if ($deleted > 0) {
            $this->setState($state);
        }

        return $deleted;
    }


    /**
     * Возвращает список активных блокировок
     *
     * @return array
     */
    public function getBlockedIps(): array
    {
        $this->cleanupExpired();

        return array_values((array)($this->getState()['blocked'] ?? []));
    }


    /**
     * Возвращает текущие настройки правил
     *
     * @return array
     */
    public function getRules(): array
    {
        return [
            'enabled'           => $this->isEnabled(),
            'block_ttl_seconds' => (int)Lh::config(ConfigEnum::IpBlock, 'block_ttl_seconds', 3600),
            'rules'             => [
                IpBlockRuleEnum::RequestsPerMinute->value     => $this->getRule(IpBlockRuleEnum::RequestsPerMinute->value),
                IpBlockRuleEnum::NotFoundPerMinute->value     => $this->getRule(IpBlockRuleEnum::NotFoundPerMinute->value),
                IpBlockRuleEnum::UnauthorizedPerMinute->value => $this->getRule(IpBlockRuleEnum::UnauthorizedPerMinute->value),
                IpBlockRuleEnum::SuspiciousPayload->value     => $this->getRule(IpBlockRuleEnum::SuspiciousPayload->value),
            ],
        ];
    }


    /**
     * Обновляет настройки правил блокировки
     *
     * @param array $rules
     * @return array
     */
    public function updateRules(array $rules): array
    {
        $state = $this->getState();
        $current = (array)($state['rules'] ?? []);
        $state['rules'] = array_replace_recursive($current, $this->withoutNullsRecursive($rules));

        $this->setState($state, false);

        return (array)$this->getRules()['rules'];
    }


    /**
     * Проверяет правило по количеству запросов
     *
     * @param string $ip
     * @param array $metrics
     * @return void
     */
    private function checkRequestRateRule(string $ip, array $metrics): void
    {
        $rule = $this->getRule(IpBlockRuleEnum::RequestsPerMinute->value);

        if (!(bool)($rule['enabled'] ?? false)) {
            return;
        }

        $count = (int)($metrics['requests'] ?? 0);
        $limit = (int)($rule['limit'] ?? 100);

        if ($count > $limit) {
            $this->blockIp(
                $ip,
                IpBlockRuleEnum::RequestsPerMinute->value,
                'auto',
                "Запросов: {$count}, лимит: {$limit}/мин",
            );
        }
    }


    /**
     * Проверяет правило по 404 запросам
     *
     * @param string $ip
     * @param array $metrics
     * @return void
     */
    private function checkNotFoundRule(string $ip, array $metrics): void
    {
        $rule = $this->getRule(IpBlockRuleEnum::NotFoundPerMinute->value);

        if (!(bool)($rule['enabled'] ?? false)) {
            return;
        }

        $count = (int)($metrics['notFound'] ?? 0);
        $limit = (int)($rule['limit'] ?? 10);

        if ($count > $limit) {
            $this->blockIp(
                $ip,
                IpBlockRuleEnum::NotFoundPerMinute->value,
                'auto',
                "Ответов 404: {$count}, лимит: {$limit}/мин",
            );
        }
    }


    /**
     * Проверяет правило по неавторизованным запросам
     *
     * @param string $ip
     * @param array $metrics
     * @return void
     */
    private function checkUnauthorizedRule(string $ip, array $metrics): void
    {
        $rule = $this->getRule(IpBlockRuleEnum::UnauthorizedPerMinute->value);

        if (!(bool)($rule['enabled'] ?? false)) {
            return;
        }

        $count = (int)($metrics['unauthorized'] ?? 0);
        $limit = (int)($rule['limit'] ?? 5);

        if ($count > $limit) {
            $this->blockIp(
                $ip,
                IpBlockRuleEnum::UnauthorizedPerMinute->value,
                'auto',
                "Неавторизованных: {$count}, лимит: {$limit}/мин",
            );
        }
    }


    /**
     * Проверяет правило по подозрительному payload
     *
     * @param Request $request
     * @param string $ip
     * @return void
     */
    private function checkSuspiciousPayloadRule(Request $request, string $ip): void
    {
        $rule = $this->getRule(IpBlockRuleEnum::SuspiciousPayload->value);

        if (!(bool)($rule['enabled'] ?? false)) {
            return;
        }

        // Пропускаем проверку для URL из списка исключений
        if ($this->isExcludedUrl($request->getRequestUri())) {
            return;
        }

        $payload = [
            json($request->query->all(), Hlp::jsonFlags()),
            json($request->request->all(), Hlp::jsonFlags()),
            (string)$request->getContent(),
            (string)$request->getRequestUri(),
        ];

        $subject = implode("\n", array_filter($payload));
        $patterns = (array)($rule['patterns'] ?? []);

        foreach ($patterns as $pattern) {
            if (!$pattern) {
                continue;
            }

            $modifier = (bool)Lh::config(ConfigEnum::IpBlock, 'patterns_case_insensitive', true) ? 'i' : '';
            $regexp = "/{$pattern}/{$modifier}";
            $isMatch = @preg_match($regexp, $subject);

            if ($isMatch === 1) {
                $this->blockIp(
                    $ip,
                    IpBlockRuleEnum::SuspiciousPayload->value,
                    'auto',
                    "Паттерн: {$pattern}, URL: {$request->getRequestUri()}",
                );

                return;
            }
        }
    }


    /**
     * Проверяет входит ли URL запроса в список исключений для suspicious_payload
     *
     * @param string $url
     * @return bool
     */
    private function isExcludedUrl(string $url): bool
    {
        $excludeUrls = (array)Lh::config(ConfigEnum::IpBlock, 'exclude_urls', []);

        foreach ($excludeUrls as $pattern) {
            $pattern = trim((string)$pattern);

            if ($pattern === '') {
                continue;
            }

            if (str_contains($url, $pattern)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Проверяет можно ли применять автоматические правила к ip
     *
     * @param string $ip
     * @return bool
     */
    private function canApplyAutoRules(string $ip): bool
    {
        if ($this->isIpInList($ip, $this->getManualAllowIps())) {
            return false;
        }

        $ignore = $this->normalizeIpList(Lh::config(ConfigEnum::IpBlock, 'ignore', []));

        if ($this->isIpInList($ip, $ignore)) {
            return false;
        }

        return !$this->isIpInList($ip, $this->getManualDenyIps());
    }


    /**
     * Возвращает настройки правила с учетом runtime override
     *
     * @param string $name
     * @return array
     */
    private function getRule(string $name): array
    {
        $stateRules = (array)($this->getState()['rules'] ?? []);
        $configRule = (array)Lh::config(ConfigEnum::IpBlock, "rules.{$name}", []);
        $stateRule = (array)($stateRules[$name] ?? []);

        if (
            array_key_exists('patterns', $stateRule)
            && is_array($stateRule['patterns'])
            && empty(array_filter($stateRule['patterns'], static fn ($item) => (string)$item !== ''))
        ) {
            unset($stateRule['patterns']);
        }

        return array_replace($configRule, $stateRule);
    }


    /**
     * Возвращает массив ip из ручного deny списка
     *
     * @return array
     */
    private function getManualDenyIps(): array
    {
        return $this->normalizeIpList(Lh::config(ConfigEnum::IpBlock, 'manual_deny', []));
    }


    /**
     * Возвращает массив ip из ручного allow списка
     *
     * @return array
     */
    private function getManualAllowIps(): array
    {
        return $this->normalizeIpList(Lh::config(ConfigEnum::IpBlock, 'manual_allow', []));
    }


    /**
     * Нормализует список ip/cidr из mixed значения конфига
     *
     * @param mixed $value
     * @return array
     */
    private function normalizeIpList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];
        $result = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                foreach ($item as $nestedItem) {
                    $nestedValue = trim((string)$nestedItem);

                    if ($nestedValue !== '') {
                        $result[] = $nestedValue;
                    }
                }

                continue;
            }

            $stringValue = trim((string)$item);

            if ($stringValue === '') {
                continue;
            }

            if (str_starts_with($stringValue, '[') && str_ends_with($stringValue, ']')) {
                $decoded = json_decode($stringValue, true);

                if (is_array($decoded)) {
                    foreach ($decoded as $decodedItem) {
                        $decodedValue = trim((string)$decodedItem);

                        if ($decodedValue !== '') {
                            $result[] = $decodedValue;
                        }
                    }

                    continue;
                }
            }

            if (str_contains($stringValue, ',')) {
                foreach (explode(',', $stringValue) as $csvItem) {
                    $csvValue = trim($csvItem, " \t\n\r\0\x0B\"'");

                    if ($csvValue !== '') {
                        $result[] = $csvValue;
                    }
                }

                continue;
            }

            $result[] = trim($stringValue, " \t\n\r\0\x0B\"'");
        }

        return array_values(array_unique(array_filter($result, static fn ($item) => $item !== '')));
    }


    /**
     * Проверяет, входит ли ip адрес в список ip/cidr
     *
     * @param string $ip
     * @param array $list
     * @return bool
     */
    private function isIpInList(string $ip, array $list): bool
    {
        foreach ($list as $mask) {
            $mask = trim((string)$mask);

            if ($mask === '') {
                continue;
            }

            if ($mask === $ip) {
                return true;
            }

            if (str_contains($mask, '/') && IpUtils::checkIp($ip, $mask)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Проверяет что proxy сервер доверенный
     *
     * @param string $remoteIp
     * @return bool
     */
    private function isTrustedProxy(string $remoteIp): bool
    {
        $trusted = array_values(array_filter(array_map('trim', (array)Lh::config(ConfigEnum::IpBlock, 'trusted_proxies', []))));

        if (empty($trusted)) {
            return false;
        }

        foreach ($trusted as $proxy) {
            if ($proxy === '*') {
                return true;
            }

            if ($proxy === $remoteIp) {
                return true;
            }

            if (str_contains($proxy, '/') && IpUtils::checkIp($remoteIp, $proxy)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Возвращает метрики для ip с ротацией окна 1 минута
     *
     * @param string $ip
     * @return array
     */
    private function touchMetrics(string $ip): array
    {
        $state = $this->getState();
        $metrics = (array)($state['metrics'][$ip] ?? []);
        $now = now()->timestamp;
        $windowStartedAt = (int)($metrics['windowStartedAt'] ?? 0);

        if (!$windowStartedAt || ($now - $windowStartedAt) >= self::WINDOW_SECONDS) {
            return [
                'windowStartedAt' => $now,
                'requests'        => 0,
                'notFound'        => 0,
                'unauthorized'    => 0,
            ];
        }

        return [
            'windowStartedAt' => $windowStartedAt,
            'requests'        => (int)($metrics['requests'] ?? 0),
            'notFound'        => (int)($metrics['notFound'] ?? 0),
            'unauthorized'    => (int)($metrics['unauthorized'] ?? 0),
        ];
    }


    /**
     * Сохраняет метрики ip адреса
     *
     * @param string $ip
     * @param array $metrics
     * @return void
     */
    private function saveMetrics(string $ip, array $metrics): void
    {
        $state = $this->getState();
        $state['metrics'][$ip] = $metrics;

        $this->setState($state);
    }


    /**
     * Возвращает состояние сервиса
     *
     * @return array
     */
    private function getState(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $state = $this->readState();
        $state['blocked'] = (array)($state['blocked'] ?? []);
        $state['metrics'] = (array)($state['metrics'] ?? []);
        $state['rules'] = (array)($state['rules'] ?? []);

        return $this->state = $state;
    }


    /**
     * Обновляет состояние сервиса
     *
     * @param array $state
     * @return void
     */
    private function setState(array $state, bool $preserveRules = true): void
    {
        $effectiveRules = (array)($state['rules'] ?? []);

        if ($preserveRules) {
            $latest = $this->readState();
            $effectiveRules = (array)($latest['rules'] ?? []);
        }

        $this->state = [
            'blocked' => (array)($state['blocked'] ?? []),
            'metrics' => (array)($state['metrics'] ?? []),
            'rules'   => $effectiveRules,
        ];

        $this->writeState($this->state);
    }


    /**
     * Возвращает путь к state файлу
     *
     * @return string
     */
    private function getStorageFilePath(): string
    {
        return (string)Lh::config(ConfigEnum::IpBlock, 'storage_file', storage_path('framework/ip-block-state.php'));
    }


    /**
     * Читает состояние из файла
     *
     * @return array
     */
    private function readState(): array
    {
        $path = $this->getStorageFilePath();

        if (!is_file($path)) {
            return [];
        }

        $content = (string)file_get_contents($path);

        if (str_starts_with(ltrim($content), '<?php')) {
            $state = require $path;

            return is_array($state) ? $state : [];
        }

        $state = (array)(json_decode($content, true) ?: []);

        if (!empty($state)) {
            $this->writeState($state);
        }

        return $state;
    }


    /**
     * Записывает состояние в файл атомарно
     *
     * @param array $state
     * @return void
     */
    private function writeState(array $state): void
    {
        $path = $this->getStorageFilePath();
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $resource = fopen($path, 'c+');

        if (!is_resource($resource)) {
            return;
        }

        try {
            if (!flock($resource, LOCK_EX)) {
                fclose($resource);

                return;
            }

            ftruncate($resource, 0);
            rewind($resource);
            $php = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($state, true) . ';' . PHP_EOL;
            fwrite($resource, $php);
            fflush($resource);
            flock($resource, LOCK_UN);
        } finally {
            fclose($resource);
        }

        // Инвалидируем OPcache для файла состояния,
        // чтобы следующий require вернул свежие данные
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }


    /**
     * Удаляет null значения из массива рекурсивно
     *
     * @param array $value
     * @return array
     */
    private function withoutNullsRecursive(array $value): array
    {
        $result = [];

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $item = $this->withoutNullsRecursive($item);
            }

            if ($item === null) {
                continue;
            }

            $result[$key] = $item;
        }

        return $result;
    }
}
