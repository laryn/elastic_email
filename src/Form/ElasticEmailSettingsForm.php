<?php

/**
 * @file
 * Contains \Drupal\elastic_email\Form\ElasticEmailSettingsForm.
 */

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('elastic_email.settings');
    $config->set('username', $form['credentials']['username']['#value']);
    $config->set('api_key', $form['credentials']['api_key']['#value']);
    $config->set('queue_enabled', $form['options']['queue_enabled']['#value']);
    $config->set('log_success', $form['options']['log_success']['#value']);
    $config->set('credit_low_threshold', $form['settings']['credit_low_threshold']['#value']);
    $config->set('use_default_channel', $form['settings']['use_default_channel']['#value']);
    $config->set('default_channel', $form['settings']['default_channel']['#value']);
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
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
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    global $base_url;

    // Emails won't get sent if allow_url_fopen is disabled.
    if (ini_get('allow_url_fopen') != 1) {
      drupal_set_message(t("You must enable 'allow_url_fopen' in your php.ini settings to be able to use this service."), 'error');
    }

    // Fieldset to hold credential fields, and Test fieldset.
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => t('API Credentials'),
    ];

    $form['credentials']['username'] = array(
      '#type' => 'textfield',
      '#size' => 48,
      '#title' => t('API username'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('elastic_email.settings')->get('username'),
      '#description' => t('This is typically your Elastic Email account email address.'),
    );

    $form['credentials']['api_key'] = array(
      '#type' => 'textfield',
      '#size' => 48,
      '#title' => t('API Key'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('elastic_email.settings')->get('api_key'),
      '#description' => t('The API Key format is typically <tt>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</tt>.'),
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
      '#title' => t('Options'),
    ];

    $form['options']['queue_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Queue outgoing messages'),
      '#description' => t('When checked, outgoing messages will be queued via Drupal core system queue, and delivered when the queue is emptied at cron time. When unchecked, messages are delivered immediately (synchronously). Note that synchronous delivery can cause delay in page execution time.') .
        '<br /><br />' . t('If enabled, you can use the <a href="@link" target="_blank">Queue UI</a> to view the queue.', array('@link' => 'https://www.drupal.org/project/queue_ui')),
      '#default_value' => \Drupal::config('elastic_email.settings')->get('queue_enabled'),
    );

    $form['options']['log_success'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log message delivery success'),
      '#description' => t('When checked, a log message will also be generated for <em>successful</em> email delivery. Errors are always logged.'),
      '#default_value' => \Drupal::config('elastic_email.settings')->get('log_success'),
    );

    // Fieldset for other settings.
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Settings'),
    ];

    $form['settings']['credit_low_threshold'] = [
      '#type' => 'textfield',
      '#size' => 8,
      '#title' => t('Low Credit Threshold (USD)'),
      '#description' => t('Sets the lower threshold limit value of when to warn admin users about a low credit limit.'),
      '#default_value' => \Drupal::config('elastic_email.settings')->get('credit_low_threshold'),
    ];

    $form['settings']['use_default_channel'] = [
      '#type' => 'checkbox',
      '#title' => t('Use a Default Channel'),
      '#description' => t('If no default channel is set, then the default (set by Elastic Email) is the sending email address.<br />Setting a default channel will add this value to every email that is sent, meaning that you can more easily identify email that has come from each specific site within the reporting section.'),
      '#default_value' => \Drupal::config('elastic_email.settings')->get('use_default_channel'),
    ];

    $url = parse_url($base_url);
    $form['settings']['default_channel'] = [
      '#type' => 'textfield',
      '#size' => 48,
      '#maxlength' => 60,
      '#title' => t('Default Channel'),
      '#default_value' => \Drupal::config('elastic_email.settings')->get('default_channel'),
      '#states' => [
        'visible' => [
          ':input[name="elastic_email_use_default_channel"]' => [
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

}
