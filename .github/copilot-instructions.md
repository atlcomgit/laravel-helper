# Инструкции для ИИ-агента Copilot по пакету (atlcom/laravel-helper)

Назначение: пакет Laravel, добавляющий логирование, кеширование, контроллеры на DTO, профайлер и инструменты Telegram-бота. Используйте готовые базовые классы, сервисы, события, макросы и флаги конфига — не изобретайте заново.

## Общая инструкция для ИИ-агентов
[AI_GUIDELINES.md](https://github.com/atlcomgit/laravel-helper/blob/master/AI_GUIDELINES.md)

## Архитектура и ключевые модули
- Пакет: Laravel (10+), (PHP 8.2+), Redis.

## Крупная архитектура
- Провайдер `src/Providers/LaravelHelperServiceProvider.php` связывает всё: мержит конфиг, грузит миграции/фабрики/роуты, регистрирует singletons, middleware, слушателей событий, консольные команды и макросы (Http/Builder/Str/Collection).
- Фичи управляются конфигом `config/laravel-helper.php` (разделы по Enum-ключам). Почти весь функционал защищён флагами и “global” переключателями.
- Сквозные аспекты реализованы через Middleware (RouteLog, HttpLog, HttpCache), макросы запросов (QueryLog/Cache), Http-макросы (клиенты сервисов), Events + Listeners и базовые классы Default*.
- Telegram: REST через `Services/TelegramApiService` (база `Http::telegramOrg()`); поток бота — в `Services/TelegramBot/*`; публичные роуты `routes/api-telegram-bot.php`; на исходящие действия эмитится `TelegramBotEvent`.

## Ключевые конвенции
- Наследуйтесь от Default* в `src/Defaults/` (Controller c DTO, Service/Repository singletons и т.п.). DTO авто-заполняются из Request (биндинг `Atlcom\\Dto` в провайдере).
- Предпочитайте сервисы фасадам. Пример: вместо ручных HTTP запросов к Telegram — `TelegramApiService`/`TelegramBotService`.
- Используйте макросы, а не ad-hoc код:
  - Query: `Model::withQueryLog()`, `DB::withQueryCache()` (см. `BuilderMacrosService`, `Query*Service`).
  - Http: `Http::telegramOrg()` и другие из `config[Http]`.
  - Коллекции/Str макросы включаются через конфиг.
- Логи пишутся в модели/таблицы из конфига. Глобальные флаги подключают middleware/слушателей автоматически.
- Хелперы из `src/Helpers/helpers.php` (например, `telegram()`, `sql()`, `isDebug()`) — используйте их, не дублируйте логику.

## Telegram — практично
- Исходящие сообщения отправляйте через `TelegramBotService::send()` c типизированными DTO (`src/Dto/TelegramBot/Out/*`). Сервис предотвращает дубли и всегда стреляет `TelegramBotEvent` с `TelegramBotOutResponseDto`.
- Низкоуровневый REST — `TelegramApiService::call()` (multipart/json), лучше вызывать готовые методы (`sendMessage`, `sendPhoto`, `sendVideo`, ...). Основа HTTP задаётся `HttpMacrosService` → `Http::telegramOrg()` и `config('laravel-helper')[Http][telegramOrg]`.

## Рабочие процессы
- Установка в приложение: `composer require atlcom/laravel-helper` → `php artisan vendor:publish --tag="laravel-helper"` → настроить `.env` → `php artisan migrate` → `php artisan optimize`.
- Миграции: автозагрузка пакета + публикация с тегом `laravel-helper` при встраивании.

## Интеграции
- Зависит от `atlcom/helper` и `atlcom/dto`. Логи HTTP — через события Http клиента; логи очередей — через хуки очереди; прочие логи — через стандартные события Laravel.

## Добавление фич
- Регистрируйте новые сервисы/слушатели/макросы в провайдере; добавляйте флаги в `config/laravel-helper.php` с разумными env-дефолтами и проводкой.
- Для логов/кеша — ставьте middleware/макросы + события + модели с конфигурируемыми таблицами.
- Для внешних API — добавляйте Http-макрос и выделенный Service; параметры — из конфига, не хардкод.

## Полезные ссылки
- Примеры в README: ConsoleLog/HttpLog/ModelLog/QueryLog/RouteLog/ViewLog, кеш (Http/Query/View), Profiler, Telegram Bot (`docs/readme_telegram_bot_events.md`).

## Примечания для агентов
- Держитесь принципа “config-first” и проводки через провайдер. Не обходите хелперы и макросы. Для контроллеров — DTO и базовые Default* классы.

