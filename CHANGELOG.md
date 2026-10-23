# История изменений проекта

## Без номера: Итоговый отчёт тестов

- Дата: 2026-10-23.
- Автор: 🅰️🅻🅴🅺.
- Ветка: master.
- Что сделано: добавлено расширение PHPUnit `TestReportExtension`, которое после всех тестов печатает итоговый отчёт:
  время прогона и суммарное время в тестах, ускорение и разброс загрузки процессов paratest, пик памяти, число тестов
  по результатам, утверждения, среднюю длительность и скорость, минимум, медиану, p95 и максимум, распределение по
  интервалам, медленные тесты и классы, тесты с ошибкой. Подключается в `phpunit.xml`, параметры `slowSeconds`
  (порог медленного теста) и `slowLimit` (длина списка).
- Ключевые моменты: при paratest воркеры передают результаты главному процессу через временный каталог из переменной
  окружения `LARAVEL_HELPER_TESTING_REPORT_DIR`; главный процесс печатает отчёт после итогов paratest и удаляет
  каталог. Отчёт не печатается с `--teamcity` и `--no-output` и не меняет код завершения; счёт ошибок совпадает с
  итогами PHPUnit, включая ошибки `setUpBeforeClass` и `tearDownAfterClass`. Проверено на PHPUnit 10.5 и 11.5,
  paratest 7.8. Добавлены тесты `TestReportTest`, `TestReportCollectorTest` и `TestReportExtensionTest`.
- Файлы:
    CHANGELOG.md
    README.md
    src/Testing/TestReport.php
    src/Testing/TestReportCollector.php
    src/Testing/TestReportExtension.php
    src/Testing/TestReportStorage.php
    tests/Unit/TestReportCollectorTest.php
    tests/Unit/TestReportExtensionTest.php
    tests/Unit/TestReportTest.php

## Без номера: Сокращено чтение конфигурации на каждом запросе модели

- Дата: 2026-10-23.
- Автор: 🅰️🅻🅴🅺.
- Ветка: master.
- Что сделано: `ModelCacheTrait::newEloquentBuilder()` (построитель каждого запроса модели) читает флаги кеша и логов
  одним обращением к конфигурации вместо трёх `Lh::config()`, каждый из которых заново проверял глобальный выключатель
  `laravel-helper.enabled`; `QueryLogMethodsTrait::createQueryLog()` при выключенном логе запросов сразу выходит, не
  читая порог длительности. В `.env.example` добавлены `HELPER_TESTING_LOG_DATABASE_PREPARE_ENABLED` и
  `HELPER_TESTING_LOG_TEST_NAME_ENABLED`.
- Ключевые моменты: поведение не меняется, включая глобальный выключатель; добавлены тесты `ModelCacheTraitTest` и
  `QueryLogMethodsTraitTest`. Построитель запроса создаётся быстрее: 9,9 → 8,0 мкс при включённом кеше запросов, при
  выключенных флагах экономия до ~6 мкс на запрос.
- Файлы:
    .env.example
    CHANGELOG.md
    src/Traits/ModelCacheTrait.php
    src/Traits/QueryLogMethodsTrait.php
    tests/Unit/ModelCacheTraitTest.php
    tests/Unit/QueryLogMethodsTraitTest.php

## Без номера: Ускорена подготовка тестовой БД и чтения через QueryOperationsTrait

- Дата: 2026-10-23.
- Автор: 🅰️🅻🅴🅺.
- Ветка: master.
- Что сделано: `TestingTrait` готовит тестовую БД один раз на процесс для всех классов, подключивших трейт:
  `migrate:fresh`, миграции пакета и сиды выполняются при `testing_log.database.fresh` или изменении хеша файлов
  миграций и таблицы `migrations`. Хеш хранится в `storage/framework/testing/migrations_{env}_{database}.hash`;
  прежняя версия читала и записывала его по разным путям, поэтому пересоздавала БД при каждом запуске. Ошибка
  команды подготовки останавливает тесты с кодом и выводом команды, журнал моделей после подготовки восстанавливается.
  Новые настройки: `testing_log.database.prepare` (`HELPER_TESTING_LOG_DATABASE_PREPARE_ENABLED`) отключает подготовку,
  если проект готовит БД сам; `testing_log.test_name.enabled` (`HELPER_TESTING_LOG_TEST_NAME_ENABLED`) отключает
  вывод имени теста. `QueryOperationsTrait` при чтении собирает SQL с параметрами и список таблиц только для кеша
  запросов или включённого лога запросов.
