<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Services\TelegramBot;

use Atlcom\LaravelHelper\Defaults\DefaultService;
use Atlcom\LaravelHelper\Dto\TelegramBot\Models\TelegramBotUserDto;
use Atlcom\LaravelHelper\Models\TelegramBotUser;
use Atlcom\LaravelHelper\Repositories\TelegramBot\TelegramBotUserRepository;

/**
 * @internal
 * Сервис пользователя телеграм бота
 */
class TelegramBotUserService extends DefaultService
{
    public function __construct(private TelegramBotUserRepository $telegramBotUserRepository) {}


    /**
     * Сохраняет пользователя телеграм бота
     *
     * @param TelegramBotUserDto $dto
     * @return TelegramBotUser
     */
    public function save(TelegramBotUserDto $dto): TelegramBotUser
    {
        return $this->telegramBotUserRepository->updateOrCreate($dto);
    }


    /**
     * Оценивает примерную дату регистрации пользователя в телеграм по external_user_id
     *
     * @param int|null $externalUserId
     * @return string|null
     */
    public function getEstimateRegistrationDate(?int $externalUserId): ?string
    {
        $points = [
            // ранние годы
            20_000_000  => '2014-02-01', // официальная отметка 20 млн MAU
            50_000_000  => '2014-12-01',
            100_000_000 => '2016-02-01',
            200_000_000 => '2018-03-01',
            300_000_000 => '2019-10-01',

            // рост после 2020
            400_000_000 => '2020-04-01',
            500_000_000 => '2021-01-01',
            600_000_000 => '2021-12-01',

            // ID начинают расти более хаотично — ставлю контрольные точки по скачкам
            650_000_000 => '2022-05-01',
            700_000_000 => '2023-04-01',
            800_000_000 => '2024-04-01',
            900_000_000 => '2025-02-01',

            // прогнозируемая область (для новых id)
            950_000_000 => '2025-08-01',
        ];
        ksort($points);

        $lastId = null;
        $lastDate = null;

        foreach ($points as $pointId => $pointDate) {
            if ($externalUserId < $pointId) {
                if (!$lastId) {
                    return $pointDate;
                }

                // Интерполяция между двумя точками
                $ratio = ($externalUserId - $lastId) / ($pointId - $lastId);

                $start = strtotime($lastDate);
                $end = strtotime($pointDate);

                $estimate = $start + ($end - $start) * $ratio;

                return date('Y-m-d', (int)$estimate);
            }

            $lastId = $pointId;
            $lastDate = $pointDate;
        }

        // user_id больше всех известных → прогнозируем “в будущее”
        return date('Y-m-d', strtotime($lastDate . " +30 days"));
    }
}
