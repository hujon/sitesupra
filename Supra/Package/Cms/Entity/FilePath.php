<?php

namespace Supra\Package\Cms\Entity;

/**
 * @Entity
 * @Table(name="file_path")
 */
class FilePath extends Abstraction\Entity
{
	/**
	 * @Column(type="string", name="system_path", nullable=true)
	 * 
	 * @var integer
	 */
	protected $systemPath;

	/**
	 * @Column(type="string", name="web_path", nullable=true)
	 * 
	 * @var integer
	 */
	protected $webPath;

	/**
	 * Entity autogenerated ID is overrided, 
	 * so the file and file path IDs would be the same
	 * 
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Returns file system path
	 * 
	 * @return string 
	 */
	public function getSystemPath()
	{
		return $this->systemPath;
	}

	/**
	 * System path setter
	 * 
	 * @param string $systemPath
	 */
	public function setSystemPath($systemPath)
	{
		$this->systemPath = $systemPath;
	}

	/**
	 * Returns file web path
	 * 
	 * @return string
	 */
	public function getWebPath()
	{
		return $this->webPath;
	}

	/**
	 * Web path setter
	 * 
	 * @param string $webPath
	 */
	public function setWebPath($webPath)
	{
		$this->webPath = $webPath;
	}
}