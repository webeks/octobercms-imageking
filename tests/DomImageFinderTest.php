<?php

namespace Code200\ImageKing\Tests;

use Code200\ImageKing\Classes\DomImageFinder;
use Code200\Imageking\models\Settings;
use PluginTestCase;

class DomImageFinderTest extends PluginTestCase
{


    public function testGetImageSources()
    {
        $html = '<html><img src="test.jpg"><img src="test2.gif" class="something" /><img src="test3.png"></html>';
        $manipulator = new DomImageFinder($html);
        $sources     = $manipulator->getImageSources();

        $this->assertCount(3, $sources);
        $this->assertEquals(['test.jpg', 'test2.gif', 'test3.png'], $sources);
    }


    public function testGetImageSourcesFromMainClassHolder() {
        $settingsValue = Settings::get("main_class");
        Settings::set('main_class', "imgHolder");

        $html = '<html><img src="test1.jpg" /><div class="class1 imgHolder class3"><img src="test.jpg"><img src="test2.jpg" /></div><img src="test3.jpg"></html>';
        $manipulator = new DomImageFinder($html);
        $sources     = $manipulator->getImageSources();

        $this->assertCount(2, $sources);
        $this->assertEquals(['test.jpg', 'test2.jpg'], $sources);

        //clean after this test
        Settings::set('main_class', $settingsValue);
    }

    public function testExcludeClass() {
        $settingsValue = Settings::get("exclude_class");
        Settings::set('exclude_class', "noProcess");

        $html = '<html><img src="test.jpg" class="rounded noProcess main"><img src="test2.gif" class="main" /><img src="test3.png"></html>';
        $manipulator = new DomImageFinder($html);
        $sources     = $manipulator->getImageSources();
        $this->assertCount(2, $sources);
        $this->assertEquals(['test2.gif', 'test3.png'], $sources);

        //clean after this test
        Settings::set("exclude_class", $settingsValue);
    }
}

