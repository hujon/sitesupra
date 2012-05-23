<?php

namespace Supra\Cms\AuditLog\Root;

use Supra\Controller\SimpleController;
use Supra\Request;
use Supra\Response;
use Supra\Cms\CmsAction;

/**
 * Root action, returns initial HTML
 * @method TwigResponse getResponse()
 */
class RootAction extends CmsAction
{
	/**
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface 
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return $this->createTwigResponse();
	}
	
	/**
	 * Method returning manager initial HTML
	 */
	public function indexAction()
	{
		$this->getResponse()->outputTemplate('audit-log/root/index.html.twig');
	}
	
	public function loadAction()
	{
		
	}
}