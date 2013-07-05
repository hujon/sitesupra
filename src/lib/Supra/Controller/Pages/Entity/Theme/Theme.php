<?php

namespace Supra\Controller\Pages\Entity\Theme;

use Supra\Database;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Layout\Theme\ThemeInterface;
use Supra\Less\SupraLessC;
use Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfiguration;
use Supra\Controller\Layout\Theme\Configuration\ThemeConfigurationLoader;
use Supra\Configuration\Parser\YamlParser;
use Supra\Controller\Layout\Exception;
use Supra\Controller\Pages\Entity\Theme\ThemeParameterSet;

/**
 * @Entity
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @InheritanceType("SINGLE_TABLE")
 * @DetachedDiscrimintators
 * @DetachedDiscriminatorValue("theme")
 * @Table(uniqueConstraints={@UniqueConstraint(name="unique_name_idx", columns={"name"})}))
 */
class Theme extends Database\Entity implements ThemeInterface
{

	const PATH_PART_GENERATED_CSS = 'generatedCss';
	const PATH_PART_LAYOUTS = 'layouts';
	const DEFAULT_PARAMETER_SET_NAME = 'default';

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $enabled;

	/**
	 * @Column(type="string")
	 * @var string 
	 */
	protected $title;

	/**
	 * @Column(type="string");
	 * @var string
	 */
	protected $rootDir;

	/**
	 * @Column(type="string")
	 * @var string 
	 */
	protected $description;

	/**
	 * @Column(type="string")
	 * @var string 
	 */
	protected $configMd5;

	/**
	 * @OneToMany(targetEntity="ThemeLayout", mappedBy="theme", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var Arraycollection
	 */
	protected $layouts;

	/**
	 * @OneToMany(targetEntity="ThemeParameterSet", mappedBy="theme", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $parameterSets;

	/**
	 * @OneToMany(targetEntity="Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction", mappedBy="theme", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var ArrayCollection
	 */
	protected $parameters;

	/**
	 * @OneToOne(targetEntity="ThemeParameterSet")
	 * @JoinColumn(name="active_parameter_set_id", referencedColumnName="id")
	 * @var ThemeParameterSet
	 */
	protected $activeParameterSet;

	/**
	 * @var ThemeParameterSet
	 */
	protected $currentParameterSet;

	/**
	 * @Column(type="string");
	 * @var string
	 */
	protected $urlBase;
	
	/**
	 * @OneToMany(targetEntity="ThemePlaceholderGroupLayout", mappedBy="theme", cascade={"all"}, orphanRemoval=true, indexBy="name")
	 * @var Arraycollection
	 */
	protected $placeholderGroupLayouts;
	
	/**
	 * @var array
	 */
	private $currentParameterSetOuptutValues;

	
	public function __construct()
	{
		parent::__construct();

		$this->parameters = new ArrayCollection();
		$this->parameterSets = new ArrayCollection();
		$this->layouts = new ArrayCollection();
		
		$this->placeholderGroupLayouts = new ArrayCollection();
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name 
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return 'Look, master, we made some description-nama for theme "' . $this->name . '"!';
	}

	/**
	 * @param string $description 
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return string 
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title 
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @param string $rootDir 
	 */
	public function setRootDir($rootDir)
	{
		$rootDir = str_replace(SUPRA_PATH, '{SUPRA_PATH}', $rootDir);

		$this->rootDir = preg_replace('@/+@', '/', $rootDir);
	}

	/**
	 * @return string
	 */
	public function getRootDir()
	{
		$rootDir = str_replace('{SUPRA_PATH}', SUPRA_PATH, $this->rootDir);

		return $rootDir;
	}

	/**
	 * @return string
	 */
	public function getGeneratedCssDir()
	{
		return $this->getRootDir() . DIRECTORY_SEPARATOR . self::PATH_PART_GENERATED_CSS;
	}

	/**
	 * @param string $urlBase 
	 */
	public function setUrlBase($urlBase)
	{
		$this->urlBase = preg_replace('@/+@', '/', $urlBase . DIRECTORY_SEPARATOR);
	}

	/**
	 * @return string
	 */
	public function getUrlBase()
	{
		return $this->urlBase;
	}

	/**
	 * @return string
	 */
	public function getGeneratedCssUrlBase()
	{
		return $this->getUrlBase() . self::PATH_PART_GENERATED_CSS . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return boolean
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * @param boolean $enabled 
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = $enabled;
	}

