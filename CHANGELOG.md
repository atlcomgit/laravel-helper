# История изменений проекта

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
