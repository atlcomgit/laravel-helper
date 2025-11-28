<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Базовый класс для отправки писем
 */
class DefaultMailable extends Mailable
{
    use Queueable, SerializesModels;
}
