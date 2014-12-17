<?php

namespace Supra\Package\Cms\Controller;

use Doctrine\ORM\EntityManager;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\Entity\GroupPage;
use Supra\Package\Cms\Entity\GroupLocalization;
use Supra\Package\Cms\Entity\Abstraction\Localization;

class PagesGroupController extends AbstractPagesController
{
	/**
	 * Handles Page creation request.
	 */
	public function createAction()
	{
		$this->isPostRequest();

		$page = new GroupPage();

		$localeId = $this->getCurrentLocale()->getId();

		$localization = Localization::factory($page, $localeId);
		/* @var $localization \Supra\Package\Cms\Entity\GroupLocalization */

		$title = trim($this->getRequestParameter('title', ''));

		if (empty($title)) {
			throw new CmsException(null, 'Group title cannot be empty.');
		}

		$localization->setTitle($title);

		$parentLocalizationId = $this->getRequestParameter('parent_id');

		if (empty($parentLocalizationId)) {
			throw new \UnexpectedValueException(
					'Parent ID is empty while it is not allowed Group to be root.'
			);
		}

		$parentLocalization = $this->getEntityManager()
					->find(Localization::CN(), $parentLocalizationId);

		if ($parentLocalization === null) {
			throw new CmsException(null, sprintf(
					'Specified parent page [%s] not found.',
					$parentLocalizationId
			));
		}

		$entityManager = $this->getEntityManager();

		$entityManager->transactional(function (EntityManager $entityManager) use ($page, $localization, $parentLocalization) {

			$this->lockNestedSet($page);

			$entityManager->persist($page);
			$entityManager->persist($localization);

			if ($parentLocalization) {
				$page->moveAsLastChildOf($parentLocalization->getMaster());
			}

			$this->unlockNestedSet($page);
		});

		return new SupraJsonResponse($this->loadNodeMainData($localization));
	}


	/**
	 * Action for delete virtual folder
	 */
	public function deleteAction()
	{
		$this->checkLock();

		$this->isPostRequest();
		$folder = $this->getPageLocalization()->getMaster();

		if( ! $folder instanceof GroupPage ) {
			throw new CmsException(null, "Not a virtualfolder object");
		}


		if ($folder->hasChildren()) {
			throw new CmsException(null, "Cannot remove virtualfolder with children");
		}

		$this->delete();

		return new SupraJsonResponse();
	}

	/**
	 * Settings save action handler.
	 * Initiated when group title is changed via Sitemap.
	 *
	 * @return SupraJsonResponse
	 */
	public function saveAction()
	{
		$this->isPostRequest();

		$this->checkLock();

		$localization = $this->getPageLocalization();
		
		if (! $localization instanceof GroupLocalization) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting instanceof GroupLocalization, [%s] received.',
					get_class($localization)
			));
		}

		$this->saveLocalizationCommonAction();

		$this->getEntityManager()
					->flush($localization->getMaster());

		return new SupraJsonResponse();
	}
}
