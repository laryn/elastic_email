<?php 

namespace Drupal\elastic_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\elastic_email\Api\ElasticEmailApiAccountDetails;
use Drupal\elastic_email\Api\ElasticEmailException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Default controller for the elastic_email module.
 */
class ElasticEmailController extends ControllerBase {

  public function dashboard() {
    try {
      /** @var ElasticEmailApiAccountDetails $service */
      $service = \Drupal::service('elastic_email.api.account_details');
      $data = $service->makeRequest(FALSE);

      $build = [
        '#theme' => 'elastic_email_dashboard',
        '#data' => $data,
        '#attached' => [
          'library' => ['elastic_email/admin'],
        ],
      ];

      return $build;
    }
    catch (ElasticEmailException $e) {
      $route = Url::fromRoute('elastic_email.admin_settings');
      $params = [
        '%settings' => Link::fromTextAndUrl('settings', $route)->toString(),
      ];
      drupal_set_message(t('You need to configure your Elastic Email %settings.', $params), 'error');
      return new RedirectResponse(Url::fromRoute('elastic_email.admin_settings')->toString());
    }
  }

}
