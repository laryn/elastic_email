<?php
/**
 * @file
 * Module configuration code (form builder and AJAX test function callback).
 */

/**
 * Dashboard information.
 */
function elastic_email_dashboard() {
  // Add CSS to make the AJAX part of the form look a little better.
  _elastic_email_add_admin_css();

  $data = ElasticEmailApiAccountDetails::getInstance()->makeRequest(FALSE);

  return theme('elastic_email_dashboard', array('data' => $data));
}

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
  global $base_url;

  // Add CSS to make the AJAX part of the form look a little better.
  _elastic_email_add_admin_css();

  // Get the webmaster's email address... used below.
  $site_mail = variable_get('site_mail', NULL);

  // Emails won't get sent if allow_url_fopen is disabled.
  if (ini_get('allow_url_fopen') != 1) {
    drupal_set_message(t("You must enable 'allow_url_fopen' in your php.ini settings to be able to use this service."), 'error');
  }

  // Fieldset to hold credential fields, and Test fieldset.
  $form['credentials'] = array(
    '#type' => 'fieldset',
    '#title' => t('API Credentials'),
  );

  $form['credentials'][ELASTIC_EMAIL_USERNAME] = array(
    '#type' => 'textfield',
    '#size' => 48,
    '#title' => t('API username'),
    '#required' => TRUE,
    '#default_value' => variable_get(ELASTIC_EMAIL_USERNAME, ''),
    '#description' => t('This is typically your Elastic Email account email address.')
  );

  $form['credentials'][ELASTIC_EMAIL_API_KEY] = array(
    '#type' => 'textfield',
    '#size' => 48,
    '#title' => t('API Key'),
    '#required' => TRUE,
    '#default_value' => variable_get(ELASTIC_EMAIL_API_KEY, ''),
    '#description' => t('The API Key format is typically <tt>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</tt>.')
  );

  $form['credentials']['test'] = array(
    '#type' => 'fieldset',
    '#title' => t('Test'),
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    '#attributes' => array('class' => array('credentials-test-fs')),
    '#description' => t('Attempt to send a test email to <em>!site_mail</em> to verify Elastic Email configuration.',
      array('!site_mail' => $site_mail))
  );

  // Button for testing the credentials.
  $form['credentials']['test']['test_config_button'] = array(
    '#type' => 'submit',
    '#value' => t('Send Test Email (Plain Text)'),
    '#executes_submit_callback' => FALSE,
    '#ajax' => array(
      'callback' => 'elastic_email_test_config',
      'wrapper' => 'elastic-email-test-wrapper',
      'effect' => 'fade',
      'method' => 'replace',
    )
  );

  // Button for testing the credentials.
  $form['credentials']['test']['test_html_config_button'] = array(
    '#type' => 'submit',
    '#value' => t('Send Test Email (HTML)'),
    '#executes_submit_callback' => FALSE,
    '#ajax' => array(
      'callback' => 'elastic_email_test_html_config',
      'wrapper' => 'elastic-email-test-wrapper',
      'effect' => 'fade',
      'method' => 'replace',
    )
  );

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
  );

  $form['options'][ELASTIC_EMAIL_QUEUE_ENABLED] = array(
    '#type' => 'checkbox',
    '#title' => t('Queue outgoing messages'),
    '#description' => t('When checked, outgoing messages will be queued via Drupal core system queue, and delivered when the queue is emptied at cron time. When unchecked, messages are delivered immediately (synchronously). Note that synchronous delivery can cause delay in page execution time.') .
      '<br /><br />' . t('If enabled, you can use the <a href="@link" target="_blank">Queue UI</a> to view the queue.', array('@link' => 'https://www.drupal.org/project/queue_ui')),
    '#default_value' => variable_get(ELASTIC_EMAIL_QUEUE_ENABLED, FALSE)
  );

  $form['options'][ELASTIC_EMAIL_LOG_SUCCESS] = array(
    '#type' => 'checkbox',
    '#title' => t('Log message delivery success'),
    '#description' => t('When checked, a log message will also be generated for <em>successful</em> email delivery. Errors are always logged.'),
    '#default_value' => variable_get(ELASTIC_EMAIL_LOG_SUCCESS, FALSE)
  );

  // Fieldset for other settings.
  $form['settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Settings'),
  );

  $form['settings']['elastic_email_credit_low_threshold'] = array(
    '#type' => 'textfield',
    '#size' => 8,
    '#title' => t('Low Credit Threshold (USD)'),
    '#description' => t('Sets the lower threshold limit value of when to warn admin users about a low credit limit.'),
    '#default_value' => variable_get('elastic_email_credit_low_threshold', 0)
  );

  $form['settings']['elastic_email_use_default_channel'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use a Default Channel'),
    '#description' => t('If no default channel is set, then the default (set by Elastic Email) is the sending email address.<br />Setting a default channel will add this value to every email that is sent, meaning that you can more easily identify email that has come from each specific site within the reporting section.'),
    '#default_value' => variable_get('elastic_email_use_default_channel', FALSE)
  );

  $url = parse_url($base_url);
  $form['settings']['elastic_email_default_channel'] = array(
    '#type' => 'textfield',
    '#size' => 48,
    '#maxlength' => 60,
    '#title' => t('Default Channel'),
    '#default_value' => variable_get('elastic_email_default_channel', $url['host']),
    '#states' => array(
      'visible' => array(
        ':input[name="elastic_email_use_default_channel"]' => array('checked' => TRUE),
      ),
    ),
  );

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
function elastic_email_test_config($form, &$form_state, $is_html = FALSE) {
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

    if (!$is_html) {
      $text_body = t('This is a test of the Drupal Elastic Email module configuration.')
        . "\n\n"
        . t('Message generated: !time',
          array('!time' => format_date(REQUEST_TIME, 'custom', 'r')));
      $html_body = NULL;
      $subject = t('Elastic Email module: configuration test email');
    }
    else {
      $text_body = NULL;
      $html_body = '<html><body><p><strong>' .
        t('This is a HTML test of the Drupal Elastic Email module configuration.')
          . '</strong></p><p>'
          . t('Message generated: !time',
            array('!time' => format_date(REQUEST_TIME, 'custom', 'r')))
        . '</p></body></html>';
      $subject = t('Elastic Email module: configuration HTML test email');
    }

    $result = ElasticEmailMailSystem::elasticEmailSend(
      $site_mail,
      NULL,
      $site_mail,
      $subject,
      $text_body,
      $html_body,
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

/**
 * AJAX callback function to test the sending of HTML email.
 *
 * This calls the main test function, but sets the $is_html.
 *
 * @see elastic_email_test_config
 *
 * @return string
 *   HTML 'success' or 'fail' snippet in JSON format
 */
function elastic_email_test_html_config($form, &$form_state) {
  return elastic_email_test_config($form, $form_state, TRUE);
}

/**
 * Menu callback for the activity log.
 */
function elastic_email_activity_log($form, &$form_state) {
  global $base_url;

  // Add CSS to make the AJAX part of the form look a little better.
  _elastic_email_add_admin_css();

  $form['text'] = array(
    '#markup' => t('The following log information only provides data from the last 30 days. For a full report on your emails, visit the <a href="https://elasticemail.com/account">Elastic Email</a> main dashboard.'),
  );

  $form['search'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search Options'),
    '#attributes' => array(
      'class' => array(
        'container-inline',
        'ee-admin-container',
      ),
    ),
  );

  // @todo set constants for these.
  $form['search']['status'] = array(
    '#type' => 'select',
    '#title' => t('Email Status'),
    '#options' => array(
      0 => 'All',
      1 => 'Ready To Send',
      2 => 'In Progress',
      4 => 'Bounced',
      5 => 'Sent',
      6 => 'Opened',
      7 => 'Clicked',
      8 => 'Unsubscribed',
      9 => 'Abuse Report',
    ),
  );

  // Get the channel list.
  $channel_list = ElasticEmailApiChannelList::getInstance()->makeRequest();
  $url = parse_url($base_url);

  $form['search']['channel'] = array(
    '#type' => 'select',
    '#title' => t('Select the Channel'),
    '#options' => $channel_list,
    '#default_value' => variable_get('elastic_email_default_channel', $url['host'])
  );

  $date_format = 'd/m/Y h:i A';
  $from_value = format_date(REQUEST_TIME - (60 * 60 * 24 * 30), 'custom', 'Y-m-d H:i:s');
  $to_value = format_date(REQUEST_TIME + (60 * 60 * 0.25), 'custom', 'Y-m-d H:i:s');

  $form['search']['date_from'] = array(
    '#type' => 'date_select',
    '#title' => t('Date From'),
    '#default_value' => $from_value,
    '#date_format' => $date_format,
    '#date_label_position' => 'within',
    '#date_timezone' => 'Europe/London',
    '#date_increment' => 15,
    '#date_year_range' => '0:0',
  );

  $form['search']['date_to'] = array(
    '#type' => 'date_select',
    '#title' => t('Date To'),
    '#default_value' => $to_value,
    '#date_format' => $date_format,
    '#date_label_position' => 'within',
    '#date_timezone' => 'Europe/London',
    '#date_increment' => 15,
    '#date_year_range' => '0:0',
  );

  $form['search']['apply'] = array(
    '#type' => 'button',
    '#value' => t('Apply'),
    '#ajax' => array(
      'callback' => 'elastic_email_activity_log_table',
      'wrapper' => 'elastic-email-activity-log-results',
      'method' => 'replace',
    ),
  );

  $form['results'] = array(
    '#type' => 'markup',
    '#prefix' => '<div class="ee-activity-log">',
    '#suffix' => '</div>'
  );

  $form['results']['wrapper'] = array(
    '#type' => 'markup',
    '#prefix' => '<div id="elastic-email-activity-log-results">',
    '#suffix' => '</div>'
  );

  return $form;
}

/**
 * AJAX callback function for the activity log results.
 */
function elastic_email_activity_log_table($form, &$form_state) {
  $output = '';

  $status = $form_state['input']['status'];
  $channel = $form_state['input']['channel'];
  $from_date_field = $form_state['input']['date_from'];
  $to_date_field = $form_state['input']['date_to'];

  $from_date = _elastic_email_format_date($from_date_field);
  $to_date = _elastic_email_format_date($to_date_field);

  $activity_log = ElasticEmailApiActivityLog::getInstance();
  $activity_log->setParams($status, $channel, $from_date, $to_date);
  $data = $activity_log->makeRequest(FALSE);

  if (count($data) < 1) {
    $data[] = array(
      array(
        'data' => 'No records available.',
        'colspan' => 5,
      )
    );
  }

  $activity_list = array();
  foreach ($data as $row) {
    // Remove message, bounce cat., msg-id, trans-id.
    unset($row[4], $row[5], $row[6], $row[7]);
    $activity_list[] = $row;
  }

  $table_data['header'] = array(
    'to',
    'status',
    'channel',
    'date',
    /*'message',
    'bounce cat.',
    'msg-id',
    'trans-id',*/
    'subject'
  );
  $table_data['rows'] = $activity_list;

  $output .= theme('table', $table_data);

  $element = $form['results']['wrapper'];
  $element['#markup'] = $output;
  return $element;
}

/**
 * Helper function which adds the admin css file to the page.
 */
function _elastic_email_add_admin_css() {
  $path = drupal_get_path('module', 'elastic_email');
  drupal_add_css($path . '/css/elastic_email.admin.css');
}

/**
 * Helper function to format a UK format date to American format.
 *
 * @param array $date_field
 *   The date field array.
 *
 * @return string
 *   The formatted date.
 */
function _elastic_email_format_date($date_field) {
  $data = sprintf('%d/%d/%d', $date_field['month'], $date_field['day'], $date_field['year']);
  $time = sprintf('%s:%s %s', $date_field['hour'], $date_field['minute'], $date_field['ampm']);

  return $data . ' ' . $time;
}