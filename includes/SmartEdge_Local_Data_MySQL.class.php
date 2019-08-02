<?php
class SmartEdge_Local_Data_MySQL
{
	private static $db_host;
	private static $db_user;
	private static $db_password;
	private static $db_database;
	private static $db_table;
	private static $mysqli;
	private static $errors;

	public static function _construct()
	{
	}

	public static function init($config)
	{
		static::$db_host = $config['db_host'];
		static::$db_user = $config['db_user'];
		static::$db_password = $config['db_password'];
		static::$db_database = $config['db_database'];
		static::$db_table = $config['db_table'];

		static::connect();
	}

	private static function connect()
	{
		if (!static::$mysqli) {
			static::$mysqli = new mysqli(static::$db_host, static::$db_user, static::$db_password, static::$db_database);

			if (static::$mysqli->connect_errno) {
				static::$errors[] = "Could not connect to database!";

				static::$mysqli = null;
			}
		}
	}

	public static function store($siteCd, $deviceCd, $label, $value, $timestamp)
	{
		if (!static::$mysqli) {
			static::connect();
		}
		if (!static::$mysqli) {
			static::$errors[] = "Could not store data!";

			return false;			
		}

		$siteCd = static::$mysqli->real_escape_string($siteCd);
		$deviceCd = static::$mysqli->real_escape_string($deviceCd);
		$label = static::$mysqli->real_escape_string($label);
		$value = static::$mysqli->real_escape_string($value);
		$timestamp = static::$mysqli->real_escape_string($timestamp);

		$query = "INSERT INTO `".static::$db_table."` (`siteCd`,`deviceCd`,`label`,`value`,`ts`) VALUES ('{$siteCd}','{$deviceCd}','{$label}','{$value}',{$timestamp});";
		// syslog(LOG_DEBUG, $query);
		$result = null;
		try {
			$result = static::$mysqli->query($query);
		}
		catch (Exception $e) {
			static::$errors[] = $e->getMessage();
			static::$mysqli = null;
		}
		if ($result === false) {
			static::$errors[] = 'Error inserting data. ['.static::$mysqli->info.']';
		}
	}

	public static function getErrors($reset = true)
	{
		$errors = static::$errors;
		if ($reset) {
			static::$errors = array();
		}
		return $errors;
	}
}