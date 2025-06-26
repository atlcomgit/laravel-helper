# Laravel Helper

Класс помощник для Laravel добавляет функционал во фреймворк:
- Логирование консольных команд
- Логирование входящих/исходящих http запросов
- Логирование изменения моделей, в том числе массовое изменение/удаление
- Логирование query запросов через Eloquent/Database/Connection
- Логирование запуска и выполнения очередей
- Логирование зарегистрированных роутов и их использование
- Логирование рендеринга blade шаблонов
- Кеширование query запросов через Eloquent/Database/Connection
- Кеширование рендеринга blade шаблонов
- Обработка всех исключений с отправкой логов в телеграмм
- Внедрение Dto вместо Request в контроллеры, которое поддерживает: правила валидации, мутацию, маппинг, хуки при создании/изменении свойств

<hr style="border:1px solid black">

### Подключение пакета

##### 1. Установка пакета

```bash
composer require atlcom/laravel-helper
```

##### 2. Публикация настроек

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
	protected bool $telegramLog = true;
	protected mixed $telegramComment = null;
	protected bool $withConsoleLog = true;

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

class ExampleService
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
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Illuminate\Database\Eloquent\Model;

class Example extends Model
{
	use ModelLogTrait;

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

##### QueryLog

Логирование query запросов в таблице helper_query_logs

```php
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Defaults\DefaultController;
use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Illuminate\Database\Eloquent\Model;

class Example extends Model
{
	use ModelTrait;
}

class ExampleRepository
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

##### QueueLog

Логирование зарегистрированных роутов в таблице helper_route_logs

```php
use Illuminate\Support\Facades\Route;

Route::get('/example', [ExampleController::class, 'example']);
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
use Atlcom\LaravelHelper\Traits\ModelLogTrait;
use Illuminate\Database\Eloquent\Model;

class Example extends Model
{
	use ModelTrait;
}

class ExampleRepository
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

##### DefaultCommand

Расширение класса консольных команд

```php
use Atlcom\LaravelHelper\Defaults\DefaultCommand;

class ExampleCommand extends DefaultCommand
{
	public function handle(): int
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

##### DefaultModel

Расширение класса модели

```php
use Atlcom\LaravelHelper\Defaults\DefaultModel;

class Example extends DefaultModel
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

##### DefaultException

Расширение класса исключений

```php
use Atlcom\LaravelHelper\Defaults\DefaultException;

class ExampleException extends DefaultException
{
}
```

<hr style="border:1px solid black">

### Хелперы

##### Список вспомогательных функций

```php
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
telegram() // Отправляет сообщение в телеграм
user() // Возвращает модель авторизованного пользователя или null
ip() // Возвращает ip адрес из запроса
uuid() // Возвращает uuid
```
