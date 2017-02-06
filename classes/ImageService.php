<?php namespace Code200\ImageKing\Classes;

use Cms\Helpers\File;
use Code200\ImageKing\Classes\Exceptions\ExtensionNotAllowedException;
use Code200\Imageking\models\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ImageService
{
    /**
     * @var string
     */
    private $html;

    /**
     * @var DomManipulator
     */
    private $domImageFinder;

    /**
     * Filepath of current image
     * @var string
     */
    private $imageFilePath;

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
     * Responsive sizes
     * @var array
     */
    private $responsiveSizes;





    /**
     * ImageService constructor.
     * @param string $html
     */
    public function __construct($html)
    {
        $this->html = $html;
        $this->setDomImageFinder();
        $this->s = Settings::instance();
    }


    protected function setDomImageFinder() {
        $this->domImageFinder = new DomImageFinder($this->html);
    }


    /**
     * Process images and returns modified HTML containing images.
     *
     * @return string
     */
    public function process()
    {
        $imageNodes = $this->domImageFinder->getImageNodes();

        foreach ($imageNodes as $node) {
            try {
                //get image
                $this->imageFilePath = $this->getFilePathFromNode($node);
                $image = $this->getNewImageManipulator($this->imageFilePath);
                $this->checkIfProcessable($image);


                //check cache
                $newMainImagePath = $image->getStoragePath();
                if(!file_exists($newMainImagePath) || empty(Settings::get("enable_cache", 0)) ) {
                    $imgChanged = $this->processMainImage($image);
                }

                $node->setAttribute("src", $image->getPublicUrl($newMainImagePath));

                //responsive versions
                $this->prepareResponsiveVersions($this->imageFilePath, $node);

                //captions
                $this->applyCaptions($node);

            } catch (\RemotePathException $e) {
                //we simply cant and dont want to process remote images ...
                continue;
            } catch (ExtensionNotAllowedException $e) {
                //we dont want to process certain files I guess
                continue;
            } catch (\Exception $e) {
                Log::warning("[Code200.ImageKing] could not process image: " . $this->imageFilePath);
                continue;
            }
        }

        return $this->domImageFinder->dom->saveHTML();
    }

    private function processMainImage(&$image) {
        $imgChanged = false;

        //limit its output size in case we dont want to share sources
        $maxWidth = $this->s->get("max_width");
        if (!empty($maxWidth) && $maxWidth < $image->getWidth() ) {
            $image->resize($maxWidth, null);
            $imgChanged = true;
        }

        //watermark
        if ($this->shouldWatermark()) {
            $image->applyWatermark();
            $imgChanged = true;
        }

        if($imgChanged || $this->s->get("enable_private_paths")) {
            $newMainImagePath = $image->getStoragePath();
            $image->save($newMainImagePath);
//            $node->setAttribute("src", $image->getPublicUrl($newMainImagePath));
        }

        return $imgChanged;
    }

    /**
     * Return new image manipulator object
     * @param $imagePath
     * @return ImageManipulator
     */
    protected function getNewImageManipulator($imagePath){
        return new ImageManipulator($imagePath);
    }

    /**
     * Is watermarking enabled in settings
     * @return bool
     */
    private function shouldWatermark()
    {
        $isWatermarkEnabled = $this->s->get("enable_watermark");
        if (!$isWatermarkEnabled) {
            return false;
        }
        return true;
    }


    private function applyCaptions(&$node){

        if(empty($this->s->get("enable_captions")) && ! empty($node->getAttribute("title"))){
            return;
        }

        $figureElement = $this->domImageFinder->dom->createElement("figure");
        $captionElement = $this->domImageFinder->dom->createElement("figcaption", $node->getAttribute("title"));

        $node->parentNode->replaceChild($figureElement, $node);
        $figureElement->appendChild($node);
        $figureElement->appendChild($captionElement);
    }

    /**
     * Returns array of allowed image extensions to be manipulated
     * @return array string
     */
    private function getAllowedExtensions()
    {
        if (empty($this->allowedExtensions)) {
            $this->allowedExtensions = array_map(
                function ($element) {
                    return trim($element);
                },
                explode(",", $this->s->get("allowed_extensions"))
            );
        }

        return $this->allowedExtensions;
    }


    /**
     * Returns int array of responsive sizes
     * @return array int
     */
    private function getResponsiveSizes()
    {
        if (empty($this->responsiveSizes)) {
            if(!empty($this->s->get("responsive_sizes"))) {
                //fetch from settings
                $this->responsiveSizes = array_map(
                    function ($el) {
                        if (empty($el)) {
                            return null;
                        }

                        return (int)trim($el);
                    },
                    explode(",", $this->s->get("responsive_sizes"))
                );
                //limit max size to settings
                $this->responsiveSizes = array_filter($this->responsiveSizes, function($el){
                    if(!empty($el) && (empty($this->getMaxWidth()) || $this->getMaxWidth() >= $el)) {
                        return true;
                    }
                });
            } else {
                $this->responsiveSizes = array();
            }
        }
        return $this->responsiveSizes;
    }

    /**
     * Checks if image is processable
     * @param ImageManipulator $image
     * @return bool
     */
    protected function checkIfProcessable($image)
    {
        if (!in_array($image->getExtension(), $this->getAllowedExtensions())) {
            throw new ExtensionNotAllowedException("Extension not allowed.");
        }
        return true;
    }



    /**
     * @param $imagePath
     * @param $node
     * @return ImageManipulator
     * @throws \Exception
     */
    private function prepareResponsiveVersions($imagePath, &$node)
    {
        $srcSetAttributes = array();
        foreach ($this->getResponsiveSizes() as $newSize) {

            $image = $this->getNewImageManipulator($imagePath);
            $newPath = $image->getStoragePath($newSize);
            if( !file_exists($newPath) || empty(Settings::get("enable_cache", false))){
                $image->resize($newSize, null);

                if($this->shouldWatermark()){
                    $image->applyWatermark();
                }

//                $newPath = $image->getStoragePath($newSize);
                $image->save($newPath);
                $srcSetAttributes[] = sprintf('%s %sw', $image->getPublicUrl($newPath), $newSize);

            } else {
                $srcSetAttributes[] = sprintf('%s %sw', $image->getPublicUrl($newPath), $newSize);
            }
        }

        $node->setAttribute('srcset', implode(",", $srcSetAttributes));
    }

    /**
     * Gets max (limit) image width from settings
     * @return int
     */
    private function getMaxWidth(){
        return (int)trim($this->s->get("max_width"));
    }


    /**
     * Remove the local host name from path src and add the base path.
     *
     * @param $node
     * @return mixed
     */
    protected function getFilePathFromNode($node)
    {
        $imagePath = rawurldecode($this->domImageFinder->getSrcAttribute($node));
        return str_replace(URL::to('/'), '', base_path($imagePath));
    }




}