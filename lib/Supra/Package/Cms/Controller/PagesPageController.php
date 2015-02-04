<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Controller;

use Doctrine\ORM\EntityManager;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Core\Locale\LocaleInterface;
use Supra\Package\Cms\Pages\Exception\DuplicatePagePathException;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\ApplicationPage;
use Supra\Package\Cms\Entity\RedirectTargetChild;
use Supra\Package\Cms\Entity\RedirectTargetPage;
use Supra\Package\Cms\Entity\RedirectTargetUrl;
use Supra\Package\Cms\Uri\Path;

class PagesPageController extends AbstractPagesController
{
	/**
	 * Gets available template list action.
	 *
	 * @internal Called by plugin-page-add.js,
	 * to render list of available layouts on new Template creation.
	 *
	 * @return SupraJsonResponse
	 */
	public function layoutsListAction()
	{
		return new SupraJsonResponse($this->getActiveThemeLayoutsData());
	}

	/**
	 * Page delete action.
	 *
	 * @return SupraJsonResponse
	 */
	public function deleteAction()
	{
		$this->checkLock();

		$this->isPostRequest();

		$page = $this->getPageLocalization()
			->getMaster();

		if ($page->hasChildren()) {
			throw new CmsException(null, "Cannot remove page with children");
		}

		$entityManager = $this->getEntityManager();
		$entityManager->remove($page);
		$entityManager->flush();

		return new SupraJsonResponse();
	}

