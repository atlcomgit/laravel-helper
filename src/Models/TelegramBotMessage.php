<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\TelegramBotMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель: Сообщение телеграм бота
 * 
 * @see \Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto
 * @see ./database/migrations_telegram_bot/2025_08_02_000003_create_helper_telegram_bot_messages_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property int $external_message_id
 * @property int $external_update_id
 * @property int $telegram_bot_chat_id
 * @property int $telegram_bot_user_id
 * @property ?int $telegram_bot_message_id
 * @property string $text
 * @property \Carbon\Carbon $send_at
 * @property ?\Carbon\Carbon $edit_at
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 * 
 * @mixin \Eloquent
 */
class TelegramBotMessage extends DefaultModel
{
    use SoftDeletes;
    use HasFactory;
    use DynamicTableModelTrait;


    public const COMMENT = 'Сообщение телеграм бота';

    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'external_message_id' => 'integer',
        'external_update_id' => 'integer',
        'telegram_bot_chat_id' => 'integer',
        'telegram_bot_user_id' => 'integer',
        'telegram_bot_message_id' => 'integer',
        'text' => 'string',
        'send_at' => 'datetime',
        'edit_at' => 'datetime',
        'info' => 'array',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|TelegramBotMessageFactory
     */
    protected static function newFactory(): TelegramBotMessageFactory
    {
        return TelegramBotMessageFactory::new();
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
     * Фильтр по external_message_id
     *
     * @param Builder $query
     * @param int $externalMessageId
     * @return Builder
     */
    public function scopeOfExternalMessageId(Builder $query, int $externalMessageId): Builder
    {
        return $query->where('external_message_id', $externalMessageId);
    }
}
