<?php
require_once __DIR__.'/config/config.inc.php';
require_once __DIR__.'/includes/SmartEdge_Local_Data_PubSub.class.php';

SmartEdge_Local_Data_PubSub::authorize($config);
$errors = SmartEdge_Local_Data_PubSub::getErrors();
if ($errors) {
	foreach ($errors as $error) {
		echo $error.PHP_EOL;
	}
}
echo 'done'.PHP_EOL;
