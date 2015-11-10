<?php /**
 * @file
 * Contains \Drupal\elastic_email\Controller\DefaultController.
 */

namespace Drupal\elastic_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\elastic_email\ElasticEmailApiAccountDetails;
use Drupal\elastic_email\ElasticEmailException;

/**
 * Default controller for the elastic_email module.
 */
class DefaultController extends ControllerBase {

  public function elastic_email_dashboard() {
    // Add CSS to make the AJAX part of the form look a little better.
    _elastic_email_add_admin_css();

    if (!_elastic_email_has_valid_settings()) {
      drupal_set_message(t('You need to configure your Elastic Email settings.'), 'error');
    }

    try {
      $data = ElasticEmailApiAccountDetails::getInstance()->makeRequest(FALSE);
      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      //
      //
      // @see https://www.drupal.org/node/2195739
      // return theme('elastic_email_dashboard', array('data' => $data));

    }
    catch (ElasticEmailException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    return '';
  }

}
