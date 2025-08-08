# Команды Telegram Bot — как использовать в laravel-helper

Этот файл описывает, как управлять командами бота Telegram (BotCommand) с помощью DTO и сервиса, входящих в пакет.

Поддерживаются операции:
- Установка команд: setMyCommands
- Удаление команд: deleteMyCommands (unsetMyCommands)
- Получение команд: getMyCommands

## Основные классы

- DTO:
  - `Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetMyCommandsDto`
  - `Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutUnsetMyCommandsDto`
  - `Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutGetMyCommandsDto`
  - `Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutCommandScopeDto` — область видимости (scope)
- Enum:
  - `Atlcom\LaravelHelper\Enums\TelegramBotCommandScopeEnum`
  - `Atlcom\LaravelHelper\Enums\TelegramBotLanguageEnum`
- Сервис (внутри):
  - `Atlcom\LaravelHelper\Services\TelegramBot\TelegramBotService` (работает из джобы/события)
  - `Atlcom\LaravelHelper\Services\TelegramApiService` (низкоуровневый доступ к Bot API)

Все Out-DTO наследуются от `TelegramBotOutDto` и поддерживают `->send()` для отправки сразу или `->dispatch()` для постановки в очередь.

## Структура команд

Команды передаются как массив структур:

```php
$commands = [
    ['command' => 'start', 'description' => 'Старт'],
    ['command' => 'help',  'description' => 'Помощь'],
];
```

## Область видимости (scope)

Scope описывается DTO `TelegramBotOutCommandScopeDto` и enum `TelegramBotCommandScopeEnum`.

Варианты enum:
- Default — по умолчанию (глобально)
- AllPrivateChats — все приватные чаты
- AllGroupChats — все групповые чаты
- AllChatAdministrators — все администраторы
- Chat — конкретный чат (требуется chat_id)
- ChatAdministrators — администраторы конкретного чата (требуется chat_id)
- ChatMember — конкретный участник чата (требуются chat_id и user_id)

Удобные методы DTO для задания области:

```php
$scope = \Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutCommandScopeDto::create();
$scope->forChat(123456789);                 // type=Chat, chatId=...
$scope->forChatAdministrators(123456789);   // type=ChatAdministrators, chatId=...
$scope->forChatMember(123456789, 42);       // type=ChatMember, chatId=..., userId=...
```

## Язык

`TelegramBotLanguageEnum` (например: En, Ru, Uk, De). В Bot API передается как `language_code`.

## Примеры

### 1) Установить команды по умолчанию (глобально)

```php
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetMyCommandsDto;

TelegramBotOutSetMyCommandsDto::create([
    'commands' => [
        ['command' => 'start', 'description' => 'Старт'],
        ['command' => 'help',  'description' => 'Помощь'],
    ],
])->send();
```

### 2) Установить команды для всех приватных чатов и языка RU

```php
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetMyCommandsDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutCommandScopeDto;
use Atlcom\LaravelHelper\Enums\TelegramBotCommandScopeEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotLanguageEnum;

$dto = TelegramBotOutSetMyCommandsDto::create([
    'commands' => [
        ['command' => 'start', 'description' => 'Старт'],
    ],
    'language' => TelegramBotLanguageEnum::Ru,
]);
$dto->scope->type = TelegramBotCommandScopeEnum::AllPrivateChats;
$dto->send();
```

### 3) Установить команды для конкретного чата

```php
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutSetMyCommandsDto;
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\Data\TelegramBotOutCommandScopeDto;

$dto = TelegramBotOutSetMyCommandsDto::create([
    'commands' => [
        ['command' => 'start', 'description' => 'Старт'],
    ],
]);
$dto->scope->forChat(123456789);
$dto->send();
```

### 4) Удалить команды (по умолчанию)

```php
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutUnsetMyCommandsDto;

TelegramBotOutUnsetMyCommandsDto::create()->send();
```

### 5) Удалить команды для участника чата

```php
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutUnsetMyCommandsDto;

$dto = TelegramBotOutUnsetMyCommandsDto::create();
$dto->scope->forChatMember(123456789, 42);
$dto->send();
```

### 6) Получить команды (по умолчанию)

```php
use Atlcom\LaravelHelper\Dto\TelegramBot\Out\TelegramBotOutGetMyCommandsDto;

$dto = TelegramBotOutGetMyCommandsDto::create();
$dto->send();
// Результат в $dto->response
```

## Прямой вызов API (опционально)

Если нужно обойтись без DTO, можно вызывать сервис напрямую:

```php
app(\Atlcom\LaravelHelper\Services\TelegramApiService::class)->setMyCommands(
    botToken: '123:ABC',
    commands: [
        ['command' => 'start', 'description' => 'Старт'],
    ],
    options: [
        'scope' => ['type' => 'default'],
        'language_code' => 'ru',
    ]
);
```

## Примечания
- DTO автоматически используют токен и настройки из конфига пакета (если не переопределены).
- `->send()` отправляет сразу (или синхронно через джобу, в зависимости от конфигурации). Для явной асинхронной постановки используйте `->dispatch()`.
- В ответе используйте `TelegramBotOutResponseDto`; поле `$dto->response` содержит ok/result/message/description.
