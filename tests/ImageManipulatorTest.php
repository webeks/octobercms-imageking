<?php

namespace Code200\ImageKing\Tests;

use Code200\ImageKing\Classes\DomImageFinder;
use Code200\ImageKing\Classes\ImageManipulator;
use Code200\Imageking\models\Settings;
use League\Flysystem\Exception;
use October\Rain\Support\Facades\Config;
use PluginTestCase;
use File as FileHelper;

class ImageManipulatorTest extends PluginTestCase
{

    private $testImagePath;
    private $testWatermarkPath;

    public function setUp()
    {
//        $this->markTestSkipped();

        parent::setUp();
        $this->testImagePath = base_path("plugins/Code200/imageking/tests/src/main.jpg");
        $this->testWatermarkPath = base_path("plugins/Code200/imageking/tests/src/watermark.png");
    }

    public function testNotLocalFileException()
    {
        $this->setExpectedException("Code200\ImageKing\Classes\Exceptions\NotLocalFileException");

        $image = new ImageManipulator("http://php.net/images/logo.png");
    }

    public function testFileNotFoundException()
    {
        $this->setExpectedException("Code200\ImageKing\Classes\Exceptions\FileNotFoundException");
        $image = new ImageManipulator(base_path("/images/logo.png"));
    }

    public function testGetExtension()
    {
        $image = new ImageManipulator($this->testImagePath);
        $this->assertEquals("jpg", $image->getExtension());
    }

    public function testGetNewFilename()
    {
        $image = new ImageManipulator($this->testImagePath);
        $this->assertEquals("main.jpg", $image->getNewFilename());

        $image = new ImageManipulator(base_path("plugins/Code200/imageking/tests/src/weird namećč ž.,@.png"));
        $this->assertEquals("weird-namecc-z.png", $image->getNewFilename());

        $image = new ImageManipulator(base_path("plugins/Code200/imageking/tests/src/weird namećč ž.,@.png"));
        $this->assertEquals("weird-namecc-z-700.png", $image->getNewFilename(700));
    }

    public function testGetStoragePath()
    {
        $settingsValue = Settings::get("main_class");
        Settings::set('private_paths_obstruction', false);

        $testImageUploadsDir = base_path() . Config::get('cms.storage.uploads.path') . "/test.jpg";
        $testImage1 = base_path() . Config::get('cms.storage.media.path') . "/news/some-folder/test.jpg";


        //we dont have actual image so we need to mock few methods
        $getStoragePath = function($imagePath)  {

            $imageManipulatorMock = $this->getMockBuilder('Code200\ImageKing\Classes\ImageManipulator')
                ->setMethods(array('getOriginalImageFilePath', "getExtension", "__construct", "makeDirectory"))
                ->setConstructorArgs(array($imagePath))
                ->disableOriginalConstructor()
                ->getMock();
            $imageManipulatorMock
                ->expects($this->any())
                ->method("getOriginalImageFilePath")
                ->willReturn($imagePath);

            $imageManipulatorMock
                ->expects($this->any())
                ->method("getExtension")
                ->willReturn("jpg");

            return $imageManipulatorMock->getStoragePath();
        };

        $this->assertEquals(temp_path("public/uploads/test.jpg"), $getStoragePath($testImageUploadsDir));
        $this->assertEquals(temp_path("public/media/news/some-folder/test.jpg"), $getStoragePath($testImage1));

        Settings::set('private_paths_obstruction', true);
        $this->assertNotEquals(temp_path("public/uploads/test.jpg"), $getStoragePath($testImageUploadsDir));

        Settings::set('private_paths_obstruction', $settingsValue);
    }
}
