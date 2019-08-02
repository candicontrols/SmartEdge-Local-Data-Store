<?php
chdir(__DIR__);
require_once __DIR__.'/config/config.inc.php';
require_once __DIR__.'/includes/SmartEdge_Local_Data_PubSub.class.php';
require_once __DIR__.'/includes/SmartEdge_Local_Data_Logger.class.php';
require_once __DIR__.'/includes/SmartEdge_Local_Data_MySQL.class.php';

$logger = SmartEdge_Local_Data_Logger::getLogger($config['logFile'], $config['logLevel']);

if (!defined('LOOP_INTERVAL')) {
	define("LOOP_INTERVAL", 5);      // seconds
}
if (!defined('EXIT_AFTER_SECONDS')) {
	define("EXIT_AFTER_SECONDS", 3600);
}

$lockFile = sys_get_temp_dir().'/SmartEdgeLocalDataStore.'.$config['identifier'].'.lock';
if (file_exists($lockFile)) {
	$lastModifiedTime = filemtime($lockFile);
	if ((time() - $lastModifiedTime) < EXIT_AFTER_SECONDS) {
		$logger->error("Daemon lock file in place, exiting. [{$lockFile}]");
		die();
	}
}
touch($lockFile);

SmartEdge_Local_Data_PubSub::init($config);
$errors = SmartEdge_Local_Data_PubSub::getErrors();
if ($errors) {
	foreach ($errors as $error) {
		$logger->error($error);
	}
	die();
}

SmartEdge_Local_Data_MySQL::init($config);
$errors = SmartEdge_Local_Data_MySQL::getErrors();
if ($errors) {
	foreach ($errors as $error) {
		$logger->error($error);
	}
	die();
}

$scriptName = 'worker.php';

$cores = exec("grep 'model name' /proc/cpuinfo | wc -l");
$loadThreshold = 1*$cores;

$minRunning = $cores; 
$maxRunning = 50;

$notifyFile = sys_get_temp_dir().'/maxSmartEdgeLocalData.'.$config['identifier'].'.notify';
$z = 1;
$startTime = time();
do {
	// Because of the way PHP works, this will return itself in the count too
	// i.e., $count=1 means 0 workers running ($count=2 means 1 worker, etc)
	$count = exec("pgrep -c -f $scriptName");

	$startNewWorker = ($count <= $minRunning) || file_exists($notifyFile);

	if ($startNewWorker && ($count <= $maxRunning)) {
		$load = sys_getloadavg();
		if (($count > 1) && ($load[1] > $loadThreshold)) {
			$logger->warning('System load ['.$load[1].'] for 5 min is above threshold ['.$loadThreshold.']. Not starting new worker.');
		}
		else {
		    system("setsid php ".__DIR__."/$scriptName $z </dev/null >/dev/null 2>/dev/null &"); // in background, will not wait for return
		    if (file_exists($notifyFile)) {
		    	unlink($notifyFile);
		    }
		    $z++;
		}
	}

    sleep(LOOP_INTERVAL);
}
while ((time() - $startTime) < EXIT_AFTER_SECONDS);

/*
 * Start new one
 */
unlink($lockFile);
$command = 'php '.__DIR__.'/daemon.php';
system("setsid {$command} </dev/null >/dev/null 2>/dev/null &");

exit;