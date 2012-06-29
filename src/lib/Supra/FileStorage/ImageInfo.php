<?php

namespace Supra\FileStorage;

use Supra\ObjectRepository\ObjectRepository;

/**
 * Builds info from image entity or full path
 */
class ImageInfo
{

	/**
	 * @var Entity\Image 
	 */
	protected $image;

	/**
	 * Full system file path
	 * @var string
	 */
	protected $path;

	/**
	 * File size
	 * @var integer
	 */
	protected $size;

	/**
	 * File name with extension
	 * @var string
	 */
	protected $name;

	/**
	 * File system directory
	 * @var stirng
	 */
	protected $directory;

	/**
	 * File extension
	 * @var string
	 */
	protected $extension;

	/**
	 * Image width
	 * @var integer
	 */
	protected $width;

	/**
	 * Image height
	 * @var integer 
	 */
	protected $height;

	/**
	 * IMAGETYPE_XXX
	 * @var integer 
	 */
	protected $type;

	/**
	 * File mime
	 * @var string 
	 */
	protected $mime;

	/**
	 * Image channels 
	 * @var integer
	 */
	protected $channels;

	/**
	 * Image bits
	 * @var integer
	 */
	protected $bits;

	/**
	 * Builds info from image entity or full path
	 * @param Entity\Image or string $image 
	 */
	public function __construct($image)
	{
		if ($image instanceof Entity\Image) {
			$this->image = $image;
			$fileStorage = ObjectRepository::getFileStorage($this);
			$image = $fileStorage->getImagePath($image);
		}

		$this->process($image);
	}

	/**
	 * Builds info from image full path
	 * @param string $filePath 
	 */
	public function process($filePath)
	{
		if ( ! is_string($filePath) && ! file_exists($filePath) && ! is_readable($filePath)) {
			throw new Exception\RuntimeException('Failed to get image path');
		}

		$imageInfo = getimagesize($filePath);

		if ($imageInfo === false) {
			throw new Exception\RuntimeException('Failed to get image information from path "' . $filePath . '"');
		}

		$this->width = $imageInfo[0];
		$this->height = $imageInfo[1];
		$this->type = $imageInfo[2];
		$this->bits = $imageInfo['bits'];
		$this->channels = $imageInfo['channels'];
		$this->mime = $imageInfo['mime'];
		$this->path = $filePath;
		$this->size = filesize($filePath);
		$pathInfo = pathinfo($filePath);
		$this->name = $pathInfo['basename'];
		$this->directory = $pathInfo['dirname'];
		$this->extension = $pathInfo['extension'];
	}

	/**
	 * Returns image info in array
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'width' => $this->width,
			'height' => $this->height,
			'type' => $this->type,
			'bits' => $this->bits,
			'channels' => $this->channels,
			'mime' => $this->mime,
			'path' => $this->path,
			'size' => $this->size,
			'name' => $this->name,
			'directory' => $this->directory,
			'extension' => $this->extension,
		);
	}

	/**
	 * Returns Image entity instance if info was built from entity
	 * @return Entity\Image  
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * Returns full system file path
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Returns file size
	 * @return integer
	 */
	public function getSize()
	{
		return $this->size;
	}

	/**
	 * Returns file name with extension
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns file system directory
	 * @return stirng
	 */
	public function getDirectory()
	{
		return $this->directory;
	}

	/**
	 * Returns file extension
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * Returns image width
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * Returns image height
	 * @return integer 
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * Returns on of IMAGETYPE_XXX constants
	 * @return integer 
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Returns file mime
	 * @return string 
	 */
	public function getMime()
	{
		return $this->mime;
	}

	/**
	 * Returns image channels 
	 * @return integer
	 */
	public function getChannels()
	{
		return $this->channels;
	}

	/**
	 * Returns image bits
	 * @return integer
	 */
	public function getBits()
	{
		return $this->bits;
	}

}
