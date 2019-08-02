<?php
require_once __DIR__.'/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class SmartEdge_Local_Data_Logger
{
	public function getLogger($logFile, $logLevelName = 'ERROR')
	{
		$logLevel = $logLevelName;
		if (is_string($logLevelName)) {
			$logLevelName = strtoupper($logLevelName);
			switch ($logLevelName) {
				case 'DEBUG':
					$logLevel = Logger::DEBUG;
					break;
				case 'INFO':
					$logLevel = Logger::INFO;
					break;
				case 'NOTICE':
					$logLevel = Logger::NOTICE;
					break;
				case 'WARNING':
					$logLevel = Logger::WARNING;
					break;
				case 'ERROR':
					$logLevel = Logger::ERROR;
					break;
				case 'CRITICAL':
					$logLevel = Logger::CRITICAL;
					break;
				case 'ALERT':
					$logLevel = Logger::ALERT;
					break;
				case 'EMERGENCY':
					$logLevel = Logger::EMERGENCY;
					break;
			}
		}

		$channel = 'SmartEdge Local Data Store';
		
		$logger = new Logger($channel);

		/**
		 * Logs to file
		 */
		$stream = new StreamHandler($logFile);
		$stream->setLevel($logLevel);
		$formatter = new LineFormatter("[%datetime%] %level_name%: %message%\n");
		$stream->setFormatter($formatter);
		$logger->pushHandler($stream);

		return $logger;
	}

	public function setLogLevel(&$logger, $logLevel)
	{
		$levels = $logger->getLevels();
		$logLevel = strtoupper($logLevel);
		if (!isset($levels[$logLevel])) {
			return false;
		}

		$updated = false;
		$handlers = $logger->getHandlers();
		foreach ($handlers as $key => $handler) {
			if (method_exists($handler, 'getStream')) {
				// this is a StreamHandler
				$handler->setLevel($levels[$logLevel]);
				$updated = true;

				// replace
				$handlers[$key] = $handler;
			}
		}
		if ($updated) {
			$logger->setHandlers($handlers);
			return true;
		}
		return false;
	}
}