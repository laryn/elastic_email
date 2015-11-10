<?php
namespace Drupal\elastic_email;

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
