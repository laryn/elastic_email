<?php

namespace Drupal\elastic_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ElasticEmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elastic_email_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['elastic_email.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    // Emails won't get sent if allow_url_fopen is disabled.
    if (ini_get('allow_url_fopen') != 1) {
      drupal_set_message(t("You must enable 'allow_url_fopen' in your php.ini settings to be able to use this service."), 'error');
    }

    $config = \Drupal::config('elastic_email.settings');

    // Fieldset to hold credential fields, and Test fieldset.
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
    ];

    $form['credentials']['username'] = array(
      '#type' => 'textfield',
      '#size' => 48,
      '#title' => $this->t('API username'),
      '#required' => TRUE,
      '#default_value' => $config->get('username'),
      '#description' => $this->t('This is typically your Elastic Email account email address.'),
    );

    $form['credentials']['api_key'] = array(
      '#type' => 'textfield',
      '#size' => 48,
      '#title' => $this->t('API Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('The API Key format is typically') . ' <tt>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</tt>.',
    );

    // DIV to hold the results of the AJAX test call.
    $form['credentials']['test']['elastic-email-test-wrapper'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="elastic-email-test-wrapper">',
      '#suffix' => '</div>',
    ];

    // Fieldset for other options.
    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    ];

    $form['options']['queue_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Queue outgoing messages'),
      '#description' => $this->t('When checked, outgoing messages will be queued via Drupal core system queue, and delivered when the queue is emptied at cron time. When unchecked, messages are delivered immediately (synchronously). Note that synchronous delivery can cause delay in page execution time.') .
        '<br /><br />' . $this->t('If enabled, you can use the <a href="@link" target="_blank">Queue UI</a> to view the queue.', array('@link' => 'https://www.drupal.org/project/queue_ui')),
      '#default_value' => $config->get('queue_enabled'),
    );

    $form['options']['log_success'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Log message delivery success'),
      '#description' => $this->t('When checked, a log message will also be generated for <em>successful</em> email delivery. Errors are always logged.'),
      '#default_value' => $config->get('log_success'),
    );

    // Fieldset for other settings.
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['settings']['credit_low_threshold'] = [
      '#type' => 'textfield',
      '#size' => 8,
      '#title' => $this->t('Low Credit Threshold (USD)'),
      '#description' => $this->t('Sets the lower threshold limit value of when to warn admin users about a low credit limit.') .
        '<br />' .
        $this->t('(NOTE: If you are not sending out more than the Elastic Email monthly limit of 25,000 emails, set this value to zero to not show any warning).'),
      '#default_value' => $config->get('credit_low_threshold'),
    ];

    $form['settings']['use_default_channel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a Default Channel'),
      '#description' => $this->t('If no default channel is set, then the default (set by Elastic Email) is the sending email address.<br />Setting a default channel will add this value to every email that is sent, meaning that you can more easily identify email that has come from each specific site within the reporting section.'),
      '#default_value' => $config->get('use_default_channel'),
    ];

    $default_channel = $config->get('default_channel');
    if (empty($default_channel)) {
      $url = parse_url($base_url);
      $default_channel = $url['host'];
    }

    $form['settings']['default_channel'] = [
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 60,
      '#title' => $this->t('Default Channel'),
      '#default_value' => $default_channel,
      '#states' => [
        'visible' => [
          ':input[name="use_default_channel"]' => [
            'checked' => TRUE
            ]
          ]
        ],
    ];

    // Add the normal settings form stuff.
    $form = parent::buildForm($form, $form_state);

    // Return the form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('elastic_email.settings')
      ->set('username', $form_state->getValue('username'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('queue_enabled', $form_state->getValue('queue_enabled'))
      ->set('log_success', $form_state->getValue('log_success'))
      ->set('credit_low_threshold', $form_state->getValue('credit_low_threshold'))
      ->set('use_default_channel', $form_state->getValue('use_default_channel'))
      ->set('default_channel', $form_state->getValue('default_channel'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
