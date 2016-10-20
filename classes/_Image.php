<?php

namespace Code200\ImageKing\Classes;


use Code200\ImageKing\Classe\Resize\ImageResizer;
use Code200\ImageKing\Classes\Exceptions\FileDoesNotExistException;
use Code200\ImageKing\Classes\Exceptions\InvalidFileException;
use Code200\ImageKing\Classes\Exceptions\NotLocalFileException;
use Code200\Imageking\models\Settings;
use Illuminate\Support\Facades\URL;
use File as FileHelper;

class Image
{
    /**
     * Path to original source image
     * @var string
     */
    private $originalSourcePath;

    /**
     * Path to new main image that will be displayed in src attribute
     * @var string
     */
    private $mainImagePath;


    private $watermarkPath;

    private $width;

    /**
     * @var
     */
    private $responsiveImagesList;

    private $allowedExtensions;

    /**
     * Overall max width of all images
     * @var int
     */
    private $maxWidth = 0;


    /**
     * @var ImageResizer
     */
    private $resizer;

    /**
     * @var
     */
    private $image;


    /**
     * Create all the needed copies of the image.
     *
     * @param string $imagePath
     */
    public function __construct($imagePath)
    {
        $this->originalSourcePath = $this->normalizeImagePath($imagePath);
        $this->isOriginalSourceValid();
        $this->setConfigValues();
    }

    /**
     * Remove the local host name and add the base path.
     *
     * @param string $imagePath
     *
     * @return mixed
     */
    private function normalizeImagePath($imagePath) {
        $imagePath = urldecode($imagePath);
        return str_replace(URL::to('/'), '', base_path($imagePath));
    }


    private function setConfigValues() {
        $this->setAllowedExtensionsFromConfig();
        $this->setMaxWidth();
    }

    private function isOriginalSourceValid() {
        if ( ! FileHelper::isLocalPath($this->originalSourcePath) ) {
            throw new NotLocalFileException('The specified path is not local.');
        }
        if ( ! file_exists($this->originalSourcePath)) {
            throw new FileDoesNotExistException('The specified file does not exist.');
        }

        if(empty(getimagesize($this->originalSourcePath))) {
            throw new InvalidFileException("File specified is not an image.");
        }

        return true;
    }

    /**
     * Sets allowed extensions based on user input in backend settings
     */
    private function setAllowedExtensionsFromConfig() {
        $configAllowedExtensions = Settings::get("allowed_extensions");
        $this->allowedExtensions = array_map(function($element) {
            trim($element);
        }, explode(",", $configAllowedExtensions));
    }

    private function setMaxWidth() {
        $this->maxWidth = Settings::get("max_width");
    }


    public function resize($width) {
        $this->resizer = new ImageResizer($this->originalSourcePath);
        $this->
    }

}