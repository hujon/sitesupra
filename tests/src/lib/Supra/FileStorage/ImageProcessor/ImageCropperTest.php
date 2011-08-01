<?php

namespace Supra\FileStorage\ImageProcessor;

require_once dirname(__FILE__) . '/../../../../../../src/lib/Supra/FileStorage/ImageProcessor/ImageCropper.php';

/**
 * Test class for ImageCropper.
 * Generated by PHPUnit on 2011-08-01 at 12:07:01.
 */
class ImageCropperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ImageCropper
	 */
	protected $object;

	/**
	 * @var string
	 */
	protected $imagePath;

	/**
	 * @var int
	 */
	protected $imageWidth;

	/**
	 * @var int
	 */
	protected $imageHeight;

	/**
	 *
	 * @var string
	 */
	protected $outputPath;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->object = new ImageCropper;
		$this->imagePath = __DIR__ . '/../chuck.jpg';
		$imageInfo = getimagesize($this->imagePath);
		$this->imageWidth = $imageInfo[0];
		$this->imageHeight = $imageInfo[1];
		$this->outputPath = __DIR__ . '/out.' . pathinfo($this->imagePath, PATHINFO_EXTENSION);
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		unlink($this->outputPath);
	}

	/**
	 * testSetLeft()
	 * Formal setter test to check that both valid and invalid values do not fail.
	 */
	public function testSetLeft() {
		$return = $this->object->setLeft(100);
		$this->assertEquals($this->object, $return);
		$return = $this->object->setLeft('dasda');
		$this->assertEquals($this->object, $return);
	}

	/**
	 * testSetTop()
	 * Formal setter test to check that both valid and invalid values do not fail.
	 */
	public function testSetTop() {
		$return = $this->object->setTop(50);
		$this->assertEquals($this->object, $return);
		$return = $this->object->setLeft('dfsdkjhgn');
		$this->assertEquals($this->object, $return);
	}

	/**
	 * testSetRight()
	 * Formal setter test to check that both valid and invalid values do not fail.
	 */
	public function testSetRight() {
		$return = $this->object->setRight(-100);
		$this->assertEquals($this->object, $return);
		$return = $this->object->setRight('gjiwenvw');
		$this->assertEquals($this->object, $return);
	}

	/**
	 * testSetBottom()
	 * Formal setter test to check that both valid and invalid values do not fail.
	 */
	public function testSetBottom() {
		$return = $this->object->setBottom(-25);
		$this->assertEquals($this->object, $return);
		$return = $this->object->setBottom('ewfqwegn');
		$this->assertEquals($this->object, $return);
	}

	/**
	 * testSetWidth()
	 * Formal setter test to check that both valid and invalid values do not fail.
	 */
	public function testSetWidth() {
		$return = $this->object->setWidth(500);
		$this->assertEquals($this->object, $return);
		$return = $this->object->setWidth('rogmwerb');
		$this->assertEquals($this->object, $return);
	}

	/**
	 * testSetHeight()
	 * Formal setter test to check that both valid and invalid values do not fail.
	 */
	public function testSetHeight() {
		$return = $this->object->setHeight(400);
		$this->assertEquals($this->object, $return);
		$return = $this->object->setHeight('fvnasdkfna');
		$this->assertEquals($this->object, $return);
	}

	/**
	 * Test process when source file not found
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Source image
	 */
	public function testProcessSourceNotFound() {
		$this->object
				->setSourceFile('this-file-does-not-exist.lol')
				->setOutputFile('output-here.out')
				->setLeft(10)
				->setTop(10)
				->setBottom(-10)
				->setRight(-10);
		$this->object->process();
	}

	/**
	 * Test process when left position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Left offset
	 */
	public function testProcessLeftInvalid() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft($this->imageWidth + 10)
				->setTop(10)
				->setBottom(-10)
				->setRight(-10);
		$this->object->process();
	}

	/**
	 * Test process when left position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Left offset
	 */
	public function testProcessLeftInvalid2() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(-10)
				->setTop(10)
				->setBottom(-10)
				->setRight(-10);
		$this->object->process();
	}

	/**
	 * Test process when right position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Right offset
	 */
	public function testProcessRightInvalid() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(10)
				->setBottom(-10)
				->setRight(-$this->imageWidth - 100);
		$this->object->process();
	}

	/**
	 * Test process when right position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Right offset
	 */
	public function testProcessRightInvalid2() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(10)
				->setBottom(-10)
				->setRight($this->imageWidth + 500);
		$this->object->process();
	}

	/**
	 * Test process when top position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Top offset
	 */
	public function testProcessTopInvalid() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(-10)
				->setBottom(-10)
				->setRight(-10);
		$this->object->process();
	}

	/**
	 * Test process when top position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Top offset
	 */
	public function testProcessTopInvalid2() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop($this->imageWidth + 100)
				->setBottom(-10)
				->setRight(-10);
		$this->object->process();
	}

	/**
	 * Test process when bottom position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Bottom offset
	 */
	public function testProcessBottomInvalid() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(10)
				->setBottom(-$this->imageHeight - 100)
				->setRight(-10);
		$this->object->process();
	}

	/**
	 * Test process when bottom position is out of border
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Bottom offset
	 */
	public function testProcessBottomInvalid2() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(10)
				->setBottom($this->imageHeight + 100)
				->setRight(-10);
		$this->object->process();
	}

	/**
	 * Test process when crop width exceeds original
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Crop width
	 */
	public function testProcessWidthInvalid() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(10)
				->setWidth($this->imageWidth * 2)
				->setHeight($this->imageHeight - 10);
		$this->object->process();
	}

	/**
	 * Test process when crop width + left position exceeds original
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Crop width
	 */
	public function testProcessWidthInvalid2() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(20)
				->setTop(10)
				->setWidth($this->imageWidth - 10)
				->setHeight($this->imageHeight - 10);
		$this->object->process();
	}

	/**
	 * Test process when width + left equals original width (& height + top)
	 */
	public function testProcessWidthInvalid3() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(10)
				->setWidth($this->imageWidth - 10)
				->setHeight($this->imageHeight - 10);
		$this->object->process();
	}

	/**
	 * Test process when crop height exceeds original
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Crop height
	 */
	public function testProcessHeightInvalid() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(10)
				->setWidth($this->imageWidth - 10)
				->setHeight($this->imageHeight * 2);
		$this->object->process();
	}

	/**
	 * Test process when crop height + top position exceeds original
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Crop height 
	 */
	public function testProcessHeightInvalid2() {
		$this->object
				->setSourceFile($this->imagePath)
				->setOutputFile($this->outputPath)
				->setLeft(10)
				->setTop(20)
				->setWidth($this->imageWidth - 10)
				->setHeight($this->imageHeight - 10);
		$this->object->process();
	}

	/**
	 * Test process when output file is not set
	 * 
	 * @expectedException         \Exception
	 * @expectedExceptionMessage  Target
	 */
	public function testProcessOutputNotSet() {
		$this->object
				->setSourceFile($this->imagePath)
				->setLeft(10)
				->setRight(10)
				->setWidth(10)
				->setHeight(10);
		$this->object->process();
	}

	/**
	 * Test process with various valid parameters
	 */
	public function testProcess() {
		
		/* width and height setters */
		
		$sizeCases = array(
			array(
				'l' => 10,
				't' => 10,
				'w' => 10,
				'h' => 10
			),
			array(
				'l' => 100,
				't' => 100,
				'w' => 55,
				'h' => 44
			),
			array(
				'l' => 0,
				't' => 0,
				'w' => 56,
				'h' => $this->imageHeight
			),
	
		);

		foreach ($sizeCases as $case) {	
			$this->object
					->setSourceFile($this->imagePath)
					->setOutputFile($this->outputPath)
					->setLeft($case['l'])
					->setTop($case['t'])
					->setWidth($case['w'])
					->setHeight($case['h']);
			$this->object->process();
			$this->assertFileExists($this->outputPath);
			$size = getimagesize($this->outputPath);
			$this->assertEquals($case['w'], $size[0]);
			$this->assertEquals($case['h'], $size[1]);
			unlink($this->outputPath);
		}

		
		/* right and bottom setters */
		
		$sizeCases = array(
			array(
				'l' => 10,
				't' => 10,
				'r' => 10,
				'b' => 10
			),
			array(
				'l' => 10,
				't' => 10,
				'r' =>  - ($this->imageWidth - 10) + 1,
				'b' => - ($this->imageHeight - 10) + 1
			),
			array(
				'l' => 10,
				't' => 10,
				'r' => -10,
				'b' => -10
			),
		);

		foreach ($sizeCases as $case) {	
			$this->object
					->setSourceFile($this->imagePath)
					->setOutputFile($this->outputPath)
					->setLeft($case['l'])
					->setTop($case['t'])
					->setRight($case['r'])
					->setBottom($case['b']);
			$this->object->process();
			$this->assertFileExists($this->outputPath);
			$size = getimagesize($this->outputPath);
			$expectedW = ($case['r'] < 0 ? $this->imageWidth + $case['r'] - 1 : $case['r']) - $case['l'] + 1;
			$expectedH = ($case['b'] < 0 ? $this->imageHeight + $case['b'] - 1 : $case['b']) - $case['t'] + 1;
			$this->assertEquals($expectedW, $size[0]);
			$this->assertEquals($expectedH, $size[1]);
			unlink($this->outputPath);
		}
	}

	/**
	 * @todo Implement testCrop().
	 */
	public function testCrop() {
		// Method crop() is alternate id for process()
		$this->markTestSkipped('See testProcess()');
	}

	/**
	 * @todo Implement testReset().
	 * Formal method test to check that it does not fail.
	 */
	public function testReset() {
		$this->assertNull($this->object->reset());
	}

}

?>
