# Инструкции для ИИ-агента Copilot по пакету (atlcom/laravel-helper)

Назначение: пакет Laravel, добавляющий логирование, кеширование, контроллеры на DTO, профайлер и инструменты Telegram-бота. Используйте готовые базовые классы, сервисы, события, макросы и флаги конфига — не изобретайте заново.

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

[AI_MANIFEST.md]

# Общая инструкция для ИИ-агентов

## Настройка ИИ навыков
- ИИ агент является опытным специалистом в следующих областях:
	- Превосходно знает языки программирования: `PHP 8+`, `Javascript`.
	- Превосходно знает базы данных: `Postgres`, `Mysql`, `MariaDb`, `Redis`
	- Превосходно знает фреймворки: `Laravel 10-12+`, `VueJS 2/3+`, `Nuxt 2/3+`.
	- Превосходно знает php пакеты: `Spatie`, `Atlcom`.
	- Превосходно знает брокеры очередей: `Kafka`, `RabbitMQ`.
	- Превосходно умеет подключать в php проекты: `Docker-Compose`, `Makefile`, `Websocket`, `OpenSearch`.
	- Превосходно знает api документацию сервисов: `Telegram`, `Max`, `Mango`, `Devline`.
- Прогнозирует разработку на будущее с внедрением новых фич.
- Оптимизирует SQL запросы и переводит их в конструкторы фреймворка Laravel: `QueryBuilder`, `EloquentBuilder`.
- Оптимизирует новый код.
- Проверяет существующий код и не дублирует при написании нового кода.
- Ничего не выдумывает бесполезного.

## Локализация
- Добавлять описание в phpdoc для всех методов и классов на русском языке, после описания добавлять пустую строку и далее описание аргументов.
- Добавлять комментарии к блокам кода на русском языке.
- Выводить текст в консоль и UI на русском языке.

## Архитектура и ключевые модули
- Пакет: Laravel, PHP 8.2+, Redis, Postgres/MySQL/MariaDB.

## Проектные помощники (Hlp / Lh / Dto)
- Dto: базовый `*/Defaults/DefaultDto` (на основе `atlcom/laravel-helper`; совместим с `atlcom/dto`). Конкретные Dto в `*/Dto/**` расширяют его.
- В `DefaultDto` уже подключены трейты `App\Traits\Dto\DtoAsResourceTrait`, `DtoAsCollectionTrait`, `DtoAsModelTrait` — используйте их авто-конвертации в ресурсы/коллекции/модели.
- Lh (`atlcom/laravel-helper`): предоставляет базовые классы `DefaultRequest/DefaultModel/DefaultController/DefaultResource`, исключения и макросы (Builder/Http/Str). В проекте используются их наследники в `*/Defaults/**`.
- Hlp (`atlcom/helper`): набор утилит для повседневных операций (строки/массивы/даты и пр.). Применяйте для сокращения рутины, когда уместно.
- TelegramBot (`atlcom/laravel-helper`): фасад для отправки сообщений в телеграм бота.
- Helpers (`src/Helpers/helpers.php`): функции помощники для быстрого доступа к настройкам приложения.

## Расширение классов (extends)
- Классы консольных команд `*/Console/Commands/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultCommand`.
- Классы контроллеров `*/Controllers/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultController`.
- Классы dto `*/Dto/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultDto`.
- Классы событий `*/Events/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultEvent`.
- Классы исключений `*/Exceptions/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultException`.
- Классы задач `*/Jobs/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultJob`.
- Классы слушателей `*/Listeners/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultListener`.
- Классы логгеров `*/Loggers/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultLogger`.
- Классы моделей `*/Models/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultModel`.
- Классы репозиториев `*/Repositories/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultRepository`.
- Классы запросов `*/Requests/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultRequest`.
- Классы ресурсов `*/Resources/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultResources`.
- Классы сервисов `*/Services/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultService`.
- Классы тестов `tests/*` должны расширяться от класса `Atlcom\LaravelHelper\Defaults\DefaultTest`.

