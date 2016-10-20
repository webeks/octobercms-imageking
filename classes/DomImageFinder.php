<?php

namespace Code200\ImageKing\Classes;

use Code200\Imageking\models\Settings;
use October\Rain\Support\Facades\Config;

class DomImageFinder
{
    /**
     * @var \DOMNodeList
     */
    public $imgNodes;

    /**
     * Loads the html.
     *
     * @param                   $html
     * @param \DOMDocument|null $dom
     */
    public function __construct($html, \DOMDocument $dom = null)
    {
        // suppress errors in case of invalid html
        libxml_use_internal_errors(true);

        if ($dom === null) {
            $this->dom = new \DOMDocument;
        }

        $this->dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $xQuery = $this->generateXpathQuery();
        $this->imgNodes = (new \DOMXPath($this->dom))->query( $xQuery );
    }

    /**
     * Prepares xPath query string based on backend settings
     * @return string
     */
    private function generateXpathQuery() {
        $parentElementClass = trim(Settings::get("main_class"), ".");
        $excludeProcessingClass = trim(Settings::get("exclude_class"), ".");

        $xQuery = "";
        if(!empty($parentElementClass)) {
            $xQuery .= "//*[contains(@class, '".$parentElementClass."')]";
        }
        $xQuery .= "//img";

        if(!empty($excludeProcessingClass)){
           $xQuery .= "[not(contains(@class, '".$excludeProcessingClass."'))]";
        }

        return $xQuery;
    }


    /**
     * Returns images found
     * @return \DOMNodeList
     */
    public function getImageNodes(){
        return $this->imgNodes;
    }

    /**
     * Returns an array of all img src attributes.
     *
     * @return array
     */
    public function getImageSources()
    {
        $images = [];

        foreach ($this->imgNodes as $node) {
            $images[] = $this->getSrcAttribute($node);
        }

        return $images;
    }

    /**
     * Normalize the image's src attribute and return it.
     *
     * @param $node
     *
     * @return mixed
     */
    public function getSrcAttribute($node)
    {
        $src = $node->getAttribute('src');
        return trim($src, '/');
    }


}