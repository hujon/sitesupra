<?php

namespace Supra\Cms\ContentManager\Template;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Layout\Exception as LayoutException;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Sitemap
 */
class TemplateAction extends PageManagerAction
{

	/**
	 * Template creation
	 */
	public function createAction()
	{
		$this->lock();

		$this->checkApplicationAllAccessPermission();

		$this->entityManager->beginTransaction();
		$templateData = null;

		try {
			$templateData = $this->createActionTransactional();
		} catch (\Exception $e) {
			$this->entityManager->rollback();

			throw $e;
		}

		$this->entityManager->commit();

		$this->unlock();

		// Decision in #2695 to publish the template right after creating it
		$this->pageData = $templateData;
		$this->publish();

		$this->outputPage($templateData);

		$this->writeAuditLog('%item% created', $templateData);
	}

	/**
	 * Method called in transaction
	 * @return Entity\TemplateLocalization
	 */
	protected function createActionTransactional()
	{
		$this->checkApplicationAllAccessPermission();

		$this->isPostRequest();
		$input = $this->getRequestInput();

		$rootTemplate = $input->isEmpty('parent_id', false);
		$hasLayout = ( ! $input->isEmpty('layout'));

		if ($rootTemplate && ! $hasLayout) {
			throw new CmsException(null, "Root template must have layout specified");
		}

		$localeId = $this->getLocale()->getId();

		$eventManager = $this->entityManager->getEventManager();
		$eventManager->dispatchEvent(AuditEvents::pagePreCreateEvent);
		
		$template = new Entity\Template();
		$templateData = new Entity\TemplateLocalization($localeId);

		$this->entityManager->persist($template);
		$this->entityManager->persist($templateData);

		$templateData->setMaster($template);

		if ($input->has('title')) {
			$title = $input->get('title');
			$templateData->setTitle($title);
		}

		if ($hasLayout) {
			//TODO: validate
			$layoutId = $input->get('layout');
			
			$themeProvider = ObjectRepository::getThemeProvider($this);
			$activeTheme = $themeProvider->getCurrentTheme();

			$layout = $activeTheme->getLayout($layoutId);

			$templateLayout = $template->addLayout($this->getMedia(), $layout);
			$this->entityManager->persist($templateLayout);
		}

		$this->entityManager->flush();

		// Find parent page
		if ( ! $rootTemplate) {

			$parentLocalization = $this->getPageLocalizationByRequestKey('parent_id');

			if ( ! $parentLocalization instanceof Entity\TemplateLocalization) {
				$parentId = $input->get('parent_id', null);
				throw new CmsException(null, "Could not found template parent by ID $parentId");
			}

			$parent = $parentLocalization->getMaster();

			// Set parent
			$template->moveAsLastChildOf($parent);
			$this->entityManager->flush();
		}
		
		$pageEventArgs = new PageEventArgs();
		$pageEventArgs->setProperty('referenceId', $templateData->getId());
		$pageEventArgs->setEntityManager($this->entityManager);
		$eventManager->dispatchEvent(AuditEvents::pagePostCreateEvent, $pageEventArgs);

		return $templateData;
	}

	/**
	 * Settings save action
	 */
	public function saveAction()
	{
		$this->checkApplicationAllAccessPermission();

		$this->isPostRequest();
		$input = $this->getRequestInput();
		$this->checkLock();
		$pageData = $this->getPageLocalization();

		//TODO: create some simple objects for save post data with future validation implementation?
		if ($input->has('title')) {
			$title = $input->get('title');
			$pageData->setTitle($title);
		}

		$this->entityManager->flush();

		$this->savePostTrigger();
		
		$this->writeAuditLog('%item% saved', $pageData);
	}

	public function deleteAction()
	{
		$this->lock();

		$this->checkApplicationAllAccessPermission();

		$this->isPostRequest();

		$page = $this->getPageLocalization()
				->getMaster();

		if ($page->hasChildren()) {
			throw new CmsException(null, "Cannot remove template with children");
		}

		$this->delete();
		$this->unlock();

		$this->writeAuditLog('%item% deleted', $page);
	}

	/**
	 * Called on template publish
	 */
	public function publishAction()
	{
		$this->checkApplicationAllAccessPermission();

		// Must be executed with POST method
		$this->isPostRequest();

		$this->checkLock();
		$this->publish();
		$this->unlockPage();

		$templateLocalization = $this->getPageLocalization();
		$this->writeAuditLog('%item% published', $templateLocalization);
	}

	/**
	 * Called on template lock action
	 */
	public function lockAction()
	{
		$this->checkApplicationAllAccessPermission();

		$this->lockPage();
	}

	/**
	 * Called on template unlock action
	 */
	public function unlockAction()
	{
		$this->checkApplicationAllAccessPermission();

		try {
			$this->checkLock();
		} catch (\Exception $e) {
			$this->getResponse()->setResponseData(true);
			return;
		}
		$this->unlockPage();
	}

	/**
	 * Template duplicate action
	 */
	public function duplicateAction()
	{
		$this->lock();

		$this->checkApplicationAllAccessPermission();

		$this->isPostRequest();
		$localization = $this->getPageLocalization();
		$master = $localization->getMaster();
		$this->duplicate($localization);
		$this->unlock();
		
		$this->writeAuditLog('%item% duplicated', $master);
	}

	/**
	 * Create template localization
	 */
	public function createLocalizationAction()
	{
		$this->checkApplicationAllAccessPermission();

		$this->isPostRequest();
		$this->createLocalization();
	}

}