	/**
	 * @return string
	 */
	public function getConfigMd5()
	{
		return $this->configMd5;
	}

	/**
	 * @param string $configMd5 
	 */
	public function setConfigMd5($configMd5)
	{
		$this->configMd5 = $configMd5;
	}

	/**
	 * @return ArrayCollection
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * @param ThemeParameterAbstraction $parameter 
	 */
	public function addParameter(ThemeParameterAbstraction $parameter)
	{
		$parameter->setTheme($this);
		$this->parameters[$parameter->getName()] = $parameter;
	}

	/**
	 * @param ThemeParameterAbstraction $parameter 
	 */
	public function removeParameter(ThemeParameterAbstraction $parameter)
	{
		$parameter->setTheme(null);

		$this->parameters->removeElement($parameter);
	}

	/**
	 * @return array
	 */
	public function getCurrentParameterSetOutputValues()
	{
		if (empty($this->currentParameterSetOuptutValues)) {

			$currentParameterSet = $this->getCurrentParameterSet();

			$outputValues = $currentParameterSet->getOutputValues();

			$outputValues['name'] = $this->getName();

			$outputValues['urlBase'] = $this->getUrlBase();

			$outputValues['generatedCssUrl'] = $this->getCurrentGeneratedCssUrl();

			$outputValues['parameterSetName'] = $currentParameterSet->getName();
			
			$outputValues['googleFonts'] = $currentParameterSet->collectGoogleFontFamilies();

			$this->currentParameterSetOuptutValues = $outputValues;
		}

		return $this->currentParameterSetOuptutValues;
	}

	/**
	 * 
	 */
	public function generateCssFiles()
	{
		foreach ($this->parameterSets as $parameterSet) {
			/* @var $parameterSet ThemeParameterSet */
			$this->generateCssFileFromLess($parameterSet);
		}
	}

	/**
	 * @param ThemeParameterSet $parameterSet 
	 */
	protected function generateCssFileFromLess(ThemeParameterSet $parameterSet)
	{
		if ( ! file_exists($this->getRootDir() . DIRECTORY_SEPARATOR . 'theme.less')) {
			return;
		}

		$lessc = new SupraLessC($this->getRootDir() . DIRECTORY_SEPARATOR . 'theme.less');

		$lessc->setRootDir($this->getRootDir());

		$valuesForLess = $parameterSet->getOutputValuesForLess();

		$cssContent = $lessc->parse(null, $valuesForLess);

		$this->writeGenetratedCssToFile($parameterSet, $cssContent);
	}