## Написание новых api роутов
1) Добавляй маршруты api запроса в `routes/api.php`.
2) Добавляй классы контроллеров в `*/Controllers/*`.
3) Методы контроллеров могут принимать `Dto` с валидацией через метод `rules`, добавляй классы Dto в `*/Dto/*`.
4) Добавляй классы сервисов в `*/Services/*` и передай в него `Dto`.
5) Добавляй классы репозиториев в `*/Repositories/*` и передавай в них `Dto` из сервисов.
6) Добавляй классы ресурсов в `*/Resources/*` для ответа на api запросы.
7) Придерживайся принципа написания `sql запросов` и `query builder` только в репозиториях.

## Стиль php кода
- Придерживайся `PSR-12`.
- Длина строк кода не должна превышать `120 символов`.
- Добавляй в php файлы в самом начале `declare(strict_types=1);` отделяя пустыми строками до и после.
- По возможности используй интерполяцию переменных в строках, вместо конкатенации.
- По возможности используй стрелочные функции и добавляй к ним `static fn () => *`, если внутри не используется `$this`.
- По возможности используй `nullsafe` операторы `??` + `?->`.
- По возможности делай предпочтение в пользу оператора `match (true) {*, default => *}` + `?:` и избегай оператора `else`.

### Запуск консольных команд
- Запуск консольных команд через `make artisan *`.

### Тесты (структура и правила)
- Размещай файлы тестов по принципу `tests/Unit/*/{Название тестируемого класса}/{Название тестируемого метода}Test.php`, каждый метод класса тестируется в отдельном файле.
- Названия классов тестов должны быть в `PascalCase`.
- Названия методов тестов должны быть в `camelCase` и начинаться с полного названия тестируемого метода.
- Добавляй `PHPDoc` для методов в тестах.
- Запуск: `make phpunit *`. Конфигурация — `phpunit.xml` (ENV=testing, драйверы: cache/session=array, queue=sync, mail=array, storage=local).
- Дополнительные папки тестов: `tests/Unit/**`, `tests/Feature/**`. Моки — `tests/Mock/**`.

### Запуск тестов через make phpunit (с фильтрами)
- Базовый запуск: `make phpunit`
- По классу/маске: `make phpunit FILTER=ExampleTest`
- По файлу: `make phpunit FILE=tests/Unit/*.php`
- Тест конкретного метода:
  - Рекомендуемо с ограничением по файлу: `make phpunit FILE=tests/Unit/*.php FILTER='::testName$'`
  - Альтернатива (может не сработать во всех версиях PHPUnit): `make phpunit FILTER='ExampleTest::testName$'`
- Сложные regex (параметризованные тесты):
  - Пример: `make phpunit FILE=tests/Unit/*.php FILTER='^.*::(testName)(( with (data set )?.*)?)?$'`
  - Примечание: FILTER в Makefile передаётся в кавычках — спецсимволы (скобки, пробелы, $) не сломают оболочку.
- Формат TeamCity: добавить флаг `TEAMCITY=1` — `make phpunit TEAMCITY=1 FILTER=... FILE=...`
- Доп. аргументы PHPUnit: `make phpunit ARGS='--testdox'` (совместимо с FILTER/FILE/TEAMCITY)

### Стиль PHPDoc для тестов
- Для каждого тест-метода добавляйте PHPDoc следующего вида:
  - Первая строка — короткое описание по-русски (например: «Тест метода сервиса», «Тест метода контроллера»).
  - На следующей строке — ссылка на тестируемый метод через `@see` в формате с полным пространством имён: `@see \FQCN::method()`.
  - Не добавляйте `@see` на весь класс — только на конкретный метод.
  - После `@see` — пустая строка, затем описание аргументов.

Пример:

```php

declare(strict_types=1);

namespace App\Services;

class ExampleService
/**
 * Тест метода сервиса
 * @see \App\Services\ExampleService::exampleMethod()
 *
 * @return void
 */
#[Test]
public function exampleMethod(): void { /* ... */ }
```

## Документация по пакетам
- Используй документацию [Laravel](https://laravel.com/docs/12.x).
- Используй документацию для вспомогательных функций [Hlp](https://github.com/atlcomgit/helper), примеры в [тестах](https://github.com/atlcomgit/helper/tree/master/tests).
- Используй документацию для [Dto](https://github.com/atlcomgit/dto), примеры в [тестах](https://github.com/atlcomgit/dto/tree/master/tests/Examples).
- Используй документацию для [Lh](https://github.com/atlcomgit/laravel-helper).

# Updated: 2025-08-18 23:30:07
