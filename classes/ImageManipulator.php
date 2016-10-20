<?php

namespace Code200\ImageKing\Classes;

use Code200\Imageking\models\Settings;
use October\Rain\Database\Attach\Resizer;

class ImageManipulator extends Resizer
{


    /**
     * Applies appropriate watermark to image object
     * @return $this
     */
    public function applyWatermark() {
        $watermarkObj = new Watermark($this->image);
        $watermark = $watermarkObj->getWatermark();
        imagealphablending($this->image, true);

        imagecopy($this->image, $watermark,
                $watermarkObj->getPositionX(),
                $watermarkObj->getPositionY(),
                0,0,
                imagesx($watermark), imagesy($watermark));


        return $this;
    }


    public function getExtension() {
        return $this->extension;
    }
}