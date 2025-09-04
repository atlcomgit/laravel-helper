<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services;

use Atlcom\LaravelHelper\Defaults\DefaultDto;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Throwable;

/**
 * Сервис генерации OpenAPI (swagger) по роутам, контроллерам, DTO и ресурсам
 */
class SwaggerService
{
    /**
     * Генерирует массив спецификации OpenAPI
     *
     * @param array $cfg
     * @return array
     */
    public function generate(array $cfg): array
    {
        // Подключаем роуты
        foreach ($cfg['scan']['routes'] as $routeFile) {
            if ($routeFile && file_exists($routeFile)) {
                require_once $routeFile;
            }
        }

        $paths = [];
        $schemas = [];

        // Предзагружаем все снапшоты разом (уменьшаем количество IO и исключаем расхождения)
        [$preloadedSnapshots, $schemas] = $this->preloadSnapshotSchemas($schemas);

        $controllersBasePath = realpath($cfg['scan']['controllers_path']);

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (!str_contains($action, '@')) {
                continue; // пропускаем
            }

            [$controllerClass, $methodName] = explode('@', $action);

            try {
                $rc = new ReflectionClass($controllerClass);
            } catch (Throwable) {
                continue;
            }

            $controllerFile = realpath($rc->getFileName());
            if ($controllersBasePath && $controllerFile && !str_starts_with($controllerFile, $controllersBasePath)) {
                continue; // вне папки
            }

            if (!$rc->hasMethod($methodName)) {
                continue;
            }
            $rm = $rc->getMethod($methodName);

            $httpMethods = array_values(array_filter($route->methods(), static fn ($m) => !in_array($m, ['HEAD', 'OPTIONS'], true)));
            if (!$httpMethods) {
                continue;
            }

            $originalUri = $route->uri();
            // Фильтруем только API эндпоинты с префиксом /api
            if (!str_starts_with($originalUri, 'api/')) {
                continue;
            }
            $uri = '/' . ltrim($originalUri, '/');
            $doc = $this->parseDocComment($rm, $methodName);
            $tag = $this->tagFromController($controllerClass);

            foreach ($httpMethods as $httpMethod) {
                $lower = strtolower($httpMethod);
                $paths[$uri][$lower] = [
                    'summary' => $doc['summary'],
                    'description' => $doc['description'],
                    'tags' => [$tag],
                    'parameters' => $this->buildPathParams($uri),
                ];

                // DTO (тело/ query)
                $dtoSchemas = $this->extractDtoSchemas($rm);
                if ($dtoSchemas['requestDto']) {
                    [$dtoClass, $dtoSchema] = $dtoSchemas['requestDto'];
                    $schemaName = $this->schemaNameFromClass($dtoClass);
                    $schemas[$schemaName] = $dtoSchema;

                    if (in_array($lower, ['post', 'put', 'patch'], true)) {
                        $paths[$uri][$lower]['requestBody'] = [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => "#/components/schemas/{$schemaName}"],
                                ],
                            ],
                        ];
                    } else {
                        $paths[$uri][$lower]['parameters'] = array_merge(
                            $paths[$uri][$lower]['parameters'],
                            $this->dtoQueryParams($dtoSchema, $uri),
                        );
                    }
                }

                // Схема успешного ответа: приоритет снапшот (создаётся тестами), иначе эвристика по ресурсам
                $snapshotKey = basename(str_replace('\\', '/', $controllerClass)) . '@' . $methodName;
                if (isset($preloadedSnapshots[$snapshotKey])) {
                    $successSchema = $preloadedSnapshots[$snapshotKey];
                } else {
                    [$successSchema, $schemas] = $this->buildSuccessSchemaFromMethod($controllerClass, $methodName, $schemas);
                }

                // Ошибочная схема (ExceptionDto)
                [$errorSchemaRef, $schemas] = $this->ensureExceptionSchema($schemas);

                $successCode = ($lower === 'post' && str_ends_with($methodName, 'create')) ? '201' : '200';
                $paths[$uri][$lower]['responses'] = [
                    $successCode => [
                        'description' => 'Успешный ответ',
                        'content' => [
                            'application/json' => [
                                'schema' => $successSchema,
                            ],
                        ],
                    ],
                    '400' => [
                        'description' => 'Ошибка запроса',
                        'content' => [
                            'application/json' => [
                                'schema' => $errorSchemaRef,
                            ],
                        ],
                    ],
                    '401' => ['description' => 'Не авторизован'],
                ];
            }
        }

        ksort($paths);
        ksort($schemas);

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $cfg['title'],
                'version' => $cfg['version'],
                'description' => $cfg['description'],
            ],
            'servers' => $cfg['servers'] ?? [],
            'paths' => $paths,
            'components' => [
                'schemas' => $schemas,
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
            'security' => [['BearerAuth' => []]],
        ];
    }


    /** Парсит phpdoc */
    private function parseDocComment(ReflectionMethod $rm, string $fallback): array
    {
        $doc = $rm->getDocComment() ?: '';
        $doc = preg_replace('/^\/\*\*|\*\/$/', '', $doc ?? '');
        $lines = [];
        foreach (preg_split('/\r?\n/', (string)$doc) as $line) {
            $line = trim(ltrim($line, '* \t'));
            if ($line === '' && $lines && end($lines) === '') {
                continue;
            }
            $lines[] = $line;
        }
        $lines = array_values(array_filter($lines, static fn ($v) => !str_starts_with($v, '@')));
        $summary = $lines[0] ?? '';
        $description = trim(implode("\n", array_slice($lines, 1)));
        $summary = $summary ?: $fallback;

        return compact('summary', 'description');
    }


    /** Имя тега = имя класса контроллера */
    private function tagFromController(string $controllerClass): string
    {
        return basename(str_replace('\\', '/', $controllerClass));
    }


    /** Извлекаем первый DTO из параметров */
    private function extractDtoSchemas(ReflectionMethod $rm): array
    {
        $res = ['requestDto' => null];
        foreach ($rm->getParameters() as $param) {
            $type = $param->getType();
            if (!$type) {
                continue;
            }
            $names = [];
            if ($type instanceof ReflectionUnionType) {
                foreach ($type->getTypes() as $t) {
                    if ($t instanceof ReflectionNamedType) {
                        $names[] = $t->getName();
                    }
                }
            } elseif ($type instanceof ReflectionNamedType) {
                $names[] = $type->getName();
            }
            foreach ($names as $n) {
                if (!class_exists($n)) {
                    continue;
                }
                if (is_subclass_of($n, DefaultDto::class)) {
                    $schema = $this->schemaFromDtoClass($n);
                    $res['requestDto'] = [$n, $schema];
                    break 2;
                }
            }
        }
        return $res;
    }


    /** Схема из DTO — используя getPropertiesWithAllTypes */
    private function schemaFromDtoClass(string $dtoClass): array
    {
        /** @var class-string<DefaultDto> $dtoClass */
        $typesMap = $dtoClass::getPropertiesWithAllTypes();
        $properties = [];
        $required = [];
        foreach ($typesMap as $prop => $types) {
            $nullable = in_array('null', $types, true);
            $oa = $this->convertTypesToOpenApi($types);
            if ($nullable) {
                $oa['nullable'] = true;
            } else {
                $required[] = $prop;
            }
            $properties[$prop] = $oa;
        }
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }


    /** Конверсия массива типов в openapi */
    private function convertTypesToOpenApi(array $types): array
    {
        $types = array_values(array_filter($types, static fn ($t) => $t !== 'null'));
        if (!$types) {
            return ['type' => 'string'];
        }
        if (count($types) === 1) {
            return $this->mapPhpTypeToOpenApi($types[0]);
        }
        // union -> anyOf
        return [
            'anyOf' => array_map(fn ($t) => $this->mapPhpTypeToOpenApi($t), $types),
        ];
    }


    /** Маппинг */
    private function mapPhpTypeToOpenApi(string $phpType): array
    {
        if (enum_exists($phpType)) {
            $cases = array_map(static fn ($c) => $c->value, $phpType::cases());
            return [
                'type' => is_string($cases[0] ?? '') ? 'string' : 'integer',
                'enum' => $cases,
            ];
        }
        return match ($phpType) {
            'int', 'integer' => ['type' => 'integer', 'format' => 'int32'],
            'float', 'double' => ['type' => 'number', 'format' => 'double'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'array' => ['type' => 'array', 'items' => ['type' => 'string']],
            \Carbon\Carbon::class, 'DateTime', 'DateTimeInterface' => ['type' => 'string', 'format' => 'date-time'],
            default => ['type' => 'string'],
        };
    }


    /** path params */
    private function buildPathParams(string $uri): array
    {
        $params = [];
        if (preg_match_all('/\{([^}]+)}/', $uri, $m)) {
            foreach ($m[1] as $n) {
                $params[] = [
                    'name' => $n,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string'],
                ];
            }
        }
        return $params;
    }


    /** query params из dto */
    private function dtoQueryParams(array $schema, string $uri): array
    {
        $params = [];
        $pathParams = [];
        if (preg_match_all('/\{([^}]+)}/', $uri, $m)) {
            $pathParams = $m[1];
        }
        foreach (Arr::get($schema, 'properties', []) as $k => $p) {
            if (in_array($k, $pathParams, true)) {
                continue;
            }
            $params[] = [
                'name' => $k,
                'in' => 'query',
                'required' => in_array($k, Arr::get($schema, 'required', []), true),
                'schema' => Arr::only($p, ['type', 'format', 'enum', 'anyOf']),
            ];
        }
        return $params;
    }


    /**
     * Создаёт схему успешного ответа на основе ресурса модели (Model::toResource())
     * Пытаемся вызвать метод контроллера через рефлексию и перехватить модель/ресурс
     * Упрощено: определяем возвращаемый ResourceEnum через текст метода (по ResourceEnum::*)
     */
    private function buildSuccessSchemaFromMethod(string $controllerClass, string $method, array $schemas): array
    {
        try {
            $ref = new ReflectionClass($controllerClass);
            $source = file_get_contents($ref->getFileName());
            $rm = $ref->getMethod($method);
            $lines = file($ref->getFileName());
            $methodBody = implode("\n", array_slice($lines, $rm->getStartLine() - 1, $rm->getEndLine() - $rm->getStartLine() + 1));

            // Определяем структуру
            $structure = 'Default';
            if (preg_match('/ResourceEnum::(\w+)/', $methodBody, $m)) {
                $structure = $m[1];
            } elseif (preg_match('/UserResourceEnum::(\w+)/', $methodBody, $m)) {
                $structure = $m[1];
            }

            // Поиск всех моделей use ... Models\X
            $models = [];
            if (preg_match_all('/^use\s+(App\\\\[^;]+\\\\Models\\\\[A-Za-z0-9_\\\\]+);/m', $source, $mu)) {
                $models = $mu[1];
            }

            $structureToGetter = [
                'Index' => 'getIndex',
                'Default' => 'getDefault',
                'Form' => 'getForm',
                'Create' => 'getCreate',
                'Read' => 'getRead',
                'Update' => 'getUpdate',
                'Delete' => 'getDelete',
                'Login' => 'getLogin',
                'Logout' => 'getLogout',
                'Profile' => 'getProfile',
            ];
            $getter = $structureToGetter[$structure] ?? ("get{$structure}");

            foreach ($models as $modelClass) {
                if (!class_exists($modelClass)) {
                    continue;
                }
                $resourceClass = $this->resolveResourceClass($modelClass);
                if (!$resourceClass || !class_exists($resourceClass)) {
                    continue;
                }
                try {
                    $model = new $modelClass();
                    $resource = new $resourceClass($model);
                    if (method_exists($resource, $getter)) {
                        $data = $resource->$getter($model);
                        if (is_array($data)) {
                            $schemaName = $this->schemaNameFromClass($controllerClass) . 'Response' . $structure;
                            $schemas[$schemaName] = $schemas[$schemaName] ?? $this->schemaFromArray($data);
                            return [['$ref' => "#/components/schemas/{$schemaName}"], $schemas];
                        }
                    }
                } catch (Throwable) { /* ignore */
                }
            }
        } catch (Throwable) {
            // ignore ошибки рефлексии
        }

        return [['type' => 'object', 'description' => 'Не удалось определить структуру ресурса'], $schemas];
    }


    /** Простейшая схема из массива */
    private function schemaFromArray(array $array): array
    {
        $props = [];
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $props[$k] = [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ];
            } elseif (is_int($v)) {
                $props[$k] = ['type' => 'integer'];
            } elseif (is_float($v)) {
                $props[$k] = ['type' => 'number'];
            } elseif (is_bool($v)) {
                $props[$k] = ['type' => 'boolean'];
            } elseif (is_null($v)) {
                $props[$k] = ['type' => 'string', 'nullable' => true];
            } else {
                $props[$k] = ['type' => 'string'];
            }
        }
        return [
            'type' => 'object',
            'properties' => $props,
        ];
    }


    /** Exception schema ensure */
    private function ensureExceptionSchema(array $schemas): array
    {
        $candidates = [
            'Atlcom\\LaravelHelper\\Dto\\ExceptionDto',
            'Atlcom\\LaravelHelper\\Dto\\Exceptions\\ExceptionDto',
            'Atlcom\\LaravelHelper\\Dto\\Http\\ExceptionDto',
        ];
        $dtoClass = null;
        foreach ($candidates as $cls) {
            if (class_exists($cls)) {
                $dtoClass = $cls;
                break;
            }
        }

        $name = 'ExceptionDto';
        if ($dtoClass && is_subclass_of($dtoClass, DefaultDto::class)) {
            $name = basename(str_replace('\\', '/', $dtoClass));
            if (!isset($schemas[$name])) {
                $schemas[$name] = $this->schemaFromDtoClass($dtoClass);
            }
        } else {
            if (!isset($schemas[$name])) {
                $schemas[$name] = [
                    'type' => 'object',
                    'properties' => [
                        'message' => ['type' => 'string'],
                        'code' => ['type' => 'integer'],
                        'errors' => ['type' => 'object'],
                    ],
                ];
            }
        }

        return [['$ref' => "#/components/schemas/{$name}"], $schemas];
    }


    private function schemaNameFromClass(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }


    /**
     * Определяет класс ресурса из имени класса модели
     */
    private function resolveResourceClass(string $modelClass): ?string
    {
        $parts = explode('\\\
', $modelClass);
        $parts = explode('\\', $modelClass);
        $name = array_pop($parts);
        // Заменяем сегмент Models на Resources
        foreach ($parts as $i => $segment) {
            if ($segment === 'Models') {
                $parts[$i] = 'Resources';
                break;
            }
        }
        $parts[] = "{$name}Resource";
        return implode('\\', $parts);
    }


    private function loadSnapshotSchema(string $controllerClass, string $methodName, array $schemas): ?array
    {
        $dir = storage_path('app/swagger/snapshots');
        $file = $dir . '/' . $this->schemaNameFromClass($controllerClass) . '@' . $methodName . '.json';
        if (!is_file($file)) {
            return null;
        }
        $json = json_decode((string)file_get_contents($file), true);
        if (!is_array($json)) {
            return null;
        }
        $schemaName = $this->schemaNameFromClass($controllerClass) . 'Response' . ucfirst($methodName);
        if (!isset($schemas[$schemaName])) {
            $schemas[$schemaName] = $this->schemaFromArrayRecursive($json);
            $schemas[$schemaName]['x-snapshot'] = true; // маркер, что схема из снапшота
        }
        return [['$ref' => "#/components/schemas/{$schemaName}"], $schemas];
    }


    /**
     * Предзагрузка всех снапшотов (контроллер@метод => $ref)
     *
     * @param array $schemas
     * @return array{0:array<string,array>,1:array}
     */
    private function preloadSnapshotSchemas(array $schemas): array
    {
        $dir = storage_path('app/swagger/snapshots');
        if (!is_dir($dir)) {
            return [[], $schemas];
        }
        $map = [];
        foreach (glob("{$dir}/*.json") as $file) {
            $base = basename($file, '.json'); // Controller@method
            if (!str_contains($base, '@')) {
                continue;
            }
            [$controllerShort, $method] = explode('@', $base, 2);
            $json = json_decode((string)file_get_contents($file), true);
            if (!is_array($json)) {
                continue;
            }
            $schemaName = $controllerShort . 'Response' . ucfirst($method);
            if (!isset($schemas[$schemaName])) {
                $schemas[$schemaName] = $this->schemaFromArrayRecursive($json);
                $schemas[$schemaName]['x-snapshot'] = true;
            }
            $map[$base] = ['$ref' => "#/components/schemas/{$schemaName}"];
        }
        return [$map, $schemas];
    }


    private function schemaFromArrayRecursive(mixed $value): array
    {
        if (is_array($value)) {
            $isAssoc = $this->isAssoc($value);
            if (!$isAssoc) {
                $first = reset($value);
                return [
                    'type' => 'array',
                    'items' => $this->schemaFromArrayRecursive($first),
                ];
            }
            $props = [];
            foreach ($value as $k => $v) {
                $props[$k] = $this->schemaFromArrayRecursive($v);
            }
            return [
                'type' => 'object',
                'properties' => $props,
            ];
        }
        return match (true) {
            is_int($value) => ['type' => 'integer'],
            is_float($value) => ['type' => 'number'],
            is_bool($value) => ['type' => 'boolean'],
            is_null($value) => ['type' => 'string', 'nullable' => true],
            default => ['type' => 'string'],
        };
    }


    private function isAssoc(array $arr): bool
    {
        $keys = array_keys($arr);
        return array_keys($keys) !== $keys;
    }
}
