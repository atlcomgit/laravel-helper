<?php

declare(strict_types=1);

namespace Atlcom\LaravelHelper\Defaults;

use Atlcom\Helper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Абстрактный класс для валидатора запросов
 */
abstract class DefaultRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }


    /**
     * Подготавливает данные для валидации
     *
     * @return void
     */
    public function prepareForValidation(): void
    {
        Helper::cacheRuntimeSet('ValidationRequest', class_basename($this));
    }
}
