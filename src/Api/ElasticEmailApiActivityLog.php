<?php
namespace Drupal\elastic_email\Api;

class ElasticEmailApiActivityLog extends ElasticEmailApi {

  /**
   * Constructor.
   */
  public function __construct() {
    $this->setApiCallPath('status/log');
    $this->setApiParameter('format', 'csv');
  }

  public function setParams($status, $channel, $from_date, $to_date) {
    $this->setApiParameter('status', $status);
    $this->setApiParameter('channel', $channel);
    $this->setApiParameter('from', $from_date);
    $this->setApiParameter('to', $to_date);
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
    $lines = explode(PHP_EOL, trim($data));

    $data = array();
    foreach ($lines as $line) {
      $data[] = str_getcsv($line);
    }
    // Remove the header row.
    array_shift($data);

    return $data;
  }
}
