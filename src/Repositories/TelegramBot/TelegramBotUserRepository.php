<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Repositories\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultRepository;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotUserDto;
use Atlcom\LaravelHelper\Enums\ConfigEnum;
use Atlcom\LaravelHelper\Facades\Lh;
use Atlcom\LaravelHelper\Models\TelegramBotUser;

/**
 * @internal
 * Репозиторий пользователя телеграм бота
 */
class TelegramBotUserRepository extends DefaultRepository
{
    public function __construct(
        /** @var TelegramBotUser */ private ?string $model = null,
    ) {
        $this->model ??= Lh::config(ConfigEnum::TelegramBot, 'model_user') ?? TelegramBotUser::class;
    }


    /**
     * Возвращает пользователя по внешнему Id
     *
     * @param int $externalUserId
     * @return TelegramBotUser|null
     */
    public function getByExternalUserId(int $externalUserId): ?TelegramBotUser
    {
        return $this->withoutTelescope(
            fn () => $this->model::query()
                ->withoutQueryLog()
                ->withoutQueryCache()
                ->ofExternalUserId($externalUserId)
                ->first()
        );
    }


    /**
     * Создает или обновляет пользователя телеграм бота в БД
     *
     * @param TelegramBotUserDto $dto
     * @return TelegramBotUser
     */
    public function updateOrCreate(TelegramBotUserDto $dto): TelegramBotUser
    {
        return $this->withoutTelescope(function () use ($dto) {
            ($model = $this->getByExternalUserId($dto->externalUserId))
                ? $model->update([
                    'first_name' => $dto->firstName,
                    'user_name'  => $dto->userName,
                    'phone'      => $dto->phone,
                    'language'   => $dto->language,
                    'is_bot'     => $dto->isBot,
                    ...(
                        (is_null($model->info) && is_null($dto->info))
                        ? []
                        : [
                            'info' => [
                                ...($model->info ?? []),
                                ...($dto->info ?? []),
                            ],
                        ]
                    ),
                ])
                : $model = $this->model::query()
                    ->withoutQueryLog()
                    ->withoutQueryCache()
                    ->create([
                        'uuid'             => $dto->uuid,
                        'external_user_id' => $dto->externalUserId,
                        'first_name'       => $dto->firstName,
                        'user_name'        => $dto->userName,
                        'phone'            => $dto->phone,
                        'language'         => $dto->language,
                        'is_ban'           => $dto->isBan,
                        'is_bot'           => $dto->isBot,
                        'info'             => $dto->info,
                    ]);

            return $model;
        });
    }
}
