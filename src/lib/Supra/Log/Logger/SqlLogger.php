<?php

namespace Supra\Log\Logger;

use Doctrine\DBAL\Logging\SQLLogger as SQLLoggerInterface;
use Supra\Log\Writer\WriterAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\LogEvent;
use Supra\Controller\Pages\Event\SqlEvents;
use Supra\Controller\Pages\Event\SqlEventsArgs;
/**
 * Sql class
 */
class SqlLogger
{
	/**
	 * @var string
	 */
	private $sql;
	
	/**
	 * @var array
	 */
	private $params;
	
	/**
	 * @var array
	 */
	private $types;
	
	/**
	 * @var float
	 */
	private $start;

	/**
	 * Return list of subscribed events
	 * @return array
	 */
	public function getSubscribedEvents(){
		
		return array(
			SqlEvents::startQuery,
			SqlEvents::stopQuery,
		);
		
	}
	
	/**
	 * @param string $subject
	 */
	private function log($subject, $level = LogEvent::DEBUG)
	{
		$log = ObjectRepository::getLogger($this);
		
		// Let's find first caller offset not inside the Doctrine package
		$offset = 0;
		$trace = debug_backtrace(false);
		array_shift($trace);
		
		foreach ($trace as $traceElement) {
			$class = null;
			if (isset($traceElement['class'])) {
				$class = $traceElement['class'];
			}
			if ($class != __CLASS__ 
					&& $class != 'Supra\Log\Logger\EventsSqlLogger' 
					&& $class != 'Supra\Event\EventManager' 
					&& strpos($class, 'Doctrine\\') !== 0 
					&& strpos($class, 'Supra\\NestedSet\\') !== 0) {
				break;
			}
			
//			$log->debug("$class:{$traceElement['line']}");
			
			$offset++;
		}
		
		$log->increaseBacktraceOffset($offset);
		$log->__call($level, array($subject));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function startQuery(SqlEventsArgs $eventArgs)
	{
		$this->sql = $eventArgs->sql;
		$this->params = (array) $eventArgs->params;
		$this->types = $eventArgs->types;

		// Fix DateTime object logging
		foreach ($this->params as $key => &$param) {
			if ($param instanceof \DateTime) {
				$this->params[$key] = $param->format('c');
			}
			if ($param instanceof \Supra\Uri\Path) {
				$this->params[$key] = $param->getFullPath();
			}
			if (is_null($param)) {
				$param = 'NULL';
			}
			if (is_array($param)) {
				$param = 'ARRAY[' . implode('; ', $param) . ']';
			}
			if (is_object($param)) {
				$param = 'OBJECT[' . serialize($param) . ']';
			}
			
			$length = mb_strlen($param);
			
			if ($length > 70) {
				$size = ($length - 50) . " chars";
				$param = mb_substr($param, 0, 50) . "...<$size>..." . mb_substr($param, $length - 10);
			}
		}
		unset($param);
		
		$level = $this->getLogLevel();
		
		$sql = preg_replace('/SELECT\s+(.{20,})\s+FROM/', 'SELECT ... FROM', $this->sql);

		$this->log($sql . "\n"
				. ($this->params ? "(" . implode('; ', $this->params) . ")\n" : ""), 
				$level);
		
//		$subject = "Query\n{$this->sql}\n";
//		$this->log($subject, $level);

		$this->start = microtime(true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function stopQuery()
	{
		$executionMs = round(1000 * (microtime(true) - $this->start));
		$subject .= "... execution time {$executionMs}ms";
		
		$level = $this->getLogLevel();
		$this->log($subject, $level);
	}
	
	/**
	 * @return string
	 */
	protected function getLogLevel()
	{
		// Log selects with DEBUG level, other with INFO
		$sql = ltrim($this->sql);
		if (stripos($sql, 'SELECT') !== 0) {
			return LogEvent::INFO;
		}
		
		return LogEvent::DEBUG;
	}
}