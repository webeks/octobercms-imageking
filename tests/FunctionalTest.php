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
        Settings::set("enable_cache", false);
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
        $this->assertEquals("bfbacda9cb4906fb45df9b530f8e541c", md5_file($storagePath . "main.jpg"));
        $this->assertFileExists($storagePath . "main-100.jpg");
        $this->assertEquals("29ce490b0f3a22cd8e1696aadca99ae4", md5_file($storagePath . "main-100.jpg"));
        $this->assertFileExists($storagePath . "main-200.jpg");
        $this->assertEquals("cf82fed70356abcbb6079116b827aa05", md5_file($storagePath . "main-200.jpg"));


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

        $storagePath = ($this->getImageManipulatorMock($this->testImageFilePath))->getStoragePathDir();
        $this->assertFileExists($storagePath . "main-watermark-top-left.jpg");
        $this->assertEquals("22ac6cd3244ea60c87c955defc6eda99", md5_file($storagePath . "main-watermark-top-left.jpg"));

        $this->assertFileExists($storagePath . "main-100-no-watermark-100.jpg");
        $this->assertEquals("29ce490b0f3a22cd8e1696aadca99ae4", md5_file($storagePath . "main-100-no-watermark-100.jpg"));

        $this->assertFileExists($storagePath . "main-200-watermark-small-200.jpg");
        $this->assertEquals("c31585732f27b60c8cd40d099aa4ac94", md5_file($storagePath . "main-200-watermark-small-200.jpg"));

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

        $storagePath = ($this->getImageManipulatorMock($this->testImageFilePath))->getStoragePathDir();
        $this->assertFileExists($storagePath . "main-watermark-bottom-right.jpg");
        $this->assertEquals("b20d0344519ecd2021de928a88a38302", md5_file($storagePath . "main-watermark-bottom-right.jpg"));
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