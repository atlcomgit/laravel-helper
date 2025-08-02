<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\TelegramBotChatFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @mixin \Eloquent
 */
class TelegramBotChat extends DefaultModel
{
    use SoftDeletes;
    use HasFactory;
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


    /*
     * ATTRIBUTES
     */


    /*
     * MUTATORS
     */


    /*
     * RELATIONS
     */


    /*
     * SCOPES
     */


    /**
     * Фильтр по uuid
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
     * Фильтр по external_chat_id
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
