<?php

namespace Code200\ImageKing\Classes;

use Illuminate\Support\Facades\URL;
use October\Rain\Database\Attach\Resizer;
use October\Rain\Support\Facades\Str;
use File as FileHelper;


class ImageManipulator extends Resizer
{

    private $originalImageFilePath;

    /**
     * ImageManipulator constructor.
     * @param string $mainImagePath
     */
    public function __construct($mainImagePath)
    {
        $this->originalImageFilePath = $mainImagePath;
        parent::__construct($mainImagePath);
    }



    /**
     * Applies appropriate watermark to image object
     * @return $this
     */
    public function applyWatermark() {
        $watermarkObj = new Watermark($this->image);

        //if we cant get watermark object (image too small) do nothing
        if(empty($watermarkObj)){
            return $this;
        }

        $watermark = $watermarkObj->getWatermark();
        imagealphablending($this->image, true);
        imagecopy($this->image, $watermark,
                $watermarkObj->getPositionX(),
                $watermarkObj->getPositionY(),
                0,0,
                imagesx($watermark), imagesy($watermark));

        imagedestroy($watermark);

        return $this;
    }





    public function getExtension() {
        return $this->extension;
    }


    public function getOriginalImageFilePath() {
        return $this->originalImageFilePath;
    }

    /**
     * Extracts the filename and extension
     * out of the image path.
     */
    protected function getFilename()
    {
        if(empty($this->filename)) {
            $basename = basename($this->originalImageFilePath);
            $this->filename  = pathinfo($basename, PATHINFO_FILENAME);
        }
        return $this->filename;
    }


    /**
     * Remove the local host name and add the base path.
     *
     * @param $imagePath
     *
     * @return mixed
     */
    protected function getDiskPath($imagePath)
    {
        return str_replace(URL::to('/'), '', base_path($imagePath));
    }


    /**
     * Returns the copy's filename.
     *
     * @param $size
     * @param $slugify
     *
     * @return string
     */
    public function getNewFilename($size = null, $slugify = true)
    {
        $newFilename = $this->getFilename();
        if(!empty($size)) {
            $newFilename .= "-" . $size;
        }

        if($slugify) {
            $newFilename = Str::slug($newFilename, "-");
        }

        $newFilename .= "." . $this->extension;
        return $newFilename;
    }


    /**
     * Returns the hashed file path.
     *
     * @return string
     */
    protected function getPathHash()
    {
        return md5($this->originalImageFilePath);
    }


    /**
     * Returns the partition directory based on the image's path.
     *
     * @return string
     */
    protected function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->getPathHash(), 3), 0, 3)) . '/';
    }


    /**
     * Returns the absolute path for a image copy.
     *
     * @param $size
     *
     * @return string
     */
    public function getStoragePath($size = null)
    {
        $path = temp_path('public/' . $this->getPartitionDirectory());
        if ( ! FileHelper::isDirectory($path)) {
            FileHelper::makeDirectory($path, 0777, true, true);
        }

        $storagePath = $path . $this->getNewFilename($size);
        return $storagePath;
    }


    public function getPublicUrl($diskPath) {
        $relativePath = str_replace(base_path(), '', $diskPath);
        $filename = basename($relativePath);
        $relativeFolderPath = str_replace($filename, '', $relativePath);

        return URL::to('/') . $relativeFolderPath . rawurlencode($filename) ;
    }
}