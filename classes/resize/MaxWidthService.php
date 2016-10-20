<?php

namespace Code200\ImageKing\Classes\Resize;


use Code200\Eucookielawmadness\models\Settings;

class MaxWidthService
{
    private $node;

    public function __construct(\DOMElement $node)
    {
        $this->node = $node;
    }

    public function process() {
//        if(Settings::get())
        return $this->node;
    }

    private function isRelevant() {

    }
}