<?php
namespace Drupal\elastic_email\Api;

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
   * @param string $data
   *   The drupal_http_request object from the call to Elastic Email API.
   *
   * @return array
   *   The account details | error message string.
   *
   * @throws ElasticEmailException
   */
  protected function processResponse($data) {
    $accountDetails = simplexml_load_string($data);
    if (empty($accountDetails)) {
      throw new ElasticEmailException($data);
    }

    $data = array();
    foreach ($accountDetails->children() as $key => $item) {
      $data[$key] = (string) $item;
    }

    return $data;
  }
}
