<?php
require_once __DIR__.'/../vendor/autoload.php';

class SmartEdge_Local_Data_PubSub
{
	private static $projectId;
	private static $subscriptionName;
	private static $client;
	private static $service;
	private static $errors;

	public static function _construct()
	{
	}

	public static function init($config)
	{
		static::$projectId = $config['projectId'];
		static::$subscriptionName = $config['subscriptionName'];
		/*
		 * Set up Google API client
		 */
		static::$client = static::getClient($config);

		if (static::$client) {
			/*
			 * Set up Service
			 */
			try {
				static::$service = new \Google_Service_Pubsub(static::$client);
			}
			catch (\Exception $e) {
				static::$service = null;
			}
		}
	}

	/**
	 * Returns an authorized API client.
	 * @return Google_Client the authorized client object
	 */
	private static function getClient($config)
	{
		$client = new Google_Client();
		$client->setApplicationName('SmartEdge Local Data Store');
		$client->setScopes(Google_Service_Pubsub::PUBSUB);
		$client->setAuthConfig(__DIR__.'/../config/client_secret_'.$config['environment'].'.json');
		$client->setAccessType('offline');

		// Load previously authorized credentials from a file.
		$credentialsPath = __DIR__.'/../config/access_token_'.$config['environment'].'.json';
		if (!file_exists($credentialsPath)) {
			static::$errors[] = 'Access token not set.';

			return false;
		} 

		$accessToken = json_decode(file_get_contents($credentialsPath), true);
		$client->setAccessToken($accessToken);

		// Refresh the token if it's expired.
		if ($client->isAccessTokenExpired()) {
			$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			$written = file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
			if ($written === false) {
				die('Error saving credentials to '.$credentialsPath.PHP_EOL);
			}
		}

		return $client;
	}

	public static function authorize($config)
	{
		$client = new Google_Client();
		$client->setApplicationName('SmartEdge Local Data Store');
		$client->setScopes(Google_Service_Pubsub::PUBSUB);
		$client->setAuthConfig(__DIR__.'/../config/client_secret_'.$config['environment'].'.json');
		$client->setAccessType('offline');

		// Load previously authorized credentials from a file.
		$credentialsPath = __DIR__.'/../config/access_token_'.$config['environment'].'.json';
		if (file_exists($credentialsPath)) {
			print 'Access token already set.'.PHP_EOL;
		} 
		else {
			// Request authorization from the user.
			$authUrl = $client->createAuthUrl();
			print 'Open the following link in your browser:'.PHP_EOL.$authUrl.PHP_EOL;
			print 'Enter verification code: ';
			$authCode = trim(fgets(STDIN));

			// Exchange authorization code for an access token.
			$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

			// Store the credentials to disk.
			$written = file_put_contents($credentialsPath, json_encode($accessToken));
			if ($written === false) {
				die('Error saving credentials to '.$credentialsPath.PHP_EOL);
			}
			print 'Credentials saved to '.$credentialsPath.PHP_EOL;
		}
	}

	private static function getSubscription()
	{
		if (!static::$client) {
			static::$errors[] = 'Client not set.';
			return false;
		}
		$subscriptionName = static::$subscriptionName;

		$subscriptionID = 'projects/' . static::$projectId . '/subscriptions/' . $subscriptionName;
		$subscriptionsResource = static::$service->projects_subscriptions;
		/*
		 * get or create the subscription
		 */
		$subscription = false;
		try {
			/*
			 * we have an existing subscription
			 */
			$subscription = $subscriptionsResource->get($subscriptionID);
		}
		catch (\Exception $e) {
			$json = $e->getMessage();
			/*
			 * Process error
			 */ 
			$data = json_decode($json);
			static::$errors[] = __METHOD__.": {$data->error->message} [{$data->error->code}]";
		}
		return $subscription;
	}

	public static function pull($maxMessages = 10, $returnImmediately = true)
	{
		$subscriptionName = static::$subscriptionName;

		$subscription = static::getSubscription();
		if (!is_object($subscription)) {
			/*
			 * ALARM BELLS RINGING !!
			 */
			return false;
		}
		$subscriptionID = 'projects/' . static::$projectId . '/subscriptions/' . $subscriptionName;
		$subscriptionsResource = static::$service->projects_subscriptions;
		$pullRequest = new \Google_Service_Pubsub_PullRequest();
		$pullRequest->setMaxMessages($maxMessages);
		$pullRequest->setReturnImmediately($returnImmediately);
		$pullResponse = $subscriptionsResource->pull($subscriptionID, $pullRequest);
		$receivedMessages = $pullResponse->getReceivedMessages();
		$return = array();
		foreach ($receivedMessages as $receivedMessage) {
			$message = $receivedMessage->getMessage();
			$return[] = array(
				'ackId'       => $receivedMessage->getAckId(),
				'attributes'  => $message->getAttributes(),
				'data'        => base64_decode($message->getData()),
				'messageId'   => $message->getMessageId(),
				'publishTime' => $message->getPublishTime(),
			);
		}
		return $return;
	}

	public static function acknowledge($ackIds)
	{
		$subscriptionName = static::$subscriptionName;

		$subscription = static::getSubscription();
		if (!is_object($subscription)) {
			/*
			 * ALARM BELLS RINGING !!
			 */
			return false;
		}
		$subscriptionID = 'projects/' . static::$projectId . '/subscriptions/' . $subscriptionName;
		$subscriptionsResource = static::$service->projects_subscriptions;
		$acknowledgeRequest = new \Google_Service_Pubsub_AcknowledgeRequest();
		$ackIds = (array) $ackIds;
		$acknowledgeRequest->setAckIds($ackIds);
		$subscriptionsResource->acknowledge($subscriptionID, $acknowledgeRequest);
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