	/**
	 * @param ThemeParameterSet $parameterSet
	 * @param string $content
	 * @throws Exception\RuntimeException 
	 */
	protected function writeGenetratedCssToFile(ThemeParameterSet $parameterSet, $content)
	{
		$cssFilename = $this->getGeneratedCssFilename($parameterSet);

		$result = file_put_contents($cssFilename, $content);

		if ($result === false) {
			throw new Exception\RuntimeException('Could not write theme CSS file to "' . $cssFilename . '".');
		}
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getGeneratedCssBasename(ThemeParameterSet $parameterSet)
	{
		$parameterSetName = $parameterSet->getName();

		return $this->getName() . '_' . $parameterSetName . '.css';
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getGeneratedCssFilename(ThemeParameterSet $parameterSet)
	{
		return $this->getGeneratedCssDir() . DIRECTORY_SEPARATOR . $this->getGeneratedCssBasename($parameterSet);
	}

	/**
	 * @param string $parameterSetName
	 * @return string
	 */
	protected function getGeneratedCssUrl(ThemeParameterSet $parameterSet)
	{
		$url = $this->getGeneratedCssUrlBase() . $this->getGeneratedCssBasename($parameterSet);
		$valuesHash = $parameterSet->getLessParameterValuesHash();
		
		return $url . ( ! empty($valuesHash) ? '?' . $valuesHash : '');
	}

	/**
	 * @return string
	 */
	public function getCurrentGeneratedCssUrl()
	{
		return $this->getGeneratedCssUrl($this->getCurrentParameterSet());
	}

	/**
	 * @return string
	 */
	public function getCurrentGeneratedCssFilename()
	{
		return $this->getGeneratedCssFilename($this->getCurrentParameterSet());
	}

	/**
	 * @return ThemeParameterSet
	 */
	public function getCurrentParameterSet()
	{
		if (empty($this->currentParameterSet)) {
			$this->currentParameterSet = $this->getActiveParameterSet();
		}

		if (empty($this->currentParameterSet)) {

			$this->currentParameterSet = $this->getDefaultParameterSet();
		}

		return $this->currentParameterSet;
	}

	/**
	 * @param ThemeParameterSet $sourceSet
	 * @param ThemeParameterSet $targetSet
	 */
	protected function copyParameterSetValues(ThemeParameterSet $sourceSet, ThemeParameterSet $targetSet)
	{
		$parameters = $this->getParameters();

		$targetValues = $targetSet->getValues();
		$sourceValues = $sourceSet->getValues();

		foreach ($parameters as $parameter) {
			/* @var $parameter ThemeParameterAbstraction */
			$parameterName = $parameter->getName();

			$sourceValue = $sourceValues->get($parameterName);
			/* @var $sourceValue ThemeParameterValue */

			$targetValue = null;

			if ( ! $targetValues->containsKey($parameterName)) {
				$targetValue = $targetSet->addNewValueForParameter($parameter);
			} else {
				$targetValue = $targetValues->get($parameterName);
			}

			$targetValue->setValue($sourceValue->getValue());
		}
	}

	/**
	 * @param ThemeParameterSet $currentParameterSet 
	 */
	public function setCurrentParameterSet(ThemeParameterSet $currentParameterSet)
	{
		$this->currentParameterSet = $currentParameterSet;
	}

	/**
	 * @return ThemeParameterSet | null
	 */
	public function getActiveParameterSet()
	{
		if (empty($this->activeParameterSet)) {

			if ($this->parameterSets->isEmpty()) {
				return null;
			} else {
				$this->activeParameterSet = $this->parameterSets->first();
			}
		}

		return $this->activeParameterSet;
	}

	/**
	 * @return ThemeParameterSet
	 */
	public function getDefaultParameterSet()
	{
		if ( ! $this->parameterSets->containsKey(self::DEFAULT_PARAMETER_SET_NAME)) {
			throw new Exception\RuntimeException('Default parameter set is not defined for theme "' . $this->getName() . '".');
		}

		$defaultParameterSet = $this->parameterSets->get(self::DEFAULT_PARAMETER_SET_NAME);

		return $defaultParameterSet;
	}

	/**
	 * @param ThemeParameterSet $activeParameterSet 
	 */
	public function setActiveParameterSet(ThemeParameterSet $activeParameterSet = null)
	{
		$this->activeParameterSet = $activeParameterSet;
	}

	/**
	 * @return string
	 */
	public function getConfigurationFilename()
	{
		return $this->getRootDir() . DIRECTORY_SEPARATOR . 'theme.yml';
	}

	/**
	 * @param string $configurationFilename 
	 */
	public function setConfigurationFilename($configurationFilename)
	{
		$this->configurationFilename = $configurationFilename;
	}

	/**
	 * @return array
	 */
	public function getParameterSets()
	{
		return $this->parameterSets;
	}

	/**
	 * @param strnig $name
	 * @param boolean $doNotInitializeFromDefault
	 * @return ThemeParameterSet
	 */
	public function getParameterSet($name, $initializeFromDefault = true)
	{
		$parameterSets = $this->getParameterSets();

		if ($parameterSets->containsKey($name)) {

			$parameterSet = $parameterSets->get($name);
		} else {

			$parameterSet = new ThemeParameterSet();

			$parameterSet->setName($name);

			$this->addParameterSet($parameterSet);

			if ($initializeFromDefault) {

				$defaultParameterSet = $this->getDefaultParameterSet();

				$this->copyParameterSetValues($defaultParameterSet, $parameterSet);
			}
		}

		return $parameterSet;
	}

	/**
	 * @param ThemeParameterSet $parameterSet 
	 */
	public function addParameterSet(ThemeParameterSet $parameterSet)
	{
		if ($this->parameterSets->containsKey($parameterSet->getName())) {
			$this->removeParameterSet($this->parameterSets->get($parameterSet->getName()));
		}

		$parameterSet->setTheme($this);

		$this->parameterSets[$parameterSet->getName()] = $parameterSet;
	}

	/**
	 * @param ThemeParameterSet $parameterSet 
	 */
	public function removeParameterSet(ThemeParameterSet $parameterSet)
	{
		$parameterSet->setTheme(null);

		$this->parameterSets->removeElement($parameterSet);
	}

	/**
	 * @return array
	 */
	public function getLayouts()
	{
		return $this->layouts;
	}

	/**
	 * @param ThemeLayout $layout 
	 */
	public function addLayout(ThemeLayout $layout)
	{
		$layout->setTheme($this);

		$this->layouts[$layout->getName()] = $layout;
	}

	/**
	 * @param ThemeLayout $layout 
	 */
	public function removeLayout(ThemeLayout $layout)
	{
		$layout->setTheme(null);

		$this->layouts->removeElement($layout);
	}

	/**
	 * @param string $layoutName
	 * @return ThemeLayout
	 */
	public function getLayout($layoutName)
	{
		return $this->layouts->get($layoutName);
	}

	/**
	 * @return ThemeConfiguration
	 */
	public function getConfiguration()
	{
		if (empty($this->configuration)) {

			/**
			 * define('USE_THEME_CONF_CACHE', true) will enable 
			 * theme's configuration object cache which significantly increases the performance
			 * but this option raises segmentation faults when PHP's Xdebug extension is used
			 */
			if (defined('CACHE_THEME_CONFIGURATION') && CACHE_THEME_CONFIGURATION === true) {
				$cache = \Supra\ObjectRepository\ObjectRepository::getCacheAdapter($this);
				$key = $this->getConfigurationCacheKey();

				$this->configuration = $cache->fetch($key);
			}
			
			if (empty($this->configuration)) {
				
				$yamlParser = new YamlParser();
				$configurationLoader = new ThemeConfigurationLoader();
				$configurationLoader->setParser($yamlParser);
				$configurationLoader->setTheme($this);
				$configurationLoader->setMode(ThemeConfigurationLoader::MODE_FETCH_CONFIGURATION);
				$configurationLoader->setCacheLevel(ThemeConfigurationLoader::CACHE_LEVEL_EXPIRE_BY_MODIFICATION);

				$configurationLoader->loadFile($this->getRootDir() . DIRECTORY_SEPARATOR . 'theme.yml');
				
				if (defined('CACHE_THEME_CONFIGURATION') && CACHE_THEME_CONFIGURATION === true) {
					$key = $this->getConfigurationCacheKey();
					$cache->save($key, $this->configuration);
				}
			}
		}

		return $this->configuration;
	}

	/**
	 * @param ThemeConfiguration $configuration 
	 */
	public function setConfiguration(ThemeConfiguration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @return string
	 */
	public function getPreviewContentUrl()
	{
		return $this->getUrlBase() . 'preview.html';
	}

	/**
	 * @param ThemeParameterSet $set
	 * @param ThemeParameterAbstraction $parameter
	 * @param mixed $newValue
	 */
	public function setParameterValue(ThemeParameterSet $set, ThemeParameterAbstraction $parameter, $newValue)
	{
		$parameterName = $parameter->getName();

		$parameterValues = $set->getValues();

		/* @var $parameterValue ThemeParameterValue */
		if ($parameterValues->containsKey($parameterName)) {
			$parameterValue = $parameterValues->get($parameterName);
		} else {
			$parameterValue = $set->addNewValue($parameterName);
		}

		$parameter->updateValue($parameterValue, $newValue);
	}

	/**
	 * @param \Supra\Controller\Pages\Entity\Theme\Parameter\ThemeParameterAbstraction $parameter
	 * @param mixed $newValue
	 */
	public function setCurrentParameterSetValue(ThemeParameterAbstraction $parameter, $newValue)
	{

		$currentParameterSet = $this->getCurrentParameterSet();

		$this->setParameterValue($currentParameterSet, $parameter, $newValue);
	}
	
	/**
	 * @return ArrayCollection
	 */
	public function getPlaceholderGroupLayouts()
	{
		return $this->placeholderGroupLayouts;
	}
	
	public function addPlaceholderGroupLayout(ThemePlaceholderGroupLayout $layout)
	{
		$layout->setTheme($this);
		$this->placeholderGroupLayouts->set($layout->getName(), $layout);
	}
	
	public function removePlaceholderGroupLayout(ThemePlaceholderGroupLayout $layout)
	{
		$layout->setTheme(null);
		$this->placeholderGroupLayouts->removeElement($layout);
	}
	
	private function getConfigurationCacheKey()
	{
		return __CLASS__ . '_' . $this->name . '_' . $this->configMd5;
	}
}
