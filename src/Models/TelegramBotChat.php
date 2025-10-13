<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Atlcom\LaravelHelper\Database\Factories\TelegramBotChatFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Resources\Json\JsonResource;
use UnitEnum;

/**
 * Модель: Чат телеграм бота
 * 
 * @see \Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotChatDto
 * @see ./database/migrations_telegram_bot/2025_08_02_000001_create_helper_telegram_bot_chats_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property int $external_chat_id
 * @property string $name
 * @property string $chat_name
 * @property string $type
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<TelegramBotMessage> $telegramBotMessages
 * @method Relation|\Illuminate\Database\Eloquent\Collection<TelegramBotMessage> telegramBotMessages()
 * @property-read \Illuminate\Database\Eloquent\Collection<TelegramBotVariable> $telegramBotVariables
 * @method Relation|\Illuminate\Database\Eloquent\Collection<TelegramBotVariable> telegramBotVariables()
 * 
 * @method self|Builder ofUuid(string $uuid)
 * @method self|Builder ofExternalChatId(int $externalChatId)
 * 
 * @mixin \Eloquent
 */
class TelegramBotChat extends DefaultModel
{
    use SoftDeletes;
    use DynamicTableModelTrait;


    public const COMMENT = 'Чат телеграм бота';

    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'external_chat_id' => 'integer',
        'name' => 'string',
        'chat_name' => 'string',
        'type' => 'string',
        'info' => 'array',
    ];
    protected $fields = [
        'id' => 'ID чата телеграм бота',
        'uuid' => 'Uuid чата телеграм бота',
        'external_chat_id' => 'Внешний Id чата телеграм бота',
        'name' => 'Имя чата телеграм бота',
        'chat_name' => 'Логин чата телеграм бота',
        'type' => 'Тип чата телеграм бота',
        'info' => 'Информация о чате телеграм бота',
        'created_at' => 'Добавлено',
        'updated_at' => 'Обновлено',
        'deleted_at' => 'Удалено',
    ];


    /** CONFIG */


    public function __construct()
    {
        parent::__construct();

        $this->setConnection(Lh::getConnection(ConfigEnum::TelegramBot));
        $this->setTable(Lh::getTable(ConfigEnum::TelegramBot, 'chat'));
    }


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|TelegramBotChatFactory
     */
    protected static function newFactory(): TelegramBotChatFactory
    {
        return TelegramBotChatFactory::new();
    }


    /**
     * Общий ресурс модели
     *
     * @param UnitEnum|string|null $structure
     * @return JsonResource
     */
    public function toResource(UnitEnum|string|null $structure = null): JsonResource
    {
        // return TelegramBotChatResource::make($this)->setStructure($structure);
        return parent::toResource($structure);
    }


    /**
     * Возвращает название таблицы модели
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return Lh::getTable(ConfigEnum::TelegramBot, 'chat');
    }


    /** ATTRIBUTES */


    /** MUTATORS */


    /** RELATIONS */


    /**
     * Отношение: Связь с сообщениями бота телеграм
     *
     * @return Relation
     */
    public function telegramBotMessages(): Relation
    {
        return $this->hasMany(TelegramBotMessage::class, 'telegram_bot_chat_id');
    }


    /**
     * Отношение: Связь с переменными чата бота телеграм
     *
     * @return Relation
     */
    public function telegramBotVariables(): Relation
    {
        return $this->hasMany(TelegramBotVariable::class, 'telegram_bot_chat_id');
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
     * Фильтр: по external_chat_id
     *
     * @param Builder $query
     * @param int $externalChatId
     * @return Builder
     */
    public function scopeOfExternalChatId(Builder $query, int $externalChatId): Builder
    {
        return $query->where('external_chat_id', $externalChatId);
    }
}
