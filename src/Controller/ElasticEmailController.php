<?php

namespace Drupal\elastic_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\elastic_email\Service\ElasticEmailManager;
use ElasticEmailClient\ApiException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Default controller for the elastic_email module.
 */
class ElasticEmailController extends ControllerBase {

  public function dashboard() {
    try {
      /** @var ElasticEmailManager $service */
      $service = \Drupal::service('elastic_email.api');
      $accountData = (array) $service->getAccount()->Load();

      $build = [
        '#theme' => 'elastic_email_dashboard',
        '#data' => $accountData,
        '#attached' => [
          'library' => ['elastic_email/admin'],
        ],
      ];

      return $build;
    }
    catch (ApiException $e) {
      $route = Url::fromRoute('elastic_email.admin_settings');
      $params = [
        '%settings' => Link::fromTextAndUrl('settings', $route)->toString(),
      ];
      drupal_set_message(t('You need to configure your Elastic Email %settings.', $params), 'error');
      return new RedirectResponse(Url::fromRoute('elastic_email.admin_settings')->toString());
    }
  }

}
