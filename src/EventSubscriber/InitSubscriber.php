<?php

namespace Drupal\elastic_email\EventSubscriber;

use Drupal\elastic_email\Api\ElasticEmailApiAccountDetails;
use Drupal\elastic_email\Api\ElasticEmailException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent() {
    if (!\Drupal::currentUser()->hasPermission('administer site configuration')
      && !\Drupal::service('router.admin_context')->isAdminRoute()) {
      return;
    }

    try {
      /** @var ElasticEmailApiAccountDetails $service */
      $service = \Drupal::service('elastic_email.api.account_details');
      $accountData = $service->makeRequest();

      $creditLowThreshold = \Drupal::config('elastic_email.settings')->get('credit_low_threshold');
      if (!empty($creditLowThreshold) && $accountData['credit'] <= $creditLowThreshold) {
        drupal_set_message(t('Your Elastic Email credit is getting low - currently at %credit %currency', [
          '%credit' => $accountData['credit'],
          '%currency' => $accountData['currency'],
        ]), 'warning', FALSE);
      }
    }
    catch (ElasticEmailException $e) {
    }
  }

}
