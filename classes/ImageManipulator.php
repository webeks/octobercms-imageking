<?php

namespace Code200\ImageKing\Classes;

use Code200\ImageKing\models\Settings;
use Code200\ImageKing\Classes\Exceptions\FileNotFoundException;
use Code200\ImageKing\Classes\Exceptions\NotLocalFileException;
use Illuminate\Support\Facades\URL;
use October\Rain\Database\Attach\Resizer;
use October\Rain\Support\Facades\Config;
use October\Rain\Support\Facades\Str;
use Illuminate\Support\Facades\File as FileHelper;


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
        $this->validate();
        parent::__construct($this->originalImageFilePath);
    }


    public function validate()
    {
        if (!FileHelper::isLocalPath($this->originalImageFilePath)) {
            throw new NotLocalFileException('The specified path is not local.');
        }
        if (!file_exists($this->originalImageFilePath)) {
            throw new FileNotFoundException('The specified file does not exist.');
        }
    }

    /**
     * Applies appropriate watermark to image object
     * @return $this
     */
    public function applyWatermark()
    {
        $watermarkObj = $this->getWatermarkObject();

        //if we cant get watermark object (image too small) do nothing
        if (empty($watermarkObj)) {
            return $this;
        }

        $watermark = $watermarkObj->getWatermark();
        if (empty($watermark)) {
            return $this;
        }

        imagealphablending($this->image, true);
//        imagecolortransparent($this->image, imagecolorallocatealpha($this->image, 0, 0, 0, 127));
        imagesavealpha($this->image, true);
        imagecopy($this->image, $watermark,
            $watermarkObj->getPositionX(),
            $watermarkObj->getPositionY(),
            0, 0,
            imagesx($watermark), imagesy($watermark));

        imagedestroy($watermark);

        return $this;
    }

    protected function getWatermarkObject()
    {
        $settings = Settings::instance();
        $mainImageWidth = $this->getWidth();

        if ($settings->get("nowatermark_limit") >= $mainImageWidth) {
            return;
        } else {
            if ($settings->get("watermark_small_limit") >= $mainImageWidth && !empty($this->getSmallWatermarkFilePath())) { //use small settings
                $watermarkResouce = $this->getSmallWatermarkFilePath();
                $watermarkRelativeSize = $settings->get("watermark_size_small");
                $watermarkRelativePosX = $settings->get("watermark_position_x_small");
                $watermarkRelativePosY = $settings->get("watermark_position_y_small");
            } else { //use normal settings
                $watermarkResouce = $this->getWatermarkFilePath();
                $watermarkRelativeSize = $settings->get("watermark_size");
                $watermarkRelativePosX = $settings->get("watermark_position_x");
                $watermarkRelativePosY = $settings->get("watermark_position_y");
            }
        }

        $watermark = new Watermark($watermarkResouce);
        $watermark
            ->setWatermarkRelativePosition($watermarkRelativePosX, $watermarkRelativePosY)
            ->setWatermarkRelativeSize($watermarkRelativeSize)
            ->setMainImageSize(imagesx($this->image), imagesy($this->image))
            ->applyRelativeSize();

        return $watermark;

    }


    /**
     * Returns small watermark from settings
     * @return mixed
     */
    protected function getSmallWatermarkFilePath()
    {
        $settings = Settings::instance();
        return $settings->watermark_img_small;
    }

    /**
     * Returns watermark from settings
     * @return mixed
     */
    public function getWatermarkFilePath()
    {
        $settings = Settings::instance();
        return $settings->watermark_img;
    }


    public function getImage()
    {
        return $this->image;
    }

    public function getExtension()
    {
        if ($this->extension === "jpeg") {
            return "jpg";
        }

        return $this->extension;
    }


    public function getOriginalImageFilePath()
    {
        return $this->originalImageFilePath;
    }

    /**
     * Extracts the filename and extension
     * out of the image path.
     */
    protected function getFilename()
    {
        if (empty($this->filename)) {
            $basename = basename($this->getOriginalImageFilePath());
            $this->filename = pathinfo($basename, PATHINFO_FILENAME);
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
        if (!empty($size)) {
            $newFilename .= "-" . $size;
        }

        if ($slugify) {
            $newFilename = Str::slug($newFilename, "-");
        }

        $newFilename .= "." . $this->getExtension();

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
        $path = $this->getStoragePathDir();
        $storagePath = $path . $this->getNewFilename($size);

        return $storagePath;
    }

    public function getStoragePathDir()
    {
        $path = temp_path('public/' . $this->getPartitionDirectory());

        if (!Settings::get("private_paths_obstruction")) {
             $folderPath = dirname($this->getOriginalImageFilePath());

             $uploadsPath = base_path() . Config::get('cms.storage.uploads.path', '/storage/app/uploads');
             $mediaPath = base_path() . Config::get('cms.media.uploads.path', '/storage/app/media');

             $relativePath = str_replace($uploadsPath, 'uploads', $folderPath);
             $relativePath = str_replace($mediaPath, 'media', $relativePath);

            //we found a match either in uploads or media folder
            if ($relativePath != $folderPath) {
                $path = temp_path('public/' . $relativePath . "/");
            }
        }

        $this->makeDirectory($path);

        return $path;
    }

    public function getPublicUrl($diskPath)
    {
        $relativePath = str_replace(base_path(), '', $diskPath);
        $filename = basename($relativePath);
        $relativeFolderPath = str_replace($filename, '', $relativePath);

        return URL::to('/') . $relativeFolderPath . rawurlencode($filename);
    }


    /**
     * Returns width of current image obj
     * @return int
     */
    public function getWidth()
    {
        return parent::getWidth();
    }

    /**
     * @param $path
     */
    protected function makeDirectory($path)
    {
        if (!FileHelper::isDirectory($path)) {
            FileHelper::makeDirectory($path, 0777, true, true);
        }
    }
}