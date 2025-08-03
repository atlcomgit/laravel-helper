<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Database\Factories\TelegramBotUserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель: Пользователь телеграм бота
 * 
 * @see \Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotUserDto
 * @see ./database/migrations_telegram_bot/2025_08_02_000002_create_helper_telegram_bot_users_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property int $external_user_id
 * @property string $first_name
 * @property string $user_name
 * @property ?string $phone
 * @property string $language
 * @property bool $is_ban
 * @property ?bool $is_bot
 * @property ?array $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 * @property ?\Carbon\Carbon $deleted_at
 * 
 * @property-read \Illuminate\Database\Eloquent\Collection<TelegramBotMessage> $telegramBotMessages
 * @method Relation|\Illuminate\Database\Eloquent\Collection<TelegramBotMessage> telegramBotMessages()
 * 
 * @mixin \Eloquent
 */
class TelegramBotUser extends DefaultModel
{
    use SoftDeletes;
    use HasFactory;
    use DynamicTableModelTrait;


    public const COMMENT = 'Пользователь телеграм бота';

    protected ?bool $withModelLog = false;
    protected $guarded = ['id'];
    protected $casts = [
        'uuid' => 'string',
        'external_user_id' => 'integer',
        'first_name' => 'string',
        'user_name' => 'string',
        'phone' => 'string',
        'language' => 'string',
        'is_ban' => 'boolean',
        'is_bot' => 'boolean',
        'info' => 'array',
    ];


    public function __construct()
    {
        parent::__construct();

        $this->setConnection(Lh::getConnection(ConfigEnum::TelegramBot));
        $this->setTable(Lh::getTable(ConfigEnum::TelegramBot, 'user'));
    }


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|TelegramBotUserFactory
     */
    protected static function newFactory(): TelegramBotUserFactory
    {
        return TelegramBotUserFactory::new();
    }


    /** ATTRIBUTES */


    /** MUTATORS */


    /** RELATIONS */


    /**
     * Связь с сообщениями бота телеграм
     *
     * @return Relation
     */
    public function telegramBotMessages(): Relation
    {
        return $this->hasMany(TelegramBotMessage::class, 'telegram_bot_user_id');
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
     * Фильтр по external_user_id
     *
     * @param Builder $query
     * @param int $externalUserId
     * @return Builder
     */
    public function scopeOfExternalUserId(Builder $query, int $externalUserId): Builder
    {
        return $query->where('external_user_id', $externalUserId);
    }
}
