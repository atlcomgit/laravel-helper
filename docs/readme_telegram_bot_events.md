# События и слушатели Telegram Bot в laravel-helper

Этот документ описывает события и слушателей бота Telegram, которые входят в пакет. Он поможет понять поток обработки входящих/исходящих сообщений, доступные события, их полезную нагрузку и как подключить свои слушатели.

## Краткий поток обработки

1) Входящее сообщение (webhook):
- Контроллер `TelegramBotController@webhook` формирует `TelegramBotInDto` и диспатчит событие `TelegramBotEvent`.
- Слушатель `TelegramBotEventListener` (в очереди) вызывает `TelegramBotListenerService->incoming()`.
- Сервис сохраняет чат, пользователя и сообщение в БД и диспатчит `TelegramBotMessageEvent`.

2) Исходящее сообщение (send):
- Любое отправленное через `TelegramBotService->send()` сообщение (DTO-наследники `TelegramBotOutDto`) после попытки отправки всегда диспатчит `TelegramBotEvent`.
- Слушатель `TelegramBotEventListener` вызывает `TelegramBotListenerService->outgoing()`.
- Сервис сохраняет результат отправки как исходящее сообщение и диспатчит `TelegramBotMessageEvent`.

Итого: вы можете реагировать либо на «сырой» трафик (TelegramBotEvent: до/без сохранения), либо на сообщения уже сохраненные в БД (TelegramBotMessageEvent).

---

## События

### TelegramBotEvent
- Namespace: `Atlcom\\LaravelHelper\\Events\\TelegramBotEvent`
- Полезная нагрузка: `public TelegramBotDto $dto` где `$dto` — это `TelegramBotInDto` (входящее) ИЛИ `TelegramBotOutDto` (исходящее).
- Где диспатчится:
  - Входящее: `TelegramBotController@webhook`
  - Исходящее: `TelegramBotService->send()` (в блоке finally — всегда)
- Для чего: перехват любого трафика бота. Удобно для собственной маршрутизации команд до сохранения в БД, A/B логики и т.п.

Пример быстрой обработки обоих вариантов:

```php
use Atlcom\\LaravelHelper\\Events\\TelegramBotEvent;
use Atlcom\\LaravelHelper\\Dto\\TelegramBot\\TelegramBotInDto;
use Atlcom\\LaravelHelper\\Dto\\TelegramBot\\TelegramBotOutDto;

return \\Illuminate\\Support\\Facades\\Event::listen(
    TelegramBotEvent::class,
    function (TelegramBotEvent $event) {
        match (true) {
            $event->dto instanceof TelegramBotInDto => /* входящее */ null,
            $event->dto instanceof TelegramBotOutDto => /* исходящее */ null,
        };
    }
);
```

Замечания:
- По умолчанию это событие уже обрабатывается пакетом (см. ниже «Слушатели по умолчанию»). Если вы добавляете свой слушатель, учитывайте порядок и идемпотентность.

### TelegramBotMessageEvent
- Namespace: `Atlcom\\LaravelHelper\\Events\\TelegramBotMessageEvent`
- Полезная нагрузка: `public TelegramBotMessage $message`
- Где диспатчится: внутри `TelegramBotListenerService` после сохранения сообщения в БД (и для входящих, и для исходящих).
- Для чего: реагировать на факты сохраненных сообщений (удобно для бизнес-логики, которая опирается на БД).

Структурно модель `TelegramBotMessage` содержит, среди прочего:
- `type`: `Incoming` | `Outgoing` (`TelegramBotMessageTypeEnum`)
- `status` (для входящих):
  - `Reply` — если это ответ на предыдущее сообщение
  - `Callback` — если пришла callback-кнопка
  - `Update` — если пришло редактирование сообщения
  - `New` — иначе
- `slug` (для исходящих из `TelegramBotOutSendMessageDto`), если вы его задавали
- `external_update_id` (для входящих) = `updateId` телеграма
- `telegram_bot_message_id` — связь на предыдущее сообщение (reply/callback/или `previousMessageId` из DTO для исходящих)
- `info` — вспомогательная информация:
  - входящее: `callback`, `buttons` (если были)
  - исходящее: `buttons`, `keyboards` (если были)

Пример подписки:

```php
use Atlcom\\LaravelHelper\\Events\\TelegramBotMessageEvent;
use Illuminate\\Contracts\\Queue\\ShouldQueue;

class MyTelegramMessageListener implements ShouldQueue
{
    public function __invoke(TelegramBotMessageEvent $event): void
    {
        $msg = $event->message;
        // $msg->type, $msg->status, $msg->text, $msg->slug, $msg->info, связи chat/user/parent и т.д.
    }
}

// Регистрация (например, в AppServiceProvider@boot)
\\Illuminate\\Support\\Facades\\Event::listen(
    TelegramBotMessageEvent::class,
    MyTelegramMessageListener::class
);
```

---

## Слушатели по умолчанию

