<?php
namespace Code200\ImageKing\Widgets;


use Backend\Classes\FormWidgetBase;

class Cache extends FormWidgetBase
{

    /**
     * @var string A unique alias to identify this widget.
     */
    protected $defaultAlias = 'cache';

    public function render()
    {
        return $this->makePartial('clear_tmp_files');
    }

    public function onClearTmpFiles() {
        $tmpFolder = temp_path("public/");
        $iterator = new \RecursiveDirectoryIterator($tmpFolder);

        $folderIterator = new \RecursiveDirectoryIterator( $tmpFolder, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS );
        foreach( new \RecursiveIteratorIterator($folderIterator,
                     \RecursiveIteratorIterator::CHILD_FIRST ) as $value ) {
            $value->isFile() ? unlink( $value ) : rmdir( $value );
        }
    }
}