<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageStatusEnum;
use Atlcom\LaravelHelper\Enums\TelegramBotMessageTypeEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\TelegramBotMessageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель: Сообщение телеграм бота
 * 
 * @see \Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotMessageDto
 * @see ./database/migrations_telegram_bot/2025_08_02_000003_create_helper_telegram_bot_messages_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property TelegramBotMessageTypeEnum $type
 * @property TelegramBotMessageStatusEnum $status
 * @property int $external_message_id
 * @property ?int $external_update_id
 * @property int $telegram_bot_chat_id
 * @property int $telegram_bot_user_id
 * @property ?int $telegram_bot_message_id
 * @property ?string $slug
 * @property string $text
 * @property \Carbon\Carbon $send_at
 * @property ?\Carbon\Carbon $edit_at
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 * 
 * @property-read TelegramBotChat $telegramBotChat
 * @method Relation|TelegramBotChat telegramBotChat()
 * @property-read TelegramBotUser $telegramBotUser
 * @method Relation|TelegramBotUser telegramBotUser()
 * @property-read TelegramBotMessage $telegramBotMessage
 * @method Relation|TelegramBotMessage telegramBotMessage()
 * @property-read TelegramBotMessage $previousMessage
 * @method Relation|TelegramBotMessage previousMessage()
 * 
 * @method static|Builder ofUuid(string $uuid)
 * @method static|Builder OfType(TelegramBotMessageTypeEnum $type)
 * @method static|Builder ofStatus(TelegramBotMessageStatusEnum $status)
 * @method static|Builder ofExternalMessageId(int $externalMessageId)
 * @method static|Builder ofSlug(?string $slug)
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
        'type' => TelegramBotMessageTypeEnum::class,
        'status' => TelegramBotMessageStatusEnum::class,
        'external_message_id' => 'integer',
        'external_update_id' => 'integer',
        'telegram_bot_chat_id' => 'integer',
        'telegram_bot_user_id' => 'integer',
        'telegram_bot_message_id' => 'integer',
        'slug' => 'string',
        'text' => 'string',
        'send_at' => 'datetime',
        'edit_at' => 'datetime',
        'info' => 'array',
    ];


    public function __construct()
    {
        parent::__construct();

        $this->setConnection(Lh::getConnection(ConfigEnum::TelegramBot));
        $this->setTable(Lh::getTable(ConfigEnum::TelegramBot, 'message'));
    }


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


    /** ATTRIBUTES */


    /** MUTATORS */


    /** RELATIONS */


    /**
     * Связь с чатом бота телеграм
     *
     * @return Relation
     */
    public function telegramBotChat(): Relation
    {
        return $this->belongsTo(TelegramBotChat::class, 'telegram_bot_chat_id');
    }


    /**
     * Связь с пользователем бота телеграм
     *
     * @return Relation
     */
    public function telegramBotUser(): Relation
    {
        return $this->belongsTo(TelegramBotUser::class, 'telegram_bot_user_id');
    }


    /**
     * Связь с цитируемым сообщением бота телеграм
     *
     * @return Relation
     */
    public function telegramBotMessage(): Relation
    {
        return $this->belongsTo(TelegramBotMessage::class, 'telegram_bot_message_id');
    }


    /**
     * Предыдущее сообщение
     *
     * @return Relation
     */
    public function previousMessage(): Relation
    {
        return $this->telegramBotMessage();
    }


    /** SCOPES */


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
     * Фильтр по типу сообщения
     *
     * @param Builder $query
     * @param TelegramBotMessageTypeEnum $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, TelegramBotMessageTypeEnum $type): Builder
    {
        return $query->where('type', $type);
    }


    /**
     * Фильтр по статусу сообщения
     *
     * @param Builder $query
     * @param TelegramBotMessageStatusEnum $status
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, TelegramBotMessageStatusEnum $status): Builder
    {
        return $query->where('status', $status);
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


    /**
     * Фильтр по slug
     *
     * @param Builder $query
     * @param ?string $slug
     * @return Builder
     */
    public function scopeOfSlug(Builder $query, ?string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
