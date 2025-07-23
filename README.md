# Laravel Helper

Класс помощник для Laravel добавляет функционал во фреймворк:
- Логирование консольных команд
- Логирование входящих/исходящих http запросов
- Логирование изменения моделей, в том числе массовое изменение/удаление
- Логирование query запросов через Eloquent/Database/Connection
- Логирование запуска и выполнения очередей
- Логирование зарегистрированных роутов и их использование
- Логирование рендеринга blade шаблонов
- Кеширование http запросов через PendingRequest
- Кеширование query запросов через Eloquent/Database/Connection
- Кеширование рендеринга blade шаблонов
- Обработка всех исключений с отправкой логов в телеграмм
- Внедрение Dto вместо Request в контроллеры, которое поддерживает: правила валидации, мутацию, маппинг, хуки при создании/изменении свойств
- Профилирование методов класса на производительность и потребление памяти

<hr style="border:1px solid black">

### Подключение пакета

##### 1. Установка пакета

```bash
composer require atlcom/laravel-helper
```

##### 2. Публикация настроек и миграций

```bash
php artisan vendor:publish --tag="laravel-helper"
```

##### 3. Настройка параметров .env

[/config/laravel-helper.php](/config/laravel-helper.php)

[/.env.example](/.env.example)

##### 4. Оптимизация приложения

```bash
php artisan optimize
```

##### 5. Миграция базы данных

```bash
php artisan migrate
```

<hr style="border:1px solid black">

### Примеры логирования

##### ConsoleLog

Логирование выполнения консольных команд

```php
use Atlcom\LaravelHelper\Defaults\DefaultCommand;

class ExampleCommand extends DefaultCommand
{
	protected ?bool $withTelegramLog = true;
	protected mixed $telegramComment = null;
	protected ?bool $withConsoleLog = true;

	public function handle(): int
	{
		$this->outputBold($this->description);
		$this->outputEol();

		// ...

		$this->telegramLog = isLocal() || isProd();
		$this->telegramComment = 'Комментарий';

		return self::SUCCESS;
	}
}

// или

Artisan::call(ExampleCommand::class, ['--telegram', '--log']);
```

##### HttpLog

Логирование входящих запросов в таблице helper_http_logs

```php
use Atlcom\LaravelHelper\Middlewares\HttpLogMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return '';
})->middleware(HttpLogMiddleware::class);
```

Логирование исходящих запросов в таблице helper_http_logs

```php
use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Enums\HttpLogHeaderEnum;
use Illuminate\Support\Facades\Http;

class HttpServiceProvider
{
	public function boot(): void
	{
		Http::macro(
            'exampleRu',
            fn () => Http::baseUrl(rtrim(config('example.url'), '/'))
                ->replaceHeaders(HttpLogService::getLogHeaders(HttpLogHeaderEnum::SmsRu))
        );
	}
}

class ExampleService extends DefaultService
{
	public function getHttp(): Http|PendingRequest
    {
        return Http::exampleRu();
    }

	public function request(): void
	{
		$response = $this->getHttp()->post("/", []);

		// ...
	}
}
```

##### ModelLog

Логирование изменений модели в таблице helper_model_logs

```php
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Illuminate\Database\Eloquent\Model;

// class Example extends Model
class Example extends DefaultModel
{
	// use ModelLogTrait;

    protected ?bool $withModelLog = true;
    public $guarded = ['id'];
    public $timestamps = true;
    protected $casts = [];
}

class ExampleRepository
{
	public function find(): void
	{
		Example::query()->withModelLog()->first();
	}

	public function get(): void
	{
		Example::query()->withModelLog()->get();
	}

	public function create(): void
	{
		Example::query()->withModelLog()->create([]);
	}

	public function update(): void
	{
		Example::query()->withModelLog()->update([]);
	}

	public function delete(): void
	{
		Example::query()->withModelLog()->delete();
	}
}
```

##### ProfilerLog

Логирование профилирования методов класса

```php
use Atlcom\LaravelHelper\Defaults\DefaultController;

class Example extends DefaultProfiler
{
	public function example() {}
}

new Example()->_example();

// или

class Example
{
	use ProfilerLogTrait;

	public static function example() {}
}

Example::_example();
```

