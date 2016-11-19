<?php

namespace Drupal\elastic_email\Service;

use ElasticEmailClient\ApiClient;

class ElasticEmailManager {

  public function __construct() {
    $apiKey = \Drupal::config('elastic_email.settings')->get('api_key');
    ApiClient::SetApiKey($apiKey);
  }

}
