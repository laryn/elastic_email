<?php /**
 * @file
 * Contains \Drupal\elastic_email\EventSubscriber\InitSubscriber.
 */

namespace Drupal\elastic_email\EventSubscriber;

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
    if (\Drupal::currentUser()->hasPermission('administer site configuration') && path_is_admin(\Drupal\Core\Url::fromRoute("<current>")->toString())) {
      try {
        $account_data = ElasticEmailApiAccountDetails::getInstance()->makeRequest();
        $credit_low_threshold = \Drupal::config('elastic_email.settings')->get('elastic_email_credit_low_threshold');
        if ($account_data['credit'] <= $credit_low_threshold) {
          drupal_set_message(t('Your Elastic Email credit is getting low - currently at %credit %currency', [
            '%credit' => $account_data['credit'],
            '%currency' => $account_data['currency'],
          ]), 'warning', FALSE);
        }
      }
      
        catch (ElasticEmailException $e) {
      }
    }
  }

}
