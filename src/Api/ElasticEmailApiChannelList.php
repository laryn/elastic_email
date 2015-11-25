<?php
namespace Drupal\elastic_email\Api;

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
   * @param string $data
   *   The drupal_http_request object from the call to Elastic Email API.
   *
   * @return array
   *   The list of channels.
   */
  protected function processResponse($data) {
    $channelList = simplexml_load_string($data);

    $data = array();
    foreach ($channelList->children() as $item) {
      $name = (string) $item->attributes()->name;
      $data[$name] = $name;
    }

    return $data;
  }
}
