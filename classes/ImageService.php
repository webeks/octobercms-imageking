<?php

namespace Code200\ImageKing\Classes;


use Code200\ImageKing\Classe\Resize\ImageResizer;
use Code200\ImageKing\Classes\Exceptions\ExtensionNotAllowedException;
use Code200\ImageKing\Classes\Resize\MaxWidthService;
use Code200\Imageking\models\Settings;
use Illuminate\Support\Facades\Log;

class ImageService
{
    /**
     * @var string
     */
    private $html;
    /**
     * @var DomManipulator
     */
    private      $dom;

    private $imgPath;

    /**
     * @var Settings
     */
    private $s;


    /**
     * Allowed image extensions
     * @var array
     */
    private $allowedExtensions;

    /**
     * @param $html
     */
    public function __construct($html)
    {
        $this->html           = $html;
        $this->dom = new DomImageFinder($this->html);
        $this->s = Settings::instance();
    }


    /**
     * Process images.
     *
     * @return string
     */
    public function process()
    {
        $srcSets = [];
        $imageNodes = $this->dom->getImageNodes();
        foreach ($imageNodes as $node) {
            try {

                $this->imagePath = rawurldecode($this->dom->getSrcAttribute($node));
                $image = new ImageManipulator($this->imagePath);
                $this->checkIfProcessable($image);



                $maxWidth = $this->s->get("max_width");
                if(!empty($maxWidth)) {
                    $image->resize($maxWidth, null);
                }

                if($this->shouldWatermark()){
                    $image->applyWatermark();
                }





                $path = $image->getStoragePath();
                $image->save($path);


                $privatePaths = Settings::get("enable_private_paths");
                if(!empty($privatePaths) && empty($maxWidth)) {
                    //just move image
                }

                //get image path from node
                //get image from path
                //is max width set? => resize image from path
                //is watermark set? => apply watermark, save image, change dom src
                //else just optimize and save image to public folder and change dom ssrc if private images are set?
                //is responsive => make smaller versions of main image and add srcset to
                //is caption => transform dom
                //
                //
                //move from public path
                //add watermark
                //add responsive
                //add caption
//                $responsiveImage = new ResponsiveImage($source);




            } catch(\RemotePathException $e) {
                //we simply cant and dont want to process remote images ...
                continue;
            } catch(ExtensionNotAllowedException $e) {
                //we dont want to process certain files I guess
                continue;
            } catch (\Exception $e) {
                Log::warning("[Offline.responsiveimages] could not process image: " . $this->imgPath);
                continue;
            }

//            $srcSets[$source] = $responsiveImage->getSourceSet();
        }

//        return $this->domManipulator->addSrcSetAttributes($srcSets);
        return $this->html;
    }

    private function shouldWatermark() {
        $isWatermarkEnabled = $this->s->get("enable_watermark");
        if(!$isWatermarkEnabled) {
            return false;
        }

        return true;
    }


    private function getAllowedExtensions() {
        if(empty($this->allowedExtensions)) {
            $this->allowedExtensions = array_map(
                function($element){ return trim($element); },
                explode(",", $this->s->get("allowed_extensions"))
            );
        }

        return $this->allowedExtensions;
    }


    protected function checkIfProcessable($image) {
        if ( ! in_array($image->getExtension(), $this->getAllowedExtensions()) ) {
            throw new ExtensionNotAllowedException();
        }

        return true;
    }

    /**
     * Returns the absolute path for a image copy.
     *
     * @param $size
     *
     * @return string
     */
    protected function getStoragePath($size)
    {
        return $path = "/mnt/hgfs/WWW/gradnja-obnova/storage/app/uploads/public";
//        if ( ! FileHelper::isDirectory($path)) {
//            FileHelper::makeDirectory($path, 0777, true, true);
//        }
//
//        $storagePath = $path . $this->getStorageFilename($size);
//
//        $this->sourceSet->push($size, $storagePath);
//
//        return $storagePath;
    }


    /**
     * Returns the copy's filename.
     *
     * @param $size
     *
     * @return string
     */
    protected function getStorageFilename($size)
    {
        return $this->filename . '__' . $size . '.' . $this->extension;
    }


}