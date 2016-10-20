<?php

namespace Code200\ImageKing\Classes;


use Code200\Imageking\models\Settings;
use October\Rain\Database\Attach\Resizer;
use Backend\Models\User;
use System\Models\File;

class Watermark extends Resizer
{

    /**
     * Settings instance holder
     * @var Settings
     */
    private $s;


    /**
     * At which size we need to use small watermark setting
     * Value fetched from settings
     * @var integer
     */
    private $smallWatermarkLimit;


    /**
     * At which size we need to ignore adding watermark
     * Value fetched from settings
     * @var  integer
     */
    private $noWatermarkLimit;


    /**
     * Watermark file resource
     * @var File
     */
    private $watermarkResource;


    /**
     * Size of watermark in % of main image
     * @var integer
     */
    private $watermarkRelativeSize;

    /**
     * Horizontal position of watermark in %
     * @var integer
     */
    private $watermarkRelativePosX;

    /**
     * Vertical position of watermark in %
     * @var integer
     */
    private $watermarkRelativePosY;

    /**
     * Image Resource we are applying watermark to
     * @var Resource
     */
    private $mainImage;


    /**
     * Main Image width in px
     * @var integer
     */
    private $mainImageWidth;

    /**
     * Watermark constructor.
     * @param Resource $mainImagePath
     * @return void|null if image too small
     */
    public function __construct($mainImagePath)
    {
        $this->mainImage = $mainImagePath;
        $this->fetchSettings();
        $watermarkPath = $this->getWatermarkImgPath();

        if(empty($watermarkPath)){
            return null;
        }
        parent::__construct($watermarkPath);

        $this->resizeToSettings();
    }

    private function fetchSettings()
    {
        $this->s = Settings::instance();
        $this->smallWatermarkLimit = $this->s->get("watermark_small_limit");
        $this->noWatermarkLimit = $this->s->get("nowatermark_limit");
        $this->mainImageWidth = imagesx($this->mainImage);

        if($this->noWatermarkLimit >= $this->mainImageWidth){
            $this->watermarkResource = null;
        }
        else if($this->smallWatermarkLimit >= $this->mainImageWidth){ //use small settings
            $this->watermarkResource = $this->s->watermark_img_small;
            $this->watermarkRelativeSize = $this->s->get("watermark_size_small");
            $this->watermarkRelativePosX = $this->s->get("watermark_position_x_small");
            $this->watermarkRelativePosY = $this->s->get("watermark_position_y_small");
        }
        else if(empty($watermarkObj)) { //use normal settings
            $this->watermarkResource = $this->s->watermark_img;
            $this->watermarkRelativeSize = $this->s->get("watermark_size");
            $this->watermarkRelativePosX = $this->s->get("watermark_position_x");
            $this->watermarkRelativePosY = $this->s->get("watermark_position_y");
        }
    }


    /**
     * Gets full path of appropriate watermark image on disk
     * @return null|string
     */
    private function getWatermarkImgPath() {
        if(empty($this->watermarkResource)){
            return null;
        }

        return base_path( $this->watermarkResource->getPath() );
    }


    /**
     * Resize watermark image to percentage set in settings
     */
    private function resizeToSettings(){
        $mainImageWidth = $this->mainImageWidth;
        $watermarkImageWidth = imagesx($this->image);
        //only scale watermark down
        $newWatermarkWidthInPx = min(
            round($mainImageWidth * ($this->watermarkRelativeSize/100)),
            $watermarkImageWidth
        );

        $this->resize($newWatermarkWidthInPx, null);
    }


    public function getWatermark() {
        return $this->image;
    }


    /**
     * @return integer
     */
    public function getPositionX() {
        $posX = $this->mainImageWidth * $this->watermarkRelativePosX / 100;

        if($this->watermarkRelativePosX < 0){
            $posX = $this->mainImageWidth - imagesx($this->image) + $posX;
        }

        return (int)$posX;
    }

    /**
     * Returns watermark position in px from the main image top
     * @return int
     */
    public function getPositionY() {
        $mainImageHeight = imagesy($this->mainImage);
        $posY = $mainImageHeight * $this->watermarkRelativePosY / 100;

        if($this->watermarkRelativePosY < 0){
            $posY = $mainImageHeight - imagesy($this->image) + $posY;
        }

        return (int)$posY;
    }
}