<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Tests\Unit;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Defaults\DefaultResource;
use Atlcom\LaravelHelper\Enums\ResourceEnum;
use Atlcom\LaravelHelper\Providers\LaravelHelperServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Тесты описаний полей модели ($descriptions) и их выдачи в ресурсах форм
 */
final class ModelDescriptionsTest extends TestCase
{
    /**
     * Подключает провайдер пакета
     *
     * @param mixed $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelHelperServiceProvider::class,
        ];
    }


    /**
     * Модель возвращает свойство $descriptions, а без свойства — пустой массив.
     * @see \Atlcom\LaravelHelper\Traits\ModelTrait::getDescriptions()
     *
     * @return void
     */
    #[Test]
    public function modelReturnsDescriptions(): void
    {
        $this->assertSame(['name' => 'Описание названия'], (new ModelDescriptionsTestModel())->getDescriptions());
        $this->assertSame([], (new ModelDescriptionsTestModelWithout())->getDescriptions());
    }


    /**
     * Ресурсы форм получают descriptions модели; остальные структуры и явный ключ ресурса не меняются.
     * @see \Atlcom\LaravelHelper\Defaults\DefaultResource::resolve()
     *
     * @return void
     */
    #[Test]
    public function formResourcesIncludeDescriptions(): void
    {
        $model = new ModelDescriptionsTestModel();

        foreach ([ResourceEnum::Form, ResourceEnum::FormCreate, ResourceEnum::FormUpdate] as $structure) {
            $data = (new ModelDescriptionsTestResource($model))->setStructure($structure)->resolve();
            $this->assertSame(['name' => 'Описание названия'], $data['descriptions'] ?? null, $structure->value);
            $this->assertSame(['name' => 'string'], $data['types']);
        }

        // Ресурс чтения записи не получает описаний формы
        $data = (new ModelDescriptionsTestResource($model))->setStructure(ResourceEnum::Read)->resolve();
        $this->assertArrayNotHasKey('descriptions', $data);

        // Модель без описаний не добавляет пустой ключ
        $data = (new ModelDescriptionsTestResource(new ModelDescriptionsTestModelWithout()))
            ->setStructure(ResourceEnum::FormCreate)
            ->resolve();
        $this->assertArrayNotHasKey('descriptions', $data);

        // Явно заданный ресурсом ключ имеет приоритет
        $data = (new ModelDescriptionsTestResource($model, ['name' => 'Своё']))
            ->setStructure(ResourceEnum::FormUpdate)
            ->resolve();
        $this->assertSame(['name' => 'Своё'], $data['descriptions']);
    }
}


/**
 * Тестовая модель с описаниями полей
 */
class ModelDescriptionsTestModel extends DefaultModel
{
    /** Подробные описания полей */
    protected $descriptions = [
        'name' => 'Описание названия',
    ];
}


/**
 * Тестовая модель без описаний полей
 */
class ModelDescriptionsTestModelWithout extends DefaultModel
{
}


/**
 * Тестовый ресурс формы с необязательным явным ключом descriptions
 */
class ModelDescriptionsTestResource extends DefaultResource
{
    /**
     * @param mixed $resource Модель ресурса
     * @param array<string, string>|null $ownDescriptions Описания, заданные самим ресурсом
     */
    public function __construct($resource, private ?array $ownDescriptions = null)
    {
        parent::__construct($resource);
    }


    /**
     * Возвращает минимальную структуру формы
     *
     * @param mixed $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'types' => ['name' => 'string'],
            ...($this->ownDescriptions === null ? [] : ['descriptions' => $this->ownDescriptions]),
        ];
    }
}
