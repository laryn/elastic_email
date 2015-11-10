<?php
namespace Drupal\elastic_email;

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