	/**
	 * Handles Page creation request.
	 */
	public function createAction()
	{
		$this->isPostRequest();

		$type = $this->getRequestParameter('type');

		$page = null;

		switch ($type) {
			case Entity::APPLICATION_DISCR:
				$page = new ApplicationPage(
						$this->getRequestParameter('application_id')
				);
				break;
			case Entity::PAGE_DISCR:
				$page = new Page();
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Unknown page type [%s]', $type));
		}

		$localeId = $this->getCurrentLocale()->getId();

		$localization = Localization::factory($page, $localeId);
		/* @var $localization PageLocalization */

		if (! $localization instanceof PageLocalization) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting created localization to be instance of PageLocalization, [%s] received.',
					get_class($localization)
			));
		}

		$templateId = $this->getRequestParameter('template');
		
		$template = $this->getEntityManager()
				->find(Template::CN(), $templateId);
		/* @var $template Template */

		if ($template === null) {
			throw new CmsException(null, 'Template not specified or found.');
		}

		$templateLocalization = $template->getLocalization($localeId);

		if ($templateLocalization === null) {
			throw new \InvalidArgumentException(
					"Specified template has no localization for [{$localeId}] locale."
			);
		}

		$localization->setTemplate($template);

		// copy values from template
		$localization->setIncludedInSearch($templateLocalization->isIncludedInSearch());
		$localization->setVisibleInMenu($templateLocalization->isVisibleInMenu());
		$localization->setVisibleInSitemap($templateLocalization->isVisibleInSitemap());

		$title = trim($this->getRequestParameter('title', ''));

		if (empty($title)) {
			throw new CmsException(null, 'Page title cannot be empty.');
		}
		
		$localization->setTitle($title);

		$parentLocalization = $pathPart
				= null;

		$parentLocalizationId = $this->getRequestParameter('parent_id');
		
		if (! empty($parentLocalizationId)) {

			$parentLocalization = $this->getEntityManager()
					->find(Localization::CN(), $parentLocalizationId);

			if ($parentLocalization === null) {
				throw new CmsException(null, sprintf(
						'Specified parent page [%s] not found.',
						$parentLocalizationId
				));
			}

			$pathPart = trim($this->getRequestParameter('path'));

			// path part cannot be empty for non-root pages.
			if (empty($pathPart)) {
				throw new CmsException(null, 'Page path can not be empty.');
			}
		}

		if ($parentLocalization && $pathPart) {
			$localization->setPathPart($pathPart);
		} else {
			$rootPath = $localization->getPathEntity();
			$rootPath->setPath('');
			$localization->setPathPart('');
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
	 * @return SupraJsonResponse
	 */
	public function saveSettingsAction()
	{
		$this->isPostRequest();
		$this->checkLock();

		$localization = $this->getPageLocalization();

		if (! $localization instanceof PageLocalization) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting PageLocalization instance, [%s] received.',
					get_class($localization)
			));
		}

		$this->saveLocalizationCommonSettingsAction();

		$input = $this->getRequestInput();

		//@TODO: validation
		$localization->setPathPart($input->get('path', ''));

		$templateId = $input->get('template');

		$template = $this->getEntityManager()
				->find(Template::CN(), $templateId);

		if ($template === null) {
			throw new CmsException(null, sprintf('Specified template [%s] not found.'));
		}

		if (! $template->equals($localization->getTemplate())) {
			$localization->setTemplate($template);
		}

		$localization->setActive($input->filter('active', null, false, FILTER_VALIDATE_BOOLEAN));

		$localization->setMetaDescription($input->get('description'));

		$localization->setMetaKeywords($input->get('keywords'));

		$global = $input->filter('global', false, false, FILTER_VALIDATE_BOOLEAN);

		// @TODO: possible 'global' property renaming is needed, it's confusing.
		if ($global === false && $localization->getMaster()->isRoot()) {
			throw new \LogicException('It is not allowed to disable translation of root page.');
		}

		$localization->getMaster()
				->setGlobal($global);

		// @TODO: would be nice if date/time would be sent as single value.
		$publicationScheduleDate = $input->get('scheduled_date');
		$scheduleDateTime = null;

		if (! empty($publicationScheduleDate)) {
			
			$publicationScheduleTime = $input->get('scheduled_time', '00:00:00');

			$scheduleDateTime = \DateTime::createFromFormat(
					'Y-m-d/H:i:s',
					"$publicationScheduleDate/$publicationScheduleTime"
			);

			if ($scheduleDateTime === false) {
				throw new \RuntimeException(sprintf(
						'Failed to create page publication schedule datetime object from date [%s] and time [%s] values.',
						$publicationScheduleDate,
						$publicationScheduleTime
				));
			}
		}

		// @TODO: would be nice if date/time would be sent as single value.
		$creationDate = $input->get('created_date');
		$creationTime = $input->get('created_time', '00:00:00');

		$creationDateTime = \DateTime::createFromFormat('Y-m-d/H:i:s', "$creationDate/$creationTime");

		if ($creationDateTime === false) {
			throw new \RuntimeException(sprintf(
						'Failed to create page creation datetime object from date [%s] and time [%s] values.',
						$creationDate,
						$creationTime
				));
		}

		$localization->setCreationTime($creationDateTime);

		// @TODO: JS might not inform about redirect data if it was not changed.
		if ($localization->hasRedirectTarget()) {
			$this->getEntityManager()
					->remove($localization->getRedirectTarget());
		}

		$redirectTargetData = $input->get('redirect', array());

		if (! empty($redirectTargetData)) {
			$redirectTarget = $this->createRedirectTargetFromData($redirectTargetData);

			$this->getEntityManager()
					->persist($redirectTarget);

			$localization->setRedirectTarget($redirectTarget);
		}

		try {
			$this->getEntityManager()->flush();
		} catch (DuplicatePagePathException $e) {
			throw new CmsException(null, $e->getMessage());
		}

		return new SupraJsonResponse();
	}

	/**
	 * Settings save action handler.
	 * Initiated on page title/path editing via Sitemap.
	 *
	 * @return SupraJsonResponse
	 */
	public function saveAction()
	{
		$this->isPostRequest();

		$this->checkLock();

		$this->saveLocalizationCommonAction();

		if ($this->getRequestInput()->has('path')) {

			$pathPart = trim($this->getRequestParameter('path'));

			 $this->getPageLocalization()
					 ->setPathPart($pathPart);
		}

		try {
			$this->getEntityManager()
					->flush($this->getPageLocalization());
		} catch (DuplicatePagePathException $e) {
			throw new CmsException('sitemap.error.duplicate_path', $e->getMessage());
		}

		return new SupraJsonResponse();
	}

	/**
	 * @throws \InvalidArgumentException
	 * @throws \LogicException
	 * @throws \UnexpectedValueException
	 * @throws CmsException
	 */
	public function copyLocalizationAction()
	{
		$page = $this->getPage();

		$sourceLocaleId = $this->getRequestParameter('source_locale');
		$targetLocaleId = $this->getRequestParameter('locale');

		if (! $this->getLocaleManager()->has($sourceLocaleId)) {
			throw new \InvalidArgumentException(sprintf(
					'Source locale [%s] not found.',
					$sourceLocaleId
			));
		}

		if (! $this->getLocaleManager()->has($targetLocaleId)) {
			throw new \InvalidArgumentException(sprintf(
					'Target locale [%s] not found.',
					$targetLocaleId
			));
		}

		if ($sourceLocaleId === $targetLocaleId) {
			throw new \LogicException('Source and target locales are identical.');
		}

		$localization = $page->getLocalization($sourceLocaleId);

		if ($localization === null) {
			throw new \UnexpectedValueException(sprintf(
					'Page [%s] is missing for [%s] locale localization.',
					$page->getId(),
					$sourceLocaleId
			));
		}

		// @TODO: WAT?
//		// dissalow to create more than one instance of root page
//		if ($master instanceof Page && $master->isRoot()) {
//
//			$pathEntityName = Entity\PageLocalizationPath::CN();
//
//			$dql = "SELECT p FROM $pathEntityName p
//				WHERE p.path = :path
//				AND p.locale = :locale";
//
//			$query = $this->entityManager
//					->createQuery($dql)
//					->setParameters(array('path' => '', 'locale' => $targetLocale));
//
//			$path = $query->getOneOrNullResult();
//			if ($path instanceof Entity\PageLocalizationPath) {
//				throw new CmsException(null, 'It is not allowed to create multiple root pages');
//			}
//		}

		if ($localization->getTemplate()
				->getLocalization($targetLocaleId) === null) {

			throw new CmsException(null, sprintf(
					'There is no [%s] localization for [%s] template this page uses. Please create it first.',
					$this->getLocaleManager()
							->getLocale($targetLocaleId)
							->getTitle(),
					$localization->getTemplate()
							->getLocalization($sourceLocaleId)
							->getTitle()
			));
		}

		$input = $this->getRequestInput();
		
		$targetLocale = $this->getLocaleManager()->getLocale($targetLocaleId);

		$pageManager = $this->getPageManager();

		$copiedLocalization = $this->getEntityManager()
				->transactional(function (EntityManager $entityManager) use (
						$pageManager,
						$page,
						$localization,
						$targetLocale,
						$input
				) {

			$copiedLocalization = $pageManager->copyLocalization($localization,	$targetLocale);

			$title = trim($input->get('title'));

			if (! empty($title)) {
				$copiedLocalization->setTitle($title);
			}

			$pathPart = trim($input->get('path'));

			if (! empty($pathPart) && ! $page->isRoot()) {
				$copiedLocalization->setPathPart($pathPart);
			}

			try {
				$entityManager->flush();
			} catch (DuplicatePagePathException $e) {
				throw new CmsException(null, $e->getMessage());
			}

			return $copiedLocalization;
		});

		return new SupraJsonResponse(array(
				'id' => $copiedLocalization->getId()
		));
	}

	/**
	 * @return SupraJsonResponse
	 */
	public function copyAction()
	{
		$this->isPostRequest();

		$page = $this->getPage();

		if (! $page instanceof Page) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting Page instance, [%s] received',
					get_class($page)
			));
		}

		if ($page->isRoot()) {
			// copied page becomes a sibling,
			// but multiple roots for Pages are not allowed.
			throw new \LogicException('Root page copying is prohibited.');
		}

		$entityManager = $this->getEntityManager();

		$pageManager = $this->getPageManager();

		$this->lockNestedSet($page);

		$copiedPage = $entityManager->transactional(function (EntityManager $entityManager) use ($pageManager, $page) {

			$copiedPage = $pageManager->copyPage($page);

			$copiedPage->moveAsNextSiblingOf($page);

			$entityManager->flush();

			return $copiedPage;
		});

		$this->unlockNestedSet($page);

		return new SupraJsonResponse(
				$this->convertPageToArray($copiedPage, $this->getCurrentLocale()->getId())
		);
	}

	/**
	 * @return SupraJsonResponse
	 */
	public function pathInfoAction()
	{
		$input = $this->getRequestInput();

		$pathString = $input->get('page_path');
		$pathString = trim(parse_url($pathString, PHP_URL_PATH), '/');

		$path = new Path($pathString);
		$localeId = null;

		foreach ($this->getLocaleManager()->getLocales() as $locale) {
			/* @var $locale LocaleInterface */

			$pathPrefix = new Path($locale->getId());

			if ($path->startsWith($pathPrefix)) {
				$path->setBasePath($pathPrefix);
				$localeId = $locale->getId();
				break;
			}
		}

		$localeId = $localeId ?: $input->get('locale');

		$pathData = array();

		$localization = $this->getEntityManager()->createQuery(
			'SELECT l FROM Cms:PageLocalization l JOIN l.path p
					WHERE p.path = :path AND l.locale = :locale')
			->setParameters(array(
				'path' 		=> $path->getPath(Path::FORMAT_NO_DELIMITERS),
				'locale' 	=> $localeId,
			))->getOneOrNullResult();

		if ($localization) {
			/* @var $localization PageLocalization */
			$pathData = array(
				'locale' => $localeId,
				'page_id' => $localization->getId(),
			);

			if (($redirectTarget = $localization->getRedirectTarget()) instanceof RedirectTargetPage) {
				/* @var $redirectTarget RedirectTargetPage */
				$targetPage = $redirectTarget->getTargetPage();

				if ($targetPage && ($targetPage->getLocalization($localeId) !== null)) {
					$pathData['redirect_page_id'] = $targetPage->getLocalization($localeId)->getId();
				}
			}
		}

		return new SupraJsonResponse($pathData);
	}

	/**
	 * @param array $data
	 * @return \Supra\Package\Cms\Entity\Abstraction\RedirectTarget
	 */
	private function createRedirectTargetFromData(array $data)
	{
		$type = isset($data['type']) ? $data['type'] : null;

		switch ($type) {
			case 'page':
				$redirectTarget = new RedirectTargetPage();

				if (empty($data['page_id'])) {
					throw new \InvalidArgumentException('Missing target page id.');
				}

				$page = $this->getEntityManager()
						->find(Page::CN(), $data['page_id']);

				if ($page === null) {
					throw new \UnexpectedValueException(sprintf(
							'Target page [%s] not found.',
							$data['page_id']
					));
				}

				break;

			case 'child':
				$redirectTarget = new RedirectTargetChild();

				if (empty($data['child_position'])) {
					throw new \InvalidArgumentException('Missing target child position.');
				}

				$redirectTarget->setPage(
						$this->getPageLocalization()->getPage()
				);

				$redirectTarget->setChildPosition($data['child_position']);

				break;

			case 'url':
				$redirectTarget = new RedirectTargetUrl();

				if (empty($data['url'])) {
					throw new \InvalidArgumentException('Missing target URL.');
				}

				if (! filter_var($data['url'], FILTER_VALIDATE_URL)) {
					throw new CmsException(null, sprintf(
							'Provided URL [%s] is not correct.',
							$data['url']
					));
				}

				$redirectTarget->setUrl($data['url']);

				break;
			default:
				throw new \UnexpectedValueException(sprintf(
						'Unknown redirect target type [%s].',
						$type
				));
		}

		return $redirectTarget;
	}

	/**
	 * @return PageLocalization
	 */
	protected function getPageLocalization()
	{
		$localization = parent::getPageLocalization();

		if (! $localization instanceof PageLocalization) {
			throw new \UnexpectedValueException(sprintf(
				'Expecting PageLocalization, [%s] received.',
				get_class($localization)
			));
		}

		return $localization;
	}
}