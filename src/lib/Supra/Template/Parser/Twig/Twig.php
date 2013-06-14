<?php

namespace Supra\Template\Parser\Twig;

use Twig_Environment;
use Twig_LoaderInterface;
use Supra\Template\Parser\TemplateParser;

/**
 * Twig environment override
 */
class Twig extends Twig_Environment implements TemplateParser
{
	private function transactional(\Closure $closure, Twig_LoaderInterface $loader = null)
	{
		$e = null;
		$contents = null;
		$oldLoader = null;
		
		if ( ! is_null($loader)) {
			if ($this->loader !== null) {
				$oldLoader = $this->getLoader();
			}

			$this->setLoader($loader);
		}

		try {
			$contents = $closure($this);
		} catch (\Exception $e) {}

		if ( ! is_null($oldLoader)) {
			$this->setLoader($oldLoader);
		}

		if ( ! empty($e)) {
			throw $e;
		}

		return $contents;
	}
	
	/**
	 * Can pass loader to use, the old loader is backed up and restored afterwards
	 * @param string $templateName
	 * @param array $templateParameters
	 * @param Twig_LoaderInterface $loader
	 * @return string
	 */
	public function parseTemplate($templateName, array $templateParameters = array(), Twig_LoaderInterface $loader = null)
	{
		$closure = function(Twig $self) use ($templateName, $templateParameters) {
			$template = $self->loadTemplate($templateName);
			$contents = $template->render($templateParameters);
			
			return $contents;
		};
		
		$contents = $this->transactional($closure, $loader);
		
		return $contents;
	}
	
	/**
	 * Returns template filename if uses filesystem loader
	 * @param string $templateName
	 * @param Twig_LoaderInterface $loader
	 * @return string
	 */
	public function getTemplateFilename($templateName, Twig_LoaderInterface $loader = null)
	{
		$closure = function(Twig $self) use ($templateName) {
			$loader = $self->getLoader();
			
			if ( ! $loader instanceof \Twig_Loader_Filesystem) {
				return;
			}
			
			$filename = $loader->getCacheKey($templateName);
			
			return $filename;
		};
		
		$filename = $this->transactional($closure, $loader);
		
		return $filename;
	}

	/**
	 * Overriden just to set file permission mode
	 * @param string $file
	 * @param string $content
	 */
	protected function writeCacheFile($file, $content)
	{
		parent::writeCacheFile($file, $content);

		chmod($file, SITESUPRA_FILE_PERMISSION_MODE);
	}

}
