<?php

namespace Code200\ImageKing\Classes;


//use Code200\Imageking\models\Settings;
use Code200\Imageking\models\Settings;
use October\Rain\Database\Attach\Resizer;
use System\Models\File;

class Watermark extends Resizer
{

    /**
     * Watermark file resource
     * @var File
     */
    protected $watermarkResourceFilePath;


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
     * Main Image width in px
     * @var integer
     */
    private $mainImageWidth;


    /**
     * Main image height in px
     * @var integer
     */
    private $mainImageHeight;

    /**
     * Watermark constructor.
     */
    public function __construct($watermarkResource)
    {
        $this->watermarkResourceFilePath = $this->getWatermarkFilePathFromResource($watermarkResource);

        if(empty($this->watermarkResourceFilePath)){
            return null;
        }
        parent::__construct($this->watermarkResourceFilePath);
    }


    /**
     * @param $resource
     * @return null|string
     */
    private function getWatermarkFilePathFromResource($resource) {
        if(empty($resource)) return null;
        if(is_string($resource)) return $resource;

        return base_path($resource->getPath());
    }


    /**
     * Resize watermark image to percentage set in settings
     */
    public function applyRelativeSize(){
        $mainImageWidth = $this->mainImageWidth;
        $watermarkImageWidth = imagesx($this->image);
        //only scale watermark down
        $newWatermarkWidthInPx = min(
            round($mainImageWidth * ($this->watermarkRelativeSize/100)),
            $watermarkImageWidth
        );

        return $this->resize($newWatermarkWidthInPx, null);
    }


    public function setWatermarkRelativePosition($X, $Y) {
        $this->watermarkRelativePosX = (int) $X;
        $this->watermarkRelativePosY = (int) $Y;
        return $this;
    }

    public function setWatermarkRelativeSize($relativeSize) {
        $this->watermarkRelativeSize = (int) $relativeSize;
        return $this;
    }
    
    public function setMainImageSize($mainImageWidth, $mainImageHeight) {
        $this->mainImageWidth = (int) $mainImageWidth;
        $this->mainImageHeight = (int) $mainImageHeight;
        return $this;
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
        $posY = $this->mainImageHeight * $this->watermarkRelativePosY / 100;

        if($this->watermarkRelativePosY < 0){
            $posY = (int) $this->mainImageHeight - imagesy($this->image) + $posY;
        }

        return (int)$posY;
    }
}