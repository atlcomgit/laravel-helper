<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Models;

use Atlcom\LaravelHelper\Defaults\DefaultModel;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

/**
 * Модель лога отправки письма
 *
 * @property string $uuid
 * @property int|string|null $user_id
 * @property MailLogStatusEnum $status
 * @property string|null $from
 * @property array|null $to
 * @property array|null $cc
 * @property array|null $bcc
 * @property string|null $subject
 * @property string|null $body
 * @property array|null $attachments
 * @property string|null $error_message
 * @property array|null $info
 */
class MailLog extends DefaultModel
{
    public const COMMENT = 'Логи отправки писем';

    protected $fillable = [
        'uuid',
        'user_id',
        'status',
        'from',
        'to',
        'cc',
        'bcc',
        'subject',
        'body',
        'attachments',
        'error_message',
        'info',
    ];

    protected $casts = [
        'status'      => MailLogStatusEnum::class,
        'to'          => 'array',
        'cc'          => 'array',
        'bcc'         => 'array',
        'attachments' => 'array',
        'info'        => 'array',
    ];


    public function getConnectionName()
    {
        return Lh::getConnection(ConfigEnum::MailLog);
    }


    public function getTable()
    {
        return Lh::getTable(ConfigEnum::MailLog);
    }


    /**
     * Связь с пользователем
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
