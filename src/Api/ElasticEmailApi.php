<?php
namespace Drupal\elastic_email\Api;

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

    if (!$this->hasValidApiKeys()) {
      throw new ElasticEmailException('Invalid API credentials for: ' . $this->apiCallPath);
    }

    // Return the cached data if there is any.
    $cacheKey = 'elastic_email_api_call::' . $this->apiCallPath;
    $cachedApiCallResponse = \Drupal::cache()->get($cacheKey);

    $data = NULL;
    if ($cached && $cachedApiCallResponse && (REQUEST_TIME < $cachedApiCallResponse->expire)) {
      // Get the cached response for the API call.
      $data = $cachedApiCallResponse->data;
    }
    else {
      // Make the API request.
      $url = $this->apiUrl . $this->apiCallPath . '?' . $this->getApiFormattedParameters();

      /** @var \GuzzleHttp\Client $client */
      try {
        $response = \Drupal::httpClient()->get($url);
        $data = (string) $response->getBody();

        // Cache lifetime - 5 minutes.
        $expiryTime = REQUEST_TIME + (60 * 5);

        // Save the data in cache.
        \Drupal::cache()->set($cacheKey, $data, $expiryTime);
      }
      catch (\Exception $e) {
        watchdog_exception('elastic_email', $e);
      }
    }

    $this->validateResponse($data);
    return $this->processResponse($data);
  }

  /**
   * Method to be called on the child class to process the response.
   *
   * @param string $data
   *   The data returned from the call to Elastic Email API.
   */
  abstract protected function processResponse($data);

  /**
   * Validates the response from the API.
   *
   * @param string $data
   *   The response object from the drupal_http_request call.
   *
   * @throws ElasticEmailException
   */
  protected function validateResponse($data) {
    /*if (empty($response->data) && isset($response->error)) {
      drupal_set_message('Elastic Email error: ' . $response->error, 'warning');
      throw new ElasticEmailException('Elastic Email error: ' . $response->error);
    }*/
    if (substr($data, 0, strlen('Unauthorized:')) == 'Unauthorized:') {
      throw new ElasticEmailException('Elastic Email: Invalid API credentials set.');
    }
  }

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
    $username = \Drupal::config('elastic_email.settings')->get('username');
    $this->setApiParameter('username', $username);
  }

  /**
   * Sets the Elastic Email username parameter for the API call.
   */
  protected function setApiAuthApiKey() {
    $api_key = \Drupal::config('elastic_email.settings')->get('api_key');
    $this->setApiParameter('api_key', $api_key);
  }

  /**
   * Checks to see if the API keys have been set.
   *
   * @return bool
   *   If the API keys are valid.
   */
  protected function hasValidApiKeys() {
    $username = $this->getApiParameter('username');
    $api_key  = $this->getApiParameter('api_key');
    if (is_null($username) || $username == '') {
      return FALSE;
    }
    if (is_null($api_key) || $api_key == '') {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Sets an API parameter to be used in the API call.
   *
   * @param string $key
   *   The key for the API parameter.
   * @param string $value
   *   The value of the API parameter.
   */
  protected function setApiParameter($key, $value) {
    $this->apiParameters[$key] = $value;
  }

  /**
   * Gets the API parameter.
   *
   * @param string $key
   *   The key for the API parameter.
   *
   * @return mixed
   *   The value of the parameter.
   */
  protected function getApiParameter($key) {
    return $this->apiParameters[$key];
  }

  /**
   * Gets the array of API parameters as a URL string.
   *
   * @return string
   *   The API parameters as a URL string.
   */
  protected function getApiFormattedParameters() {
    return http_build_query($this->apiParameters);
  }
}
