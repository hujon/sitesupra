<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;
use Supra\Log\Writer\WriterAbstraction;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Controller abstraction class
 */
abstract class ControllerAbstraction implements ControllerInterface
{
	/**
	 * @var WriterAbstraction
	 */
	protected $log;

	/**
	 * Request object
	 * @var Request\RequestInterface
	 */
	protected $request;

	/**
	 * Response object
	 * @var Response\ResponseInterface
	 */
	protected $response;

	/**
	 * Binds the logger
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}

	/**
	 * Prepares controller for execution
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function prepare(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	/**
	 * Output the result
	 */
	public function output()
	{
		$response = $this->getResponse();
		$response->flush();
	}

	/**
	 * Get request object
	 * @return Request\RequestInterface
	 */
	final public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get response object
	 * @return Response\ResponseInterface
	 */
	final public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		if ($request instanceof Request\HttpRequest) {
			return new Response\HttpResponse();
		}
		
		return new Response\EmptyResponse();
	}

	public static function CN()
	{
		return get_called_class();
	}
}