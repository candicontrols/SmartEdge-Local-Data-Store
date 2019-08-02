<?php
$config = array();

/*
 * CAN NOT BE LEFT BLANK
 */
$config['projectId'] = '';
$config['subscriptionName'] = '';

/*
CREATE TABLE `SmartEdgeData` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `siteCd` varchar(50) DEFAULT NULL,
  `deviceCd` varchar(50) DEFAULT NULL,
  `label` varchar(200) DEFAULT NULL,
  `value` varchar(2048) DEFAULT NULL,
  `ts` int(11) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/
$config['db_host'] = '';
$config['db_user'] = '';
$config['db_password'] = '';
$config['db_database'] = '';
$config['db_table'] = 'SmartEdgeData';

$config['logFile'] = '/tmp/SmartEdgeLocalDataStore.log';
/*
	logLevel can be one of the following:
	DEBUG
	INFO
	NOTICE
	WARNING
	ERROR
	CRITICAL
	ALERT
	EMERGENCY
 */
$config['logLevel'] = 'WARNING';

/*
 * DO NOT CHANGE CODE BELOW
 */
if (empty($config['projectId'])) {
	die('Project Id can not be left blank!'.PHP_EOL);
}
if (empty($config['subscriptionName'])) {
	die('Subscription Name can not be left blank!'.PHP_EOL);
}
if (empty($config['db_host'])) {
	die('MySQL host can not be left blank!'.PHP_EOL);
}
if (empty($config['db_user'])) {
	die('MySQL user can not be left blank!'.PHP_EOL);
}
if (empty($config['db_password'])) {
	die('MySQL password can not be left blank!'.PHP_EOL);
}
if (empty($config['db_database'])) {
	die('MySQL database can not be left blank!'.PHP_EOL);
}
if (empty($config['db_table'])) {
	die('MySQL table can not be left blank!'.PHP_EOL);
}
$config['environment'] = null;
switch ($config['projectId']) {
	case 'celtic-beacon-125423':
		$config['environment'] = 'production';
		break;
	
	case 'candi-dev':
		$config['environment'] = 'develop';
		break;
}
if (empty($config['environment'])) {
	die('Project Id not valid!'.PHP_EOL);
}
$config['identifier'] = base64_encode($config['subscriptionName']);