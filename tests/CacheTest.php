<?php

namespace Code200\ImageKing\Tests;


use Code200\ImageKing\Classes\ImageService;
use Code200\ImageKing\Models\Settings;
use Illuminate\Support\Facades\URL;
use October\Rain\Support\Facades\Config;
use PluginTestCase;

class CacheTest extends PluginTestCase
{
    private $testImagePathUrl;
    private $testImageFilePath;
//    private $testWatermarkPath;
//    private $testWatermarkSmallPath;
    private $testCacheImagePath;
    private $testCacheImagePath100;
    private $testCacheImageMD5;
    private $testCacheImage100MD5;
    private $settings;

    public function setUp()
    {
        parent::setUp();

        $this->testImagePathUrl = URL::to('/') . "/plugins/Code200/imageking/tests/src/cache-img.png";
        $this->testImageFilePath = base_path("plugins/Code200/imageking/tests/src/cache-img.png");

        Settings::set("main_class", "");
        Settings::set("max_width", 600);
        Settings::set("responsive_sizes", "100,200");
        Settings::set("allowed_extensions", "jpg,jpeg,png");
        Settings::set("enable_cache", true);

        //prepare fake image
        $storagePath = ($this->getImageManipulatorMock($this->testImageFilePath))->getStoragePathDir();
        $this->testCacheImagePath = $storagePath . "cache-img.png";
        $this->testCacheImagePath100 = $storagePath . "cache-img-100.png";
        $this->createImage($this->testCacheImagePath);
        $this->createImage($this->testCacheImagePath100);
        $this->testCacheImageMD5 = md5_file($this->testCacheImagePath);
        $this->testCacheImage100MD5 = md5_file($this->testCacheImagePath100);
    }

    public function tearDown()
    {
        parent::tearDown();

        unlink($this->testCacheImagePath);
        unlink($this->testCacheImagePath100);
    }


    public function testDontOverwriteMainAnd100ImgInCacheButWriteMissing200(){
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

        $storagePath = ($this->getImageManipulatorMock($this->testImageFilePath))->getStoragePathDir();

        //before processing there should be present normal and 100 thumb, but not 200
        $this->assertFileExists($this->testCacheImagePath);
        $this->assertFileExists($this->testCacheImagePath100);
        $this->assertFileNotExists($storagePath . "cache-img-200.png");

        $modifiedHtml = $imageServiceMock->process();

        //check files exist
        $this->assertFileExists($this->testCacheImagePath);
        $this->assertFileExists($this->testCacheImagePath100);
        $this->assertFileExists($storagePath . "cache-img-200.png");

        //main and 100 thumb should not be changed
        $this->assertEquals($this->testCacheImageMD5, md5_file($this->testCacheImagePath));
        $this->assertEquals($this->testCacheImage100MD5, md5_file($this->testCacheImagePath100));
        $this->assertEquals("73f7fb62b407ec5416f4193b3015fb61", md5_file($storagePath . "cache-img-200.png"));

        //now clean after this test
        unlink($storagePath . "cache-img-200.png");
    }



    private function getTestStoragePath() {
        return base_path("plugins/Code200/imageking/tests/results/");
    }


    private function getImageManipulatorMock($imagePath, $filename = null) {

        $overrideMethods = [
            "getStoragePathDir",
            "makeDirectory",
        ];

        $imageManipulatorMock = $this->getMockBuilder('Code200\ImageKing\Classes\ImageManipulator')
            ->setMethods($overrideMethods)
            ->setConstructorArgs(array($imagePath))
            ->getMock();

        $storagePath = $this->getTestStoragePath();

        $imageManipulatorMock
            ->expects($this->any())
            ->method("getStoragePathDir")
            ->willReturn($storagePath);

        return $imageManipulatorMock;
    }

    private function generateHtml() {
        return '<html><img src="'.$this->testImagePathUrl.'" /></html>';
    }

    private function createImage($imagePath) {
        $image = imagecreatetruecolor(200, 50);
        imagepng($image, $imagePath);
    }
}