<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Mail;

use Illuminate\Mail\MailManager;

/**
 * Расширенный менеджер почты для создания HelperMailer
 */
class HelperMailManager extends MailManager
{
    /**
     * Build a new mailer instance.
     *
     * @param  array  $config
     * @return \Illuminate\Mail\Mailer
     */
    public function build($config)
    {
        $mailer = new HelperMailer(
            $config['name'] ?? 'ondemand',
            $this->app['view'],
            $this->createSymfonyTransport($config),
            $this->app['events'],
        );

        if ($this->app->bound('queue')) {
            $mailer->setQueue($this->app['queue']); //?!? 
        }

        return $mailer;
    }
}