##### RouteLog

Логирование зарегистрированных роутов в таблице helper_route_logs

```php
use Illuminate\Support\Facades\Route;

Route::get('/example', [ExampleController::class, 'example']);
```

##### QueryLog

Логирование query запросов в таблице helper_query_logs

```php
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultController;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Illuminate\Database\Eloquent\Model;

// class Example extends Model
class Example extends DefaultModel
{
	// use ModelTrait;
}

class ExampleRepository extends DefaultRepository
{
	public function example(ExampleDto $dto)
	{
		DB::withQueryLog()->select('select * from users');
		DB::withQueryLog()->statement('select * from users');
		DB::table('users')->withQueryLog()->first();
		DB::table('users')->withQueryLog()->insert(['name' => Hlp::fakeName()]);
		DB::table('users')->withQueryLog()->update(['name' => Hlp::fakeName()]);

		Example::withQueryLog()->first();
		Example::withQueryLog()->create(['name' => Hlp::fakeName()]);
		Example::query()->withQueryLog()->count();
		Example::query()->withQueryLog()->exists();
		Example::first()->fill(['name' => Helper::fakeName()])->withQueryLog()->save();
		Example::query()->withQueryLog()->where('id', '>=', 1)->update(['name' => Helper::fakeName()]);
		Example::query()->withQueryLog()->where('id', Example::orderByDesc('id')->first()?->id)->delete();
	}
}
```

##### QueueLog

Логирование очередей в таблице helper_queue_logs

```php
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Defaults\DefaultJob;

class ExampleDto extends DefaultDto
{

}

class ExampleJob extends DefaultJob
{
    public bool $withQueueLog = false;

    public function __invoke(): void {}
}

// или

dispatch((new ExampleJob())->withQueueLog());
```

##### ViewLog

Логирование рендеринга blade шаблонов в таблице helper_view_logs

```php
use Atlcom\LaravelHelper\Defaults\DefaultController;

class ExampleController extends DefaultController
{
	public function example()
	{
		return $this->withViewLog()->view(view: 'example', data: ['test' => true], mergeData: []);
		return $this->withViewLog()->view(view: 'example', data: ['time' => now()], ignoreData: ['time']);
	}
}
```

<hr style="border:1px solid black">

### Примеры кеширования

##### QueryCache

Кеширование query запросов

```php
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultController;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Illuminate\Database\Eloquent\Model;

class Example extends Model
{
	use ModelTrait;
}

class ExampleRepository extends DefaultRepository
{
	public function example(ExampleDto $dto)
	{
		DB::withQueryCache()->select('select * from users');
		DB::table('users')->withQueryCache()->first();

		Example::withQueryCache()->first();
		Example::query()->withQueryCache()->count();
		Example::query()->withQueryCache()->exists();
	}
}
```

##### ViewCache

Кеширование рендеринга blade шаблонов

```php
use Atlcom\LaravelHelper\Defaults\DefaultController;

class ExampleController extends DefaultController
{
	public function example()
	{
		return $this->withViewCache()->view(view: 'example', data: ['time' => now()], ignoreData: ['time']);
	}
}
```

<hr style="border:1px solid black">

### Примеры расширения классов

##### DefaultCommand

Расширение класса консольных команд

```php
use Atlcom\LaravelHelper\Defaults\DefaultCommand;

class ExampleCommand extends DefaultCommand
{
	public function handle(): int
}
```

##### DefaultController и DefaultDto

