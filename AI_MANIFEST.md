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
- По возможности используй интерполяцию переменных в строках "пример {$var}", вместо конкатенации строк.
- По возможности используй стрелочные функции и добавляй к ним `static fn () => *`, если внутри не используется `$this`.
- По возможности используй `nullsafe` операторы `??` + `?->`.
- По возможности делай предпочтение в пользу оператора `match (true) {*, default => *}` + `?:` и избегай оператора `else`.
- Вставляй пустую строку:
  - перед оператором `default`;
  - перед оператором `return`, если перед ним не оператор `if`;
  - перед оператором `else`
  - между строками с логикой присвоения переменных и блоком с логикой работы с этими переменными.
- Вставляй две пустые строки:
  - между методами класса и функциями;
  - между свойствами класса и методами.

## Миграции
- Используй `foreignId` или `foreignUuid` для связанных полей.
- Используй индексы для полей с типом: `int`, `bool`, `enum`, `uuid`.
- Названия таблиц бери из модели `Model::getTableName()`.
- Комментарий к таблице делай как `Model::COMMENT`.
- Подключай поля timestamps и softDeletes и создай к ним индексы.
- Перед созданием новой таблицы используй удаление существующей таблицы `dropIfExists`.
- Перед изменением существующей таблицы используй проверку на существование таблицы `hasTable`.

Пример:
```php
<?php

declare(strict_types=1);

/**
 * @see \App\Domains\Devline\Models\VideoCameraWatcher
 * @see VideoCameraWatcherDto
 */
return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists(Model::getTableName());

        Schema::create(Model::getTableName(), static function (Blueprint $table) {
            $table->comment(Model::getTableComment());
            $table->id();

            $table->foreignId('user_id')->nullable(false)->index()
                ->comment('...')
                ->constrained(User::getTableName())->onUpdate('cascade')->onDelete('cascade');

            $table->foreignUuid('user_uuid')->nullable(true)->index()
                ->comment('...')
                ->constrained(User::getTableName())->onUpdate('cascade')->onDelete('cascade');

            $table->string('name')->nullable(true)
                ->comment('...');
            $table->enum('type', ModelTypeEnum::getValues())->nullable(false)->index()
                ->default(ModelTypeEnum::getDefault())
                ->comment('...');

            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['created_at']);
        });
    }


    public function down()
    {
        Schema::dropIfExists(Model::getTableName());
    }
};
```


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
<?php

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
