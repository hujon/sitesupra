<?php

namespace Supra\Cms;

use Supra\Controller\SimpleController;
use Supra\Response\JsonResponse;
use Supra\Request;
use Supra\Controller\Exception;
use Supra\Exception\LocalizedException;

/**
 * Description of CmsAction
 * @method JsonResponse getResponse()
 * @method Request\HttpRequest getRequest()
 */
abstract class CmsAction extends SimpleController
{
	/**
	 * Forced request 
	 * @var string
	 */
	private $requestMethod;
	
	/**
	 * Localized error handling
	 */
	public function execute()
	{
		// Handle localized exceptions
		try {
			parent::execute();
		} catch (LocalizedException $exception) {
			
			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof JsonResponse) {
				throw $exception;
			}
			
			//TODO: should use exception "message" at all?
			$message = $exception->getMessage();
			$messageKey = $exception->getMessageKey();
			
			if ( ! empty($messageKey)) {
				$message = '{#' . $messageKey . '#}';
				$response->setErrorMessage($messageKey);
			}

			$response->setErrorMessage($message);
		}
	}
	
	/**
	 * @return JsonResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new JsonResponse();
		
		return $response;
	}
	
	/**
	 * Mark request as POST request
	 * @throws Exception\ResourceNotFoundException if POST method is not used
	 */
	protected function isPostRequest()
	{
		if ( ! $this->getRequest()->isPost()) {
			throw new Exception\BadRequestException("Post request method is required for the action");
		}
		
		$this->requestMethod = Request\HttpRequest::METHOD_POST;
	}
	
	/**
	 * If the request parameter was sent
	 * @param string $key
	 * @return boolean
	 */
	protected function hasRequestParameter($key)
	{
		$value = $this->getRequestParameter($key);
		$exists = ($value != '');
		
		return $exists;
	}
	
	/**
	 * Tells if request value is empty (not sent or empty value)
	 * @param string $key
	 * @return boolean
	 */
	protected function emptyRequestParameter($key)
	{
		$value = $this->getRequestParameter($key);
		$empty = empty($value);
		
		return $empty;
	}
	
	/**
	 * Get POST/GET request parameter depending on the action setting
	 * @param string $key
	 * @return string
	 */
	protected function getRequestParameter($key)
	{
		$value = null;
		$request = $this->getRequest();
		
		if ($this->requestMethod == Request\HttpRequest::METHOD_POST) {
			$value = $request->getPostValue($key);
		} else {
			$value = $request->getQueryValue($key);
		}
		
		return $value;
	}
	
	/**
	 * TODO: hardcoded now, maybe should return locale object (!!!)
	 * @return string
	 */
	protected function getLocale()
	{
		return 'en';
	}
}
