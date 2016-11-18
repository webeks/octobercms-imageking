<?php
namespace Code200\ImageKing\Models;

use October\Rain\Database\Model;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'code200_imageking_settings';

    public $settingsFields = 'fields.yaml';

    protected $cache = [];


    public $attachOne = [
        'watermark_img' => ['System\Models\File', "public" => false],
        'watermark_img_small' => ['System\Models\File', 'public' => false]
    ];
}

