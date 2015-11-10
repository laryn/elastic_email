<?php

/**
 * @file
 * Contains \Drupal\elastic_email\Form\ElasticEmailSendTest.
 */

namespace Drupal\elastic_email\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ElasticEmailSendTest extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elastic_email_send_test';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $site_mail = variable_get('site_mail', NULL);


    if (!_elastic_email_has_valid_settings()) {
      drupal_set_message(t('You need to configure your Elastic Email settings.'), 'error');
      return $form;
    }

    $form['elastic_email_test_email_to'] = [
      '#type' => 'textfield',
      '#size' => 40,
      '#title' => t('Email address to send a test email to'),
      '#description' => t('Enter the email address that you would like to send a test email to.'),
      '#required' => TRUE,
      '#default_value' => $site_mail,
    ];

    $form['elastic_email_test_email_subject'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => t('Test Email Subject'),
      '#description' => t('Enter the subject that you would like to send with the test email.'),
      '#required' => TRUE,
      '#default_value' => t('Elastic Email module: configuration test email'),
    ];

    $text_body = t('This is a test of the Drupal Elastic Email module configuration.') . "\n\n" . t('Message generated: !time', [
      '!time' => format_date(REQUEST_TIME, 'custom', 'r')
      ]);

    $form['elastic_email_test_email_body'] = [
      '#type' => 'textarea',
      //'#size' => 8,
    '#title' => t('Test email body contents'),
      '#description' => t('Enter the email body that you would like to send.'),
      '#default_value' => $text_body,
    ];

    $form['elastic_email_test_email_html'] = [
      '#type' => 'checkbox',
      '#title' => t('Send as HTML?'),
      '#description' => t('Check this to send a test email as HTML.'),
      '#default_value' => FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $site_mail = variable_get('site_mail', NULL);

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $username = variable_get(ELASTIC_EMAIL_USERNAME, '');

    // @FIXME
// // @FIXME
// // The correct configuration object could not be determined. You'll need to
// // rewrite this call manually.
// $api_key  = variable_get(ELASTIC_EMAIL_API_KEY, '');


    $to = $form_state->getValue(['elastic_email_test_email_to']);
    $subject = $form_state->getValue(['elastic_email_test_email_subject']);

    if ($form_state->getValue(['elastic_email_test_email_html'])) {
      $text_body = NULL;
      $html_body = $form_state->getValue(['elastic_email_test_email_body']);
    }
    else {
      $text_body = $form_state->getValue(['elastic_email_test_email_body']);
      $html_body = NULL;
    }

    $result = ElasticEmailMailSystem::elasticEmailSend($site_mail, NULL, $to, $subject, $text_body, $html_body, $username, $api_key);


    if (isset($result['error'])) {
      // There was an error. Return error HTML.
      drupal_set_message(t('Failed to send a test email to %test_to. Got the following error: %error_msg', [
        '%test_to' => $to,
        '%error_msg' => $result['error'],
      ]), 'error');
    }
    else {
      // Success!
      drupal_set_message(t('Successfully sent a test email to %test_to', [
        '%test_to' => $to
        ]));
    }
  }

}
