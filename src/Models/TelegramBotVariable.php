<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\Hlp;
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
 * @property string $group
 * @property string $name
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
 * @method self|Builder ofUuid(string $uuid)
 * @method self|Builder ofTelegramBotChatId(int $telegramBotChatId)
 * @method self|Builder ofType(TelegramBotVariableTypeEnum $type)
 * @method self|Builder ofGroup(string $group)
 * @method self|Builder ofName(string $name)
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
        'group' => 'string',
        'name' => 'string',
        'value' => 'string',
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
    public function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => TelegramBotVariableTypeEnum::getValue($this->type, $value),
            set: static fn ($value, $attributes) => is_string($value) ? $value : Hlp::castToJson($value),
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
     * Фильтр: по uuid
     *
     * @param Builder $query
     * @param int $telegramBotChatId
     * @return Builder
     */
    public function scopeOfTelegramBotChatId(Builder $query, int $telegramBotChatId): Builder
    {
        return $query->where('telegram_bot_chat_id', $telegramBotChatId);
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
     * Фильтр: по группе переменной
     *
     * @param Builder $query
     * @param string $group
     * @return Builder
     */
    public function scopeOfGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }


    /**
     * Фильтр: по имени переменной
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
