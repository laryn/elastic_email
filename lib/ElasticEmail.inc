<?php
/**
 * @file
 * Elastic Email API class.
 */

/**
 * Class for handling exceptions.
 */
class ElasticEmailException extends Exception {}

/**
 * Abstract Class for connection to the Elastic Email API.
 */
abstract class ElasticEmailApi {

  /**
   * @var string
   */
  protected $apiCallPath = NULL;

  /**
   * @var array
   */
  protected $apiParameters = array();

  /**
   * @var string
   */
  protected $apiUrl = 'https://api.elasticemail.com/mailer/';

  /**
   * @var null
   */
  protected static $instance = NULL;

  /**
   * Get an instance of an Elastic Email API call.
   *
   * @return ElasticEmailApi
   *   An instance of an Elastic Email API call.
   */
  public static function getInstance() {
    $class = get_called_class();
    if (!isset(self::$instance[$class])) {
      self::$instance[$class] = new $class();
    }

    return new self::$instance[$class]();
  }

  /**
   * Make the relevant API request to Elastic Email.
   *
   * @param bool $cached
   *   Set to define whether you want the cached version of the response.
   *
   * @throws ElasticEmailException
   */
  public function makeRequest($cached = TRUE) {
    if (is_null($this->apiCallPath)) {
      throw new ElasticEmailException('No API call has been specified');
    }

    // Return the cached data if there is any.
    $cacheKey = 'elastic_email_api_call::' . $this->apiCallPath;
    $cachedApiCallResponse = cache_get($cacheKey, 'cache');
    if ($cached && $cachedApiCallResponse && (REQUEST_TIME < $cachedApiCallResponse->expire)) {
      // Get the cached response for the API call.
      $response =  $cachedApiCallResponse->data;
    }
    else {
      // Make the API request.
      $url = $this->apiUrl . $this->apiCallPath . '?' . $this->getApiParameters();
      $response = drupal_http_request($url);

      // Cache lifetime - 5 minutes.
      $expiryTime = REQUEST_TIME + (60 * 5);

      // Save the data in cache.
      cache_set($cacheKey, $response, 'cache', $expiryTime);
    }

    return $this->processResponse($response);
  }

  /**
   * Method to be called on the child class to process the response.
   *
   * @param object $response
   *   The drupal_http_request object from the call to Elastic Email API.
   */
  abstract protected function processResponse($response);

  /**
   * Sets the API call path.
   *
   * The default base URL for the API call is:
   *   https://api.elasticemail.com/mailer/
   *
   * This call path is the additional path that is needed after this URL to be
   * able to make the API call.
   *
   * @param string $api_call_path
   *   The path for the API call.
   */
  protected function setApiCallPath($api_call_path) {
    $this->apiCallPath = $api_call_path;
    $this->setApiAuthUsername();
    $this->setApiAuthApiKey();
  }

  /**
   * Sets the Elastic Email username parameter for the API call.
   */
  protected function setApiAuthUsername() {
    $username = variable_get(ELASTIC_EMAIL_USERNAME, NULL);
    $this->setApiParameters('username', $username);
  }

  /**
   * Sets the Elastic Email username parameter for the API call.
   */
  protected function setApiAuthApiKey() {
    $api_key = variable_get(ELASTIC_EMAIL_API_KEY, NULL);
    $this->setApiParameters('api_key', $api_key);
  }

  /**
   * Sets an API parameter to be used in the API call.
   *
   * @param string $key
   *   The key for the API parameter.
   * @param string $value
   *   The value of the API parameter.
   */
  protected function setApiParameters($key, $value) {
    $this->apiParameters[$key] = $value;
  }

  /**
   * Gets the array of API parameters as a URL string.
   *
   * @return string
   *   The API parameters as a URL string.
   */
  protected function getApiParameters() {
    return http_build_query($this->apiParameters);
  }
}

/**
 * Class for handing the account-details API call.
 */
class ElasticEmailApiAccountDetails extends ElasticEmailApi {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->setApiCallPath('account-details');
  }

  /**
   * Returns the data about the Elastic Email account.
   *
   * @param object $response
   *   The drupal_http_request object from the call to Elastic Email API.
   *
   * @return array
   *   The account details | error message string.
   *
   * @throws ElasticEmailException
   */
  protected function processResponse($response) {
    $accountDetails = simplexml_load_string($response->data);
    if (empty($accountDetails)) {
      throw new ElasticEmailException($response->data);
    }

    $data = array();
    foreach ($accountDetails->children() as $key => $item) {
      $data[$key] = (string) $item;
    }

    return $data;
  }
}

class ElasticEmailApiChannelList extends ElasticEmailApi {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->setApiCallPath('channel/list');
  }

  /**
   * Returns the list of channels from the Elastic Email account.
   *
   * @param object $response
   *   The drupal_http_request object from the call to Elastic Email API.
   *
   * @return array
   *   The list of channels.
   */
  protected function processResponse($response) {
    $channelList = simplexml_load_string($response->data);

    $data = array();
    foreach ($channelList->children() as $item) {
      $name = (string) $item->attributes()->name;
      $data[$name] = $name;
    }

    return $data;
  }
}

class ElasticEmailApiActivityLog extends ElasticEmailApi {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->setApiCallPath('status/log');
    $this->setApiParameters('format', 'csv');
  }

  public function setParams($status, $channel, $from_date, $to_date) {
    $this->setApiParameters('status', $status);
    $this->setApiParameters('channel', $channel);
    $this->setApiParameters('from', $from_date);
    $this->setApiParameters('to', $to_date);
  }

  /**
   * Returns the data about the Elastic Email account.
   *
   * @param object $response
   *   The drupal_http_request object from the call to Elastic Email API.
   *
   * @return array
   *   The account details | error message string.
   *
   * @throws ElasticEmailException
   */
  protected function processResponse($response) {
    $lines = explode(PHP_EOL, trim($response->data));

    $data = array();
    foreach ($lines as $line) {
      $data[] = str_getcsv($line);
    }
    // Remove the header row.
    array_shift($data);

    return $data;
  }
}