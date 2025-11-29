<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Database\Factories\MailLogFactory;
use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Traits\DynamicTableModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User;

/**
 * Модель лога отправки письма
 *
 * @see \Atlcom\LaravelHelper\Dto\MailLogDto
 * @see ./database/migrations/0000_01_01_000009_create_helper_mail_logs_table.php
 *
 * @property int $id
 * @property string $uuid
 * @property int|string|null $user_id
 * @property MailLogStatusEnum $status
 * @property string|null $from
 * @property array|null $to
 * @property array|null $cc
 * @property array|null $bcc
 * @property array|null $reply_to
 * @property string|null $subject
 * @property string|null $body
 * @property array|null $attachments
 * @property string|null $error_message
 * @property float|null $duration
 * @property int|null $memory
 * @property int|null $size
 * @property array|null $info
 * @property ?\Carbon\Carbon $created_at
 * @property ?\Carbon\Carbon $updated_at
 *
 * @property-read ?User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|MailLog query()
 * @method static \Illuminate\Database\Eloquent\Factories\Factory|MailLogFactory factory($count = null, $state = [])
 * @method static|Builder|static ofUuid($uuid)
 * @method static|Builder|static ofStatus($status)
 * @mixin \Eloquent
 */
class MailLog extends DefaultModel
{
    use DynamicTableModelTrait;


    public const COMMENT = 'Логи отправки писем';

    protected ?bool $withModelLog = false;
    protected       $primaryKey   = 'id';
    protected       $guarded      = ['id'];
    protected       $casts        = [
        'uuid'        => 'string',
        'user_id'     => 'integer',
        'status'      => MailLogStatusEnum::class,
        'from'        => 'string',
        'to'          => 'array',
        'cc'          => 'array',
        'bcc'         => 'array',
        'reply_to'    => 'array',
        'subject'     => 'string',
        'body'        => 'string',
        'attachments' => 'array',
        'message'     => 'string',
        'duration'    => 'float',
        'memory'      => 'integer',
        'size'        => 'integer',
        'info'        => 'array',
    ];
    protected       $fields       = [
        'id'          => 'ID лога',
        'uuid'        => 'Uuid письма',
        'user_id'     => 'Id пользователя',
        'status'      => 'Статус отправки',
        'from'        => 'Отправитель',
        'to'          => 'Получатели',
        'cc'          => 'Копии',
        'bcc'         => 'Скрытые копии',
        'reply_to'    => 'Ответы',
        'subject'     => 'Тема письма',
        'body'        => 'Тело письма',
        'attachments' => 'Вложения',
        'message'     => 'Сообщение об ошибке',
        'duration'    => 'Длительность выполнения',
        'memory'      => 'Потребление памяти',
        'size'        => 'Размер письма',
        'info'        => 'Дополнительная информация',
        'created_at'  => 'Добавлено',
        'updated_at'  => 'Обновлено',
    ];


    /**
     * @static
     * Возвращает экземпляр класса фабрики модели
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|MailLogFactory
     */
    protected static function newFactory(): MailLogFactory
    {
        return MailLogFactory::new();
    }


    /**
     * Связь с пользователем
     *
     * @return Relation
     */
    public function user(): Relation
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    /**
     * Фильтр по uuid
     *
     * @param Builder $query
     * @param string|null $uuid
     * @return Builder
     */
    public function scopeOfUuid(Builder $query, ?string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }


    /**
     * Фильтр по статусу
     *
     * @param Builder $query
     * @param MailLogStatusEnum|string|null $status
     * @return Builder
     */
    public function scopeOfStatus(Builder $query, MailLogStatusEnum|string|null $status): Builder
    {
        return $query->where('status', $status);
    }
}
