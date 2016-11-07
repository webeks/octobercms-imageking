<?php

namespace Code200\ImageKing\Tests;


use Code200\ImageKing\Classes\ImageService;
use Code200\ImageKing\Models\Settings;
use Illuminate\Support\Facades\URL;
use October\Rain\Support\Facades\Config;
use PluginTestCase;

class FunctionalTest extends PluginTestCase
{
    private $testImagePathUrl;
    private $testImageFilePath;
    private $testWatermarkPath;
    private $testWatermarkSmallPath;
    private $settings;

    public function setUp()
    {
        parent::setUp();
        $this->testImagePathUrl = URL::to('/') . "/plugins/Code200/imageking/tests/src/main.jpg";
        $this->testImageFilePath = base_path("plugins/Code200/imageking/tests/src/main.jpg");
        $this->testWatermarkPath = base_path("plugins/Code200/imageking/tests/src/watermark.png");
        $this->testWatermarkSmallPath = base_path("plugins/Code200/imageking/tests/src/watermark-small.png");

        Settings::set("main_class", "");
        Settings::set("max_width", 600);
        Settings::set("responsive_sizes", "100,200");
        Settings::set("allowed_extensions", "jpg,jpeg");
    }


    public function testMaxWidthResponsive(){
        Settings::set("enable_watermark", false);

        Settings::set("private_paths_obstruction", false);

        $imageServiceMock = $this->getMockBuilder('Code200\ImageKing\Classes\ImageService')
            ->setMethods(array("getNewImageManipulator"))
            ->setConstructorArgs(array($this->generateHtml()))
            ->getMock();

        //main img resize
        $imageServiceMock->expects($this->at(0))
            ->method("getNewImageManipulator")
            ->willReturn($this->getImageManipulatorMock($this->testImageFilePath));
        //100px
        $imageServiceMock->expects($this->at(1))
            ->method("getNewImageManipulator")
            ->willReturn($this->getImageManipulatorMock($this->testImageFilePath));
        //200px
        $imageServiceMock->expects($this->at(2))
            ->method("getNewImageManipulator")
            ->willReturn($this->getImageManipulatorMock($this->testImageFilePath));

        $modifiedHtml = $imageServiceMock->process();

        $storagePath = ($this->getImageManipulatorMock($this->testImageFilePath))->getStoragePathDir();

        //check files exist
        $this->assertFileExists($storagePath . "main.jpg");
        $this->assertFileExists($storagePath . "main-100.jpg");
        $this->assertFileExists($storagePath . "main-200.jpg");

        list($width, $height) = getimagesize($storagePath . "main.jpg");
        $this->assertEquals(600, $width);
        list($width, $height) = getimagesize($storagePath . "main-100.jpg");
        $this->assertEquals(100, $width);
        list($width, $height) = getimagesize($storagePath . "main-200.jpg");
        $this->assertEquals(200, $width);
    }


    public function testWatermark() {
        Settings::set("responsive_sizes", "100,200");
        Settings::set("enable_watermark", true);
        Settings::set("watermark_small_limit",  201);
        Settings::set("nowatermark_limit",  101);
        Settings::set("watermark_size_small",  10);
        Settings::set("watermark_position_x_small",  20);
        Settings::set("watermark_position_y_small",  20);
        Settings::set("watermark_size",  10);
        Settings::set("watermark_position_x",  0);
        Settings::set("watermark_position_y",  0);


        $imageServiceMock = $this->getMockBuilder('Code200\ImageKing\Classes\ImageService')
            ->setMethods(array("getNewImageManipulator"))
            ->setConstructorArgs(array($this->generateHtml()))
            ->getMock();

        //main img resize
        $imageServiceMock->expects($this->at(0))
            ->method("getNewImageManipulator")
            ->willReturn($this->getImageManipulatorMock($this->testImageFilePath, "main-watermark-top-left"));

        $imageServiceMock->expects($this->at(1))
            ->method("getNewImageManipulator")
            ->willReturn($this->getImageManipulatorMock($this->testImageFilePath, "main-100-no-watermark"));

        $imageServiceMock->expects($this->at(2))
            ->method("getNewImageManipulator")
            ->willReturn($this->getImageManipulatorMock($this->testImageFilePath, "main-200-watermark-small"));

        $modifiedHtml = $imageServiceMock->process();


        $this->notifyWhereToFindTestProducedImages();
    }


    public function testWatermarkBottomRight(){
        Settings::set("responsive_sizes", "");
        Settings::set("enable_watermark", true);
        Settings::set("watermark_size",  10);
        Settings::set("watermark_position_x",  -1);
        Settings::set("watermark_position_y",  -1);

        $imageServiceMock = $this->getMockBuilder('Code200\ImageKing\Classes\ImageService')
            ->setMethods(array("getNewImageManipulator"))
            ->setConstructorArgs(array($this->generateHtml()))
            ->getMock();

        $imageServiceMock->expects($this->at(0))
            ->method("getNewImageManipulator")
            ->willReturn($this->getImageManipulatorMock($this->testImageFilePath, "main-watermark-bottom-right"));

        $modifiedHtml = $imageServiceMock->process();
    }



    private function getTestStoragePath() {
        return base_path("plugins/Code200/imageking/tests/results/");
    }

    private function notifyWhereToFindTestProducedImages() {
        echo "\n==============================\n";
        echo "see functional tests export images in tests/results folder\n";
        echo "==============================\n";
    }

    private function getImageManipulatorMock($imagePath, $filename = null) {

        $overrideMethods = [
            "getStoragePathDir",
            "makeDirectory",
            "getSmallWatermarkFilePath",
            "getWatermarkFilePath"
        ];

        if(!empty($filename)) {
            $overrideMethods[] = "getFilename";
        }

        $imageManipulatorMock = $this->getMockBuilder('Code200\ImageKing\Classes\ImageManipulator')
            ->setMethods($overrideMethods)
            ->setConstructorArgs(array($imagePath))
            ->getMock();

        $storagePath = $this->getTestStoragePath();

        $imageManipulatorMock
            ->expects($this->any())
            ->method("getStoragePathDir")
            ->willReturn($storagePath);

        $imageManipulatorMock
            ->expects($this->any())
            ->method("getSmallWatermarkFilePath")
            ->willReturn($this->testWatermarkSmallPath);

        $imageManipulatorMock
            ->expects($this->any())
            ->method("getWatermarkFilePath")
            ->willReturn($this->testWatermarkPath);

        if(!empty($filename)) {
            $imageManipulatorMock
                ->expects($this->any())
                ->method("getFilename")
                ->willReturn($filename);
        }

        return $imageManipulatorMock;
    }

    private function generateHtml() {
        return '<html><img src="'.$this->testImagePathUrl.'" /></html>';
    }
}