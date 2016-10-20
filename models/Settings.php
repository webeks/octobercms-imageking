<?php namespace Code200\Imageking\models;


use October\Rain\Database\Model;

use System\Models\File;

class Settings extends Model
{

//    use \October\Rain\Database\Traits\Hashable;
//    use \October\Rain\Database\Traits\Purgeable;
//    use \October\Rain\Database\Traits\Validation;

    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'code200_eucookielawmadness_settings';

    public $settingsFields = 'fields.yaml';

    protected $cache = [];


    public $attachOne = [
        'watermark_img' => ['System\Models\File', "public" => false],
        'watermark_img_small' => ['System\Models\File', 'public' => false]
    ];



    public function getWatermarkImg() {
        return $this->watermark_img;
    }



}