Расширение класса контроллера с внедрением [Dto](https://github.com/atlcomgit/dto) (замена Request)

```php
use Atlcom\LaravelHelper\Defaults\DefaultController;
use Atlcom\LaravelHelper\Defaults\DefaultDto;

class ExampleDto extends DefaultDto
{
	public int $id;

	public function rules(): array
	{
		return [
			'id' => ['require', 'numeric'],
		];
	}
}

class ExampleController extends DefaultController
{
	public function example(ExampleDto $dto)
	{
		return $dto->id;
	}
}
```

##### DefaultEvent

Расширение класса событий

```php
use Atlcom\LaravelHelper\Defaults\DefaultEvent;

class ExampleEvent extends DefaultEvent
{
    public function __construct() {}
}
```

##### DefaultException

Расширение класса исключений

```php
use Atlcom\LaravelHelper\Defaults\DefaultException;

class ExampleException extends DefaultException
{
}
```

##### DefaultJob

Расширение класса задач для очередей

```php
use Atlcom\LaravelHelper\Defaults\DefaultJob;

class ExampleJob extends DefaultJob
{
    public function __invoke(): void {}
}
```

##### DefaultListener

Расширение класса слушателя события

```php
use Atlcom\LaravelHelper\Defaults\DefaultListener;

class ExampleListener extends DefaultListener
{
}
```

##### DefaultLogger

Расширение класса логирования

```php
use Atlcom\LaravelHelper\Defaults\DefaultLogger;

class ExampleLogger extends DefaultLogger
{
}
```

##### DefaultModel

Расширение класса модели

```php
use Atlcom\LaravelHelper\Defaults\DefaultModel;

class ExampleModel extends DefaultModel
{
}
```

##### DefaultProfiler

Расширение класса для профилирования методов

```php
use Atlcom\LaravelHelper\Defaults\DefaultProfiler;

class ExampleModel extends DefaultProfiler
{
}
```

##### DefaultRepository

Расширение класса репозитория

```php
use Atlcom\LaravelHelper\Defaults\DefaultRepository;

class ExampleRepository extends DefaultRepository
{
}
```

##### DefaultRequest

Расширение класса запроса

```php
use Atlcom\LaravelHelper\Defaults\DefaultRequest;

class ExampleRequest extends DefaultRequest
{
}
```

##### DefaultResource

Расширение класса ресурса

```php
use Atlcom\LaravelHelper\Defaults\DefaultResource;

class ExampleResource extends DefaultResource
{
}
```

##### DefaultService

Расширение класса сервиса

```php
use Atlcom\LaravelHelper\Defaults\DefaultService;

class ExampleService extends DefaultService
{
}
```

##### DefaultTest

Расширение класса теста

```php
use Atlcom\LaravelHelper\Defaults\DefaultTest;

class ExampleTest extends DefaultTest
{
}
```

<hr style="border:1px solid black">

### События хелпера

```php
ConsoleLogEvent::class // Событие логирования консольных команд
ExceptionEvent::class // Событие логирования исключений
HttpLogEvent::class // Событие логирования http запросов
ModelLogEvent::class // Событие логирования моделей
ProfilerLogEvent::class // Событие логирования профилирования методов класса
RouteLogEvent::class // Событие логирования роутов
QueryCacheEvent::class // Событие кеширования query запросов
QueryLogEvent::class // Событие логирования query запросов
QueueLogEvent::class // Событие логирования очередей
TelegramLogEvent::class // Событие логирования отправки сообщения в телеграм
ViewCacheEvent::class // Событие кеширования рендеринга blade шаблонов
ViewLogEvent::class // Событие логирования рендеринга blade шаблонов
```

<hr style="border:1px solid black">

### Хелперы

##### Список вспомогательных функций

```php
lhConfig() // Возвращает настройки хелпера
isDebug() // Возвращает флаг окружения APP_DEBUG
isDebugData() // Возвращает флаг окружения APP_DEBUG_DATA
isDebugTrace() // Возвращает флаг окружения APP_DEBUG_TRACE
isLocal() // Проверяет на локальное окружение
isTesting() // Проверяет на тестовое окружение
isDev() // Проверяет на dev окружение
isProd() // Проверяет на боевое окружение
isCommand() // Проверяет на запуск приложения из консольной команды
isHttp() // Проверяет на запуск приложения из http запроса
isQueue() // Проверяет на запуск приложения из очереди
queue() // Ставит job в очередь и запускает её
sql() // Возвращает сырой sql запрос c заменой плейсхолдеров
json() // Возвращает json строку
telescope() // Включает/Отключает логи telescope
telegram() // Отправляет сообщение в телеграм
user() // Возвращает модель авторизованного пользователя или null
ip() // Возвращает ip адрес из запроса
uuid() // Возвращает uuid
```
