<?php
/**
 * @file
 * Module configuration code (form builder and AJAX test function callback).
 */

/**
 * Form builder for Elastic Email admin settings form.
 *
 * This consists of:
 * - Elastic Email username field
 * - API key field
 * - Test button (AJAX button for testing username/api key)
 * - Queue messages checkbox
 * - Log success message checkbox
 *
 * @return array
 *   The settings form array.
 *
 * @ingroup forms
 */
function elastic_email_settings_form() {
  // Add CSS to make the AJAX part of the form look a little better.
  $path = drupal_get_path('module', 'elastic_email');
  drupal_add_css($path . '/css/elastic_email.admin.css');

  // Get the webmaster's email address... used below.
  $site_mail = variable_get('site_mail', NULL);

  // Emails won't get sent if allow_url_fopen is disabled.
  if (ini_get('allow_url_fopen') != 1) {
    drupal_set_message(t("You must enable 'allow_url_fopen' in your php.ini settings to be able to use this service."), 'error');
  }

  // Fieldset to hold credential fields, and Test fieldset.
  $form['credentials'] = array(
    '#type' => 'fieldset',
    '#title' => t('Credentials'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE);

  $form['credentials'][ELASTIC_EMAIL_USERNAME] = array(
    '#type' => 'textfield',
    '#size' => 48,
    '#title' => t('Elastic Email username'),
    '#required' => TRUE,
    '#default_value' => variable_get(ELASTIC_EMAIL_USERNAME, ''),
    '#description' => t('This is typically your Elastic Email account email address.'));

  $form['credentials'][ELASTIC_EMAIL_API_KEY] = array(
    '#type' => 'textfield',
    '#size' => 48,
    '#title' => t('API Key'),
    '#required' => TRUE,
    '#default_value' => variable_get(ELASTIC_EMAIL_API_KEY, ''),
    '#description' => t('The API Key format is typically <tt>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</tt>.'));

  $form['credentials']['test'] = array(
    '#type' => 'fieldset',
    '#title' => t('Test'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    '#attributes' => array('class' => array('credentials-test-fs')),
    '#description' => t('Attempt to send a test email to <em>!site_mail</em> to verify Elastic Email configuration.',
      array('!site_mail' => $site_mail)));

  // Button for testing the credentials.
  $form['credentials']['test']['test_config_button'] = array(
    '#type' => 'submit',
    '#value' => t('Test'),
    '#executes_submit_callback' => FALSE,
    '#ajax' => array(
      'callback' => 'elastic_email_test_config',
      'wrapper' => 'elastic-email-test-wrapper',
      'effect' => 'fade',
      'method' => 'replace',
    ));

  // DIV to hold the results of the AJAX test call.
  $form['credentials']['test']['elastic-email-test-wrapper'] = array(
    '#type' => 'markup',
    '#prefix' => '<div id="elastic-email-test-wrapper">',
    '#suffix' => '</div>',
  );

  // Fieldset for other options.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Options'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE);

  $form['options'][ELASTIC_EMAIL_QUEUE_ENABLED] = array(
    '#type' => 'checkbox',
    '#title' => t('Queue outgoing messages'),
    '#description' => t('When checked, outgoing messages will be queued via Drupal core system queue, and delivered when the queue is emptied at cron time. When unchecked, messages are delivered immediately (synchronously). Note that synchronous delivery can cause delay in page execution time.'),
    '#default_value' => variable_get(ELASTIC_EMAIL_QUEUE_ENABLED, FALSE));

  $form['options'][ELASTIC_EMAIL_LOG_SUCCESS] = array(
    '#type' => 'checkbox',
    '#title' => t('Log message delivery success'),
    '#description' => t('When checked, a log message will also be generated for <em>successful</em> email delivery. Errors are always logged.'),
    '#default_value' => variable_get(ELASTIC_EMAIL_LOG_SUCCESS, FALSE));

  // Add the normal settings form stuff.
  $form = system_settings_form($form);

  // Return the form.
  return $form;

}

/**
 * AJAX callback function to test Elastic Email service.
 *
 * This method extracts the username and API key parameters from the POST data,
 * and uses them to send a test message via Elastic Email. The !site_mail
 * email address is used for both sender and recipient of the test mail.
 *
 * @return string
 *   HTML 'success' or 'fail' snippet in JSON format
 */
function elastic_email_test_config($form, &$form_state) {
  $site_mail = variable_get('site_mail', NULL);

  if (!$site_mail) {
    $result['error'] = t('Unable to send test email because the site e-mail address is not set. This can be specified on the <a href="@site-settings-url">Site information</a> page.',
      array('@site-settings-url' => url('admin/settings/site-information')));
  }

  $username = check_plain($_POST[ELASTIC_EMAIL_USERNAME]);
  $api_key  = check_plain($_POST[ELASTIC_EMAIL_API_KEY]);
  $result   = array();

  if (empty($api_key)) {
    $result['error'] = t('You must supply an Elastic Email API key.');
  }

  if (empty($username)) {
    $result['error'] = t('You must supply an Elastic Email username.');
  }

  if (!isset($result['error'])) {
    module_load_include('inc', 'elastic_email', 'mail');

    $result = ElasticEmailMailSystem::elasticEmailSend(
      $site_mail,
      NULL,
      $site_mail,
      t('Elastic Email module: configuration test email'),
      t('This is a test of the Drupal Elastic Email module configuration.')
        . "\n\n"
        . t('Message generated: !time',
            array('!time' => format_date(REQUEST_TIME, 'custom', 'r'))),
      $username,
      $api_key);
  }

  if (isset($result['error'])) {
    // There was an error. Return error HTML.
    $data = '<strong class="fail">' . t('Failed.') . '</strong> '
      . t('Reason:') . ' <tt>' . $result['error'] . '</tt>';
  }
  else {
    // Success!
    $data = '<strong class="success">' . t('Success.') . '</strong>';
  }

  $element = $form['credentials']['test']['elastic-email-test-wrapper'];
  $element['#markup'] = $element['#prefix'] . $data . $element['#suffix'];
  return $element;
}
