<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Database\Factories\TelegramBotVariableFactory;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotVariableTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Resources\Json\JsonResource;
use UnitEnum;

/**
 * Модель: Сообщение телеграм бота
 * 
 * @see \Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotVariableDto
 * @see ./database/migrations_telegram_bot/2025_09_04_000001_create_helper_telegram_bot_variables_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property int $telegram_bot_chat_id
 * @property ?int $telegram_bot_message_id
 * @property TelegramBotVariableTypeEnum $type
 * @property ?string $name
 * @property mixed $value
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 * 
 * @property-read TelegramBotChat $telegramBotChat
 * @method Relation|TelegramBotChat telegramBotChat()
 * @property-read TelegramBotMessage $telegramBotMessage
 * @method Relation|TelegramBotMessage telegramBotMessage()
 * 
 * @method static|Builder ofUuid(string $uuid)
 * @method static|Builder OfType(TelegramBotVariableTypeEnum $type)
 * @method static|Builder ofName(string $name)
 * 
 * @mixin \Eloquent
 */
class TelegramBotVariable extends DefaultModel
{
    use SoftDeletes;
    use DynamicTableModelTrait;


    public const COMMENT = 'Переменная чата телеграм бота';

    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'type' => TelegramBotVariableTypeEnum::class,
        'telegram_bot_chat_id' => 'integer',
        'telegram_bot_message_id' => 'integer',
        'name' => 'string',
        'value' => 'json',
    ];


    /** CONFIG */


    public function __construct()
    {
        parent::__construct();

        $this->setConnection(Lh::getConnection(ConfigEnum::TelegramBot));
        $this->setTable(Lh::getTable(ConfigEnum::TelegramBot, 'variable'));
    }


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|TelegramBotVariableFactory
     */
    protected static function newFactory(): TelegramBotVariableFactory
    {
        return TelegramBotVariableFactory::new();
    }


    /**
     * Общий ресурс модели
     *
     * @param UnitEnum|string|null $structure
     * @return JsonResource
     */
    public function toResource(UnitEnum|string|null $structure = null): JsonResource
    {
        // return TelegramBotVariableResource::make($this)->setStructure($structure);
        return parent::toResource($structure);
    }


    /**
     * Возвращает название таблицы модели
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return Lh::getTable(ConfigEnum::TelegramBot, 'variable');
    }


    /** ATTRIBUTES */


    /**
     * Аттрибут: Полное имя клиента
     *
     * @return Attribute
     */
    public function attrValue(): Attribute
    {
        return Attribute::make(
            get: static fn ($value, $attributes) => json_decode($value),
            set: static fn ($value, $attributes) => json_encode($value),
        );
    }


    /** MUTATORS */


    /** RELATIONS */


    /**
     * Отношение: Связь с чатом бота телеграм
     *
     * @return Relation
     */
    public function telegramBotChat(): Relation
    {
        return $this->belongsTo(TelegramBotChat::class, 'telegram_bot_chat_id');
    }


    /**
     * Отношение: Связь с цитируемым сообщением бота телеграм
     *
     * @return Relation
     */
    public function telegramBotMessage(): Relation
    {
        return $this->belongsTo(TelegramBotMessage::class, 'telegram_bot_message_id');
    }


    /** SCOPES */


    /**
     * Фильтр: по uuid
     *
     * @param Builder $query
     * @param string $uuid
     * @return Builder
     */
    public function scopeOfUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }


    /**
     * Фильтр: по типу переменной
     *
     * @param Builder $query
     * @param TelegramBotVariableTypeEnum $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, TelegramBotVariableTypeEnum $type): Builder
    {
        return $query->where('type', $type);
    }


    /**
     * Фильтр: по slug
     *
     * @param Builder $query
     * @param string $name
     * @return Builder
     */
    public function scopeOfName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }
}
