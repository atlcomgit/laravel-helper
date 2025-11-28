<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Dto;

use Atlcom\Dto;
use Atlcom\Hlp;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Enums\MailLogStatusEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Jobs\MailLogJob;
use Exception;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

/**
 * @internal
 * Dto лога отправки письма
 * @see \Atlcom\LaravelHelper\Models\MailLog
 */
//?!? 
class MailLogDto extends Dto
{
    public const AUTO_MAPPINGS_ENABLED = true;

    public ?string $uuid;

    public int|string|null    $user_id; //?!? camel
    public ?MailLogStatusEnum $status;
    public ?string            $from;
    public ?array             $to;
    public ?array             $cc;
    public ?array             $bcc;
    public ?string            $subject;
    public ?string            $body;
    public ?array             $attachments;

    public ?string $error_message; //?!? delete
    public ?array  $info;

    public ?Exception $exception;


    /**
     * @inheritDoc
     * @see parent::defaults()
     *
     * @return array
     */
    protected function defaults(): array
    {
        return [
            'user_id' => user(returnOnlyId: true),
            'status'  => MailLogStatusEnum::Process,
        ];
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
            $dto->from = self::formatAddressesToString($mailable->from);
            $dto->to = self::formatAddresses($mailable->to);
            $dto->cc = self::formatAddresses($mailable->cc);
            $dto->bcc = self::formatAddresses($mailable->bcc);
            $dto->subject = $mailable->subject;
            // Body might be hard to get before render, but we can try
            try {
                $dto->body = $mailable->render();
            } catch (Exception $e) {
                $dto->body = 'Error rendering body: ' . $e->getMessage();
            }
            $dto->attachments = array_map(fn ($a) => $a['name'] ?? 'unknown', $mailable->attachments);
        } elseif ($mailable instanceof Email) {
            $dto->from = self::formatSymfonyAddressesToString($mailable->getFrom());
            $dto->to = self::formatSymfonyAddresses($mailable->getTo());
            $dto->cc = self::formatSymfonyAddresses($mailable->getCc());
            $dto->bcc = self::formatSymfonyAddresses($mailable->getBcc());
            $dto->subject = $mailable->getSubject();
            $dto->body = $mailable->getHtmlBody() ?? $mailable->getTextBody();
            $dto->attachments = array_map(fn ($a) => $a->getFilename(), $mailable->getAttachments());
        }

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
    //?!? delete
    public function toArray(
        ?bool $onlyFilled = null,
        ?bool $onlyNotNull = null,
        ?array $onlyKeys = null,
        ?array $excludeKeys = null,
        ?array $mappingKeys = null,
    ): array {
        $data = parent::toArray($onlyFilled, $onlyNotNull, $onlyKeys, $excludeKeys, $mappingKeys);
        unset($data['exception']);

        return $data;
    }
}
