<?php

namespace Supra\Package\Cms\Pages\Response;

/**
 * Page controller response part.
 */
abstract class ResponsePart
{
	/**
	 * @var ResponseContext
	 */
	protected $context;

	/**
	 * @var array
	 */
	protected $output = array();

	/**
	 * @param string $output
	 */
	public function __construct($output = null)
	{
		if ($output) {
			$this->output($output);
		}
	}

	/**
	 * @param string $output
	 */
	public function output($output)
	{
		$this->output[] = (string) $output;
	}

	public function cleanOutput()
	{
		$this->output = array();
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return implode('', $this->output);
	}

	/**
	 * @return ResponseContext
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param ResponseContext $context
	 */
	public function setContext(ResponseContext $context)
	{
		$this->context = $context;
	}
}