- Ключевые моменты: обе новые настройки по умолчанию true, поэтому прежнее поведение сохраняется и со старым
  опубликованным конфигом. При `HELPER_TESTING_LOG_DATABASE_FRESH_ENABLED=false` БД теперь действительно
  пересоздаётся только после изменения миграций. Добавлены тесты `TestingTraitTest` и `QueryOperationsTraitTest`.
  Новые переменные в `.env.example` не добавлены: изменение env-файлов требует отдельного согласия.
- Файлы:
    CHANGELOG.md
    config/laravel-helper.php
    src/Traits/QueryOperationsTrait.php
    src/Traits/TestingTrait.php
    tests/Unit/QueryOperationsTraitTest.php
    tests/Unit/TestingTraitTest.php

## Без номера: Автоматическая версия и тег после коммита

- Дата: 2026-10-23.
- Автор: 🅰️🅻🅴🅺.
- Ветка: master.
- Что сделано: добавлен файл VERSION и Git-хуки повышения patch-версии перед коммитом и создания тега после него.
  После push GitHub Actions обновляет связанный тег существующего релиза той же серии major.minor либо
  создаёт релиз новой серии. Описание и вложения существующего релиза сохраняются.
- Ключевые моменты: тег берётся из завершённого коммита; повтор после отмены не повышает версию снова;
  существующие теги не перезаписываются. Аннотированные теги публикуются через push.followTags;
  локальные хуки необходимо подключать отдельно в каждой рабочей копии. Публикации одной серии сериализованы,
  выбирают максимальный patch и не откатывают релиз на старую версию; доступен ручной запуск для существующего тега.
- Файлы:
    .githooks/post-commit
    .githooks/pre-commit
    .github/workflows/release.yml
    .gitignore
    CHANGELOG.md
    README.md
    VERSION

## Без номера: Исправлен драйвер SQL Server и добавлен MariaDB в фабрике подключений

- Дата: 2026-10-08.
- Автор: 🅰️🅻🅴🅺.
- Ветка: master.
- Что сделано: фабрика подключений распознаёт драйвер SQL Server по стандартному имени Laravel `sqlsrv` вместо
  несуществующего `sqlserver` и поддерживает драйвер `mariadb` через новый `MariaDbConnection`.
- Ключевые моменты: раньше подключения SQL Server и MariaDB создавались стандартными классами Laravel без
  `ConnectionTrait`, поэтому кеширование и логирование запросов для них не работали; теперь фабрика покрывает все
  стандартные драйверы Laravel (`mysql`, `mariadb`, `pgsql`, `sqlite`, `sqlsrv`). Драйвер `mariadb` есть начиная с
  Laravel 11.
- Файлы:
    CHANGELOG.md
    src/Databases/Connections/ConnectionFactory.php
    src/Databases/Connections/MariaDbConnection.php

## 365988414: QueryLog не маскирует исходные ошибки бизнес-запросов

- Дата: 2026-08-22.
- Автор: 🅰️🅻🅴🅺.
- Ветка: master.
- Что сделано: ошибки сохранения и отправки QueryLog больше не заменяют исходное исключение запроса; Telescope всегда
  восстанавливает прежнее состояние, а для query-log можно выбрать отдельное подключение очереди.
- Ключевые моменты: observability работает по принципу no-throw; QueryLog job отправляется через явный Dispatcher без
  неуправляемого запуска в destructor; для failed PostgreSQL-транзакций рекомендуется Redis/SQS или независимое DB
  connection, потому что основное соединение уже находится в aborted-состоянии.
- Файлы:
    .env.example
    CHANGELOG.md
    README.md
    config/laravel-helper.php
    src/Dto/QueryLogDto.php
    src/Jobs/QueryLogJob.php
    src/Traits/QueryLogMethodsTrait.php
    src/Traits/TelescopeTrait.php
    tests/Unit/QueryLogDto/DispatchTest.php
    tests/Unit/QueryLogJob/InvokeTest.php
    tests/Unit/TelescopeTrait/WithoutTelescopeTest.php

## TRELLO-54: Автоответ на чаты по типу "баты" и "киоски"

- Дата: 2026-07-26.
- Автор: 🅰️🅻🅴🅺.
- Ветка: master.
- Что сделано: восстановлен стандартный ответ для ошибок Laravel-валидации со статусом 422 и отдельными сообщениями для каждого поля.
- Ключевые моменты: общий обработчик сохраняет прежний формат остальных исключений; frontend может безопасно показывать ошибки возле соответствующих полей формы.
- Файлы:
    CHANGELOG.md
    README.md
    src/Defaults/DefaultExceptionHandler.php
    tests/Unit/DefaultExceptionHandlerTest.php