### TelegramBotEventListener
- Namespace: `Atlcom\\LaravelHelper\\Listeners\\TelegramBotEventListener`
- Включен, если `helper.TelegramBot.enabled = true` (подключается в `LaravelHelperServiceProvider`).
- Работает из очереди: имя очереди берется из `helper.TelegramBot.queue`.
- Что делает:
  - Для `TelegramBotInDto` вызывает `TelegramBotListenerService->incoming($dto)`.
  - Для `TelegramBotOutDto` вызывает `TelegramBotListenerService->outgoing($dto)`.

### TelegramBotListenerService (под капотом)
- Namespace: `Atlcom\\LaravelHelper\\Services\\TelegramBot\\TelegramBotListenerService`
- incoming():
  - Сохраняет чат и пользователя (DTO -> save())
  - Определяет `status` (Reply/Callback/Update/New)
  - Связывает с предыдущим сообщением (reply/callback/или последнее исходящее)
  - Сохраняет входящее сообщение и диспатчит `TelegramBotMessageEvent`
- outgoing():
  - Пропускает, если `$dto->response->status === false`
  - Сохраняет чат и пользователя из ответа Telegram
  - Добавляет `slug` (если `TelegramBotOutSendMessageDto`)
  - Определяет связь с предыдущим сообщением (reply или `previousMessageId` из DTO)
  - Сохраняет исходящее сообщение и диспатчит `TelegramBotMessageEvent`
- Ошибки в процессе incoming/outgoing отправляются в Telegram (тип `Error`) через `telegram(...)` при включенной конфигурации логов.

Примечание: Пакет не подключает дефолтных слушателей для `TelegramBotMessageEvent`. Это точка расширения для вашей бизнес-логики.

---

## Конфигурация

Раздел `helper.TelegramBot` (`config/laravel-helper.php`):
- `enabled` — включает функциональность бота (миграции, события, слушатель)
- `queue` — имя очереди для `TelegramBotEventListener`
- `connection` — БД для таблиц бота
- `table_chat`, `table_user`, `table_message` — имена таблиц
- `model_chat`, `model_user`, `model_message` — классы моделей
- `token`, `name`, `link`, `webhook` — параметры бота

При включенном боте автоматически подключаются миграции `database/migrations_telegram_bot` и роуты `routes/api-telegram-bot.php`.

---

## Практические рекомендации

- Нужны быстрые реакции без записи в БД — подписывайтесь на `TelegramBotEvent`.
- Нужна логика на «подтвержденных» данных — подписывайтесь на `TelegramBotMessageEvent`.
- Для маршрутизации команд удобно использовать `slug` исходящих сообщений или анализ `text` входящих. После сохранения обработчик будет получать событие `TelegramBotMessageEvent` для обоих направлений.
- Для отправки сообщений используйте DTO из раздела Out (см. `docs/readme_telegram_bot_api.md`), например `TelegramBotOutSendMessageDto::create(...)->send()` — после отправки будет сохранено исходящее сообщение и сгенерировано событие `TelegramBotMessageEvent`.

---

## Минимальные примеры

1) Реакция на входящую callback-кнопку (после сохранения в БД):
```php
class OnCallbackListener
{
    public function __invoke(\\Atlcom\\LaravelHelper\\Events\\TelegramBotMessageEvent $e): void
    {
        if ((string)$e->message->status !== 'Callback') {
            return;
        }

        $callback = $e->message->info['callback'] ?? null;
        // обработка callback...
    }
}
```

2) Реакция на исходящее сообщение с определенным slug:
```php
class OnOutgoingBySlug
{
    public function __invoke(\\Atlcom\\LaravelHelper\\Events\\TelegramBotMessageEvent $e): void
    {
        if ((string)$e->message->type !== 'Outgoing') {
            return;
        }
        if ($e->message->slug !== 'welcome') {
            return;
        }
        // пост-обработка после отправки приветствия...
    }
}
```

---

## Отладка и безопасность

- Все операции проходят через очередь, убедитесь что очередь запущена и соответствует `helper.TelegramBot.queue`.
- Ошибки внутри `TelegramBotListenerService` логируются в Telegram (если включен `helper.TelegramLog`).
- Исключения ваших слушателей лучше обрабатывать и логировать самостоятельно, чтобы не блокировать очередь.

---

## Ссылки по коду
- События: `src/Events/TelegramBotEvent.php`, `src/Events/TelegramBotMessageEvent.php`
- Слушатель: `src/Listeners/TelegramBotEventListener.php`
- Сервис слушателя: `src/Services/TelegramBot/TelegramBotListenerService.php`
- Отправка сообщений: `src/Services/TelegramBot/TelegramBotService.php`
- Контроллер вебхука: `src/Controllers/TelegramBotController.php`
- Конфиг: `config/laravel-helper.php` (секция TelegramBot)

[Документация по TelegramBot API](docs/readme_telegram_bot_api.md)
