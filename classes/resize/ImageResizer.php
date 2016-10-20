<?php

namespace Code200\ImageKing\Classe\Resize;

use October\Rain\Database\Attach\Resizer;

/**
 * Class ImageResizer
 */
class ImageResizer extends Resizer
{
    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }
}