<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\MailLogJob;
use Atlcom\LaravelHelper\Models\MailLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\PendingMail;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Throwable;

/**
 * @internal
 * Dto лога отправки письма
 * @see \Atlcom\LaravelHelper\Models\MailLog
 */
class MailLogDto extends Dto
{
    public const AUTO_MAPPINGS_ENABLED = true;

    public ?string $uuid;

    public int|string|null    $userId;
    public ?MailLogStatusEnum $status;
    public ?string            $from;
    public ?array             $to;
    public ?array             $cc;
    public ?array             $bcc;
    public ?array             $replyTo;
    public ?string            $subject;
    // markdown, view, viewData, rawAttachments, diskAttachments, tags, metadata, theme, callbacks
    public ?string $body;
    public ?array  $attachments;

    public ?string $message;
    public ?float  $duration;
    public ?int    $memory;
    public ?int    $size;
    public ?array  $info;

    public ?Throwable $exception;
    public string     $startTime;
    public int        $startMemory;


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [
            'user_id'     => user(returnOnlyId: true),
            'status'      => MailLogStatusEnum::Process,
            'startTime'   => (string)now()->getTimestampMs(),
            'startMemory' => memory_get_usage(),
        ];
    }


    /**
     * Создает dto из PendingMail
     *
     * @param Mailable|string|array $view
     * @param array $data
     * @return static
     */
    public static function createFromPendingMail(Mailable|string|array $view, array $data = []): static
    {
        return $view instanceof Mailable
            ? static::createByMailable($view)
            : static::create([
                'uuid' => uuid(),
                'info' => ['view' => $view],
            ]);
    }


    /**
     * Создает dto из Mailable или Email
     *
     * @param Mailable|Email|null $mailable
     * @param array|null $info
     * @return static
     */
    public static function createByMailable(
        Mailable|Email|null $mailable = null,
        ?array $info = null,
    ): static {
        $dto = static::create([
            'uuid' => uuid(),
            'info' => $info,
        ]);

        if ($mailable instanceof Mailable) {
            /** @see \Illuminate\Mail\Mailable */
            $dto->from = static::formatAddressesToString($mailable->from)
                ?: static::formatAddressesToString([config('mail.from')]);
            $dto->to = static::formatAddresses($mailable->to);
            $dto->cc = static::formatAddresses($mailable->cc);
            $dto->bcc = static::formatAddresses($mailable->bcc);
            $dto->replyTo = static::formatAddresses($mailable->replyTo);
            $dto->subject = $mailable->subject;
            try {
                $dto->body = $mailable->render() ?: $mailable->textView;

            } catch (Exception $e) {
                $dto->body = 'Error rendering body: ' . $e->getMessage();
            }
            $dto->attachments = array_map(fn ($a) => $a['name'] ?? 'unknown', $mailable->attachments);
            $dto->info = [
                'class'           => $mailable::class,
                'markdown'        => $mailable->markdown,
                'view'            => $mailable->view,
                'data'            => $mailable->viewData,
                'rawAttachments'  => $mailable->rawAttachments,
                'diskAttachments' => $mailable->diskAttachments,
                // 'callbacks'       => $mailable->callbacks,
                'theme'           => $mailable->theme,
                'mailer'          => $mailable->mailer,
            ];

        } else if ($mailable instanceof Email) {
            /** @see \Symfony\Component\Mime\Email */
            $dto->from = static::formatSymfonyAddressesToString($mailable->getFrom());
            $dto->to = static::formatSymfonyAddresses($mailable->getTo());
            $dto->cc = static::formatSymfonyAddresses($mailable->getCc());
            $dto->bcc = static::formatSymfonyAddresses($mailable->getBcc());
            $dto->replyTo = static::formatSymfonyAddresses($mailable->getReplyTo());
            $dto->subject = $mailable->getSubject();
            $dto->body = $mailable->getHtmlBody() ?: $mailable->getTextBody();
            $dto->attachments = array_map(fn ($a) => $a->getFilename(), $mailable->getAttachments());
            $dto->info = [
                'class'       => $mailable::class,
                'priority'    => $mailable->getPriority(),
                'htmlCharset' => $mailable->getHtmlCharset(),
                'textCharset' => $mailable->getTextCharset(),
            ];
        }

        $dto->size = Hlp::stringLength($dto->body);

        return $dto;
    }


    private static function formatAddresses(array $addresses): array
    {
        return array_map(function ($address) {
            if ($address instanceof Address) {
                return $address->getAddress();
            }

            if (is_array($address)) {
                return $address['address'] ?? '';
            }

            if (is_object($address) && property_exists($address, 'address')) {
                return $address->address;
            }

            if (is_object($address) && property_exists($address, 'email')) {
                return $address->email;
            }

            return (string)$address;
        }, $addresses);
    }


    private static function formatAddressesToString(array $addresses): ?string
    {
        $formatted = self::formatAddresses($addresses);

        return !empty($formatted) ? implode(', ', $formatted) : null;
    }


    private static function formatSymfonyAddresses(array $addresses): array
    {
        return array_map(fn ($a) => $a->getAddress(), $addresses);
    }


    private static function formatSymfonyAddressesToString(array $addresses): string
    {
        return implode(', ', self::formatSymfonyAddresses($addresses));
    }


    public function updateFromMailable(Mailable $mailable): static
    {
        $this->from = static::formatAddressesToString($mailable->from)
            ?: static::formatAddressesToString([config('mail.from')]);
        $this->to = static::formatAddresses($mailable->to);
        $this->cc = static::formatAddresses($mailable->cc);
        $this->bcc = static::formatAddresses($mailable->bcc);
        $this->replyTo = static::formatAddresses($mailable->replyTo);
        $this->subject = $mailable->subject;

        try {
            $this->body = $mailable->render() ?: $mailable->textView;

        } catch (Exception $e) {
            $this->body = 'Error rendering body: ' . $e->getMessage();
        }
        $this->attachments = array_map(fn ($a) => $a['name'] ?? 'unknown', $mailable->attachments);
        $this->size = Hlp::stringLength($this->body);

        return $this;
    }


    public function updateFromEmail(Email $email): static
    {
        $this->from = static::formatSymfonyAddressesToString($email->getFrom());
        $this->to = static::formatSymfonyAddresses($email->getTo());
        $this->cc = static::formatSymfonyAddresses($email->getCc());
        $this->bcc = static::formatSymfonyAddresses($email->getBcc());
        $this->replyTo = static::formatSymfonyAddresses($email->getReplyTo());
        $this->subject = $email->getSubject();
        $this->body = $email->getHtmlBody() ?: $email->getTextBody();
        $this->attachments = array_map(fn ($a) => $a->getFilename(), $email->getAttachments());
        $this->size = Hlp::stringLength($this->body);

        $this->info = [
            ...$this->info ?? [],
            'priority'    => $email->getPriority(),
            'htmlCharset' => $email->getHtmlCharset(),
            'textCharset' => $email->getTextCharset(),
        ];

        return $this;
    }


    public function update(?SentMessage $result = null): static
    {
        $this->info = [
            ...$this->info ?? [],
            ...(
                $result
                ? [
                    'message_id' => $result->getMessageId(),
                    'debug'      => $result->getDebug(),
                    'headers'    => $result->getOriginalMessage()->getHeaders()->toArray(),
                ]
                : []
            ),
            ...(
                $this->exception
                ? ['exception' => Hlp::exceptionToString($this->exception)]
                : []
            ),
            'duration' => $duration = Hlp::timeSecondsToString(
                value: $this->duration = $this->getDuration(),
                withMilliseconds: true,
            ),
            'memory'   => $memory = Hlp::sizeBytesToString($this->memory = $this->getMemory()),
            'size'     => $size = Hlp::sizeBytesToString($this->size),
        ];

        return $this;
    }


    /**
     * Отправляет dto в очередь для сохранения лога
     *
     * @return static
     */
    public function dispatch(): static
    {
        if (Lh::canDispatch($this)) {
            (Lh::config(ConfigEnum::MailLog, 'queue_dispatch_sync') ?? (isLocal() || isDev() || isTesting()))
                ? MailLogJob::dispatchSync($this)
                : MailLogJob::dispatch($this);
        }

        return $this;
    }


    /**
     * @see parent::__serialize()
     * @return array
     */
    public function __serialize(): array
    {
        $this->body = Hlp::stringTruncateBetweenQuotes($this->body, 10000);

        return parent::__serialize();
    }


    /**
     * @inheritDoc
     */
    protected function onSerializing(array &$array): void
    {
        $this->onlyKeys(MailLog::getModelKeys())
            ->onlyNotNull()
            ->excludeKeys([
                'exception',
                'startTime',
                'startMemory',
            ]);
    }


    /**
     * Возвращает длительность работы скрипта
     *
     * @return float
     */
    public function getDuration(): float
    {
        return max(0, Carbon::createFromTimestampMs($this->startTime)->diffInMilliseconds() / 1000);
    }


    /**
     * Возвращает потребляемую память скрипта
     *
     * @return int
     */
    public function getMemory(): int
    {
        return max(0, memory_get_usage() - $this->startMemory);
    }
}
