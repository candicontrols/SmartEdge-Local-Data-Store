<?php
require_once __DIR__.'/config/config.inc.php';
require_once __DIR__.'/includes/SmartEdge_Local_Data_PubSub.class.php';
require_once __DIR__.'/includes/SmartEdge_Local_Data_Logger.class.php';
require_once __DIR__.'/includes/SmartEdge_Local_Data_MySQL.class.php';

$logger = SmartEdge_Local_Data_Logger::getLogger($config['logFile'], $config['logLevel']);

SmartEdge_Local_Data_PubSub::init($config);
$errors = SmartEdge_Local_Data_PubSub::getErrors();
if ($errors) {
	foreach ($errors as $error) {
		$logger->error($error);
	}
}

SmartEdge_Local_Data_MySQL::init($config);
$errors = SmartEdge_Local_Data_MySQL::getErrors();
if ($errors) {
	foreach ($errors as $error) {
		$logger->error($error);
	}
}

if (!defined('LOOP_INTERVAL')) {
	define("LOOP_INTERVAL", 10);      // seconds
}
if (!defined('EXIT_AFTER_SECONDS')) {
	define("EXIT_AFTER_SECONDS", 300);
}

$notifyFile = sys_get_temp_dir().'/maxSmartEdgeLocalData.'.$config['identifier'].'.notify';

$startTime = time();

$maxNumberProcessedCount = 0;
$maxNumberProcessedNotifyLevel = 2;
do {
    $maxNumberProcessed = SmartEdge_Local_Data_Message_Processor($logger);
    if ($maxNumberProcessed) {
    	$maxNumberProcessedCount++;
    }
    else {
    	$maxNumberProcessedCount = 0;
    }
    if ($maxNumberProcessedCount >= $maxNumberProcessedNotifyLevel) {
    	touch($notifyFile);
    }

    sleep(LOOP_INTERVAL);
}
while ((time() - $startTime) < EXIT_AFTER_SECONDS);

exit;


function SmartEdge_Local_Data_Message_Processor(&$logger) {
	$noProcessed = 0;

	$messages = SmartEdge_Local_Data_PubSub::pull(10);
	$errors = SmartEdge_Local_Data_PubSub::getErrors();
	if ($errors) {
		foreach ($errors as $error) {
			$logger->error($error);
		}
	}

	$logger->debug("No messages retrieved. [".count($messages)."]");

	$ackIds = array();
	foreach($messages as $message) {
		$ackId = $message['ackId'];

		$siteCd = $message['attributes']['siteCd'];

		$json = $message['data'];
		$logger->debug("Message data [{$json}]");
		$data = json_decode($json);
		if (isset($data->usages)) {
			foreach ($data->usages as $usage) {
				$deviceCd = isset($usage->deviceCd) ? $usage->deviceCd : '';
				$labelParts = array();
				if (isset($usage->intervalType)) {
					$labelParts[] = $usage->intervalType;
				}
				if (isset($usage->usageType)) {
					$labelParts[] = $usage->usageType;
				}
				$label = '';
				if (!empty($labelParts)) {
					$label = implode('_', $labelParts);
				}
				$value = isset($usage->value) ? $usage->value : '';
				$timestamp = isset($usage->timestamp) ? $usage->timestamp : time();
				$logger->info("Storing: siteCd [{$siteCd}] deviceCd [{$deviceCd}] label [{$label}] value [{$value}] timestamp [{$timestamp}]");
				if (($label === '') && ($value === ''))
					continue;
				}
				SmartEdge_Local_Data_MySQL::store($siteCd, $deviceCd, $label, $value, $timestamp);
			}
		}
		if (isset($data->events)) {
			foreach ($data->events as $event) {
				$deviceCd = isset($event->deviceCd) ? $event->deviceCd : '';
				$label = isset($event->label) ? $event->label : '';
				$value = isset($event->value) ? $event->value : '';
				$timestamp = isset($event->timestamp) ? $event->timestamp : time();
				$logger->info("Storing: siteCd [{$siteCd}] deviceCd [{$deviceCd}] label [{$label}] value [{$value}] timestamp [{$timestamp}]");
				if (($label === '') && ($value === '')) {
					continue;
				}
				SmartEdge_Local_Data_MySQL::store($siteCd, $deviceCd, $label, $value, $timestamp);
			}		
		}
		$errors = SmartEdge_Local_Data_MySQL::getErrors();
		if ($errors) {
			foreach ($errors as $error) {
				$logger->error($error);
			}
		}
		else {
			$ackIds[] = $ackId;
		}
		$noProcessed++;
	}
	if (!empty($ackIds)) {
		SmartEdge_Local_Data_PubSub::acknowledge($ackIds);
		$errors = SmartEdge_Local_Data_PubSub::getErrors();
		if ($errors) {
			foreach ($errors as $error) {
				$logger->error($error);
			}
		}
		else {
			$ackIds[] = array();
		}
	}
	return $noProcessed;
}