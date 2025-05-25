<?php

namespace Atlcom\LaravelHelper\Enums;

use App\Domains\General\Interfaces\EnumInterface;
use App\Domains\General\Traits\EnumTrait;
use Atlcom\Traits\HelperEnumTrait;
use BackedEnum;

/**
 * Файловые настройки для конфигураций моделей
 */
enum ModelConfigFilesEnum: string
{
    use HelperEnumTrait;


    case ModelConfigClass = 'model_class'; // класс модели
    case ModelConfigMorphName = 'morph_name'; // имя полиморфной связи
    case ModelConfigFilters = 'filters'; // настройка фильтров
    case ModelConfigFiles = 'files'; // настройка файлов
    case ModelConfigFilesEnabled = 'enabled'; // включить
    case ModelConfigFilesDisk = 'disk'; // диск хранения файлов
    case ModelConfigFilesPath = 'path'; // путь хранения файлов
    case ModelConfigFilesLabel = 'label'; // заголовок блока файлов
    case ModelConfigFilesDescription = 'description'; // описание блока файлов
    case ModelConfigFilesTypes = 'types'; // разрешённые типы файлов (jpg, png ...)
    case ModelConfigFilesMimes = 'mimes'; // разрешённые mime типы файлов
    case ModelConfigFilesMaxCount = 'max_count'; // макс. количество файлов в блоке
    case ModelConfigFilesMaxSize = 'max_size'; // макс. размер файла
    case ModelConfigFilesRequired = 'required'; // обязательный блок файлов
    case ModelConfigFilesPreview = 'preview'; // предпросмотр файлов в блоке
    case ModelConfigFilesUnique = 'unique'; // уникальность файлов для блока
    case ModelConfigFilesDelete = 'delete'; // удалять файлы с диска при удалении у модели
    case ModelConfigFilesTokenUrlTtl = 'token_ttl'; // время жизни токена просмотра файла
    case ModelConfigFilesItems = 'items'; // список файлов
    case ModelConfigFilesScopes = 'scopes'; // область видимости
    case ModelConfigFilesIsNew = 'is_new'; // флаг нового файла
    case ModelConfigFilesIsDelete = 'is_delete'; // флаг удалённого файла
    case ModelConfigFilesIsUpdate = 'is_update'; // флаг обновлённого файла
    case ModelConfigFilesModify = 'modify'; // флаг модификаций файла
    case ModelConfigFilesModifyUploadWidthMax = 'modify_upload_width_max'; // модификация максимальной ширины при upload
    case ModelConfigFilesModifyUploadHeightMax = 'modify_upload_height_max'; // модификация максимальной высота при upload
    case ModelConfigFilesModifyUploadQualityMax = 'modify_upload_quality_max'; // модификация максимального качества при upload
    case ModelConfigFilesModifyUploadWatermark = 'modify_upload_watermark'; // модификация водяного знака при upload
    case ModelConfigFilesModifyUploadFormat = 'modify_upload_format'; // модификация формата при upload
    case ModelConfigFilesModifyViewWidthMax = 'modify_view_width_max'; // модификация максимальной ширины при просмотре
    case ModelConfigFilesModifyViewHeightMax = 'modify_view_height_max'; // модификация максимальной высоты при просмотре
    case ModelConfigFilesModifyViewQualityMax = 'modify_view_quality_max'; // модификация максимального качества при просмотре
    case ModelConfigFilesModifyViewWatermark = 'modify_view_watermark'; // модификация водяного знака при просмотре
    case ModelConfigFilesModifyViewFormat = 'modify_view_format'; // модификация формата при просмотре
    case ModelConfigFilesModifyUploadOnFront = 'modify_upload_on_front'; // модификация формата на фронте перед загрузкой
    case ModelConfigFilesModifyUploadLzw = 'modify_upload_lzw'; // сжатие файла на фронте перед загрузкой
    case ModelConfigResources = 'resources'; // ресурс модели
}
