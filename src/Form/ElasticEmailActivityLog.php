<?php

/**
 * @file
 * Contains \Drupal\elastic_email\Form\ElasticEmailActivityLog.
 */

namespace Drupal\elastic_email\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ElasticEmailActivityLog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elastic_email_activity_log';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if (!_elastic_email_has_valid_settings()) {
      drupal_set_message(t('You need to configure your Elastic Email settings.'), 'error');
      return $form;
    }

    global $base_url;

    // Add CSS to make the AJAX part of the form look a little better.
    _elastic_email_add_admin_css();

    $form['text'] = [
      '#markup' => t('The following log information only provides data from the last 30 days. For a full report on your emails, visit the <a href="https://elasticemail.com/account">Elastic Email</a> main dashboard.')
      ];

    $form['search'] = [
      '#type' => 'fieldset',
      '#title' => t('Search Options'),
      '#attributes' => [
        'class' => [
          'container-inline',
          'ee-admin-container',
        ]
        ],
    ];

    // @todo set constants for these.
    $form['search']['status'] = [
      '#type' => 'select',
      '#title' => t('Email Status'),
      '#options' => [
        0 => 'All',
        1 => 'Ready To Send',
        2 => 'In Progress',
        4 => 'Bounced',
        5 => 'Sent',
        6 => 'Opened',
        7 => 'Clicked',
        8 => 'Unsubscribed',
        9 => 'Abuse Report',
      ],
    ];

    try {
      // Get the channel list.
      $channel_list = ElasticEmailApiChannelList::getInstance()->makeRequest();
    }
    
      catch (ElasticEmailException $e) {
      drupal_set_message($e->getMessage(), 'error');
      return [];
    }

    $url = parse_url($base_url);

    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/elastic_email.settings.yml and config/schema/elastic_email.schema.yml.
    $form['search']['channel'] = [
      '#type' => 'select',
      '#title' => t('Select the Channel'),
      '#options' => $channel_list,
      '#default_value' => \Drupal::config('elastic_email.settings')->get('elastic_email_default_channel'),
    ];

    $date_format = 'd/m/Y h:i A';
    $from_value = format_date(REQUEST_TIME - (60 * 60 * 24 * 30), 'custom', 'Y-m-d H:i:s');
    $to_value = format_date(REQUEST_TIME + (60 * 60 * 0.25), 'custom', 'Y-m-d H:i:s');

    $form['search']['date_from'] = [
      '#type' => 'date_select',
      '#title' => t('Date From'),
      '#default_value' => $from_value,
      '#date_format' => $date_format,
      '#date_label_position' => 'within',
      '#date_timezone' => 'Europe/London',
      '#date_increment' => 15,
      '#date_year_range' => '0:0',
    ];

    $form['search']['date_to'] = [
      '#type' => 'date_select',
      '#title' => t('Date To'),
      '#default_value' => $to_value,
      '#date_format' => $date_format,
      '#date_label_position' => 'within',
      '#date_timezone' => 'Europe/London',
      '#date_increment' => 15,
      '#date_year_range' => '0:0',
    ];

    $form['search']['apply'] = [
      '#type' => 'button',
      '#value' => t('Apply'),
      '#ajax' => [
        'callback' => 'elastic_email_activity_log_table',
        'wrapper' => 'elastic-email-activity-log-results',
        'method' => 'replace',
      ],
    ];

    $form['results'] = [
      '#type' => 'markup',
      '#prefix' => '<div class="ee-activity-log">',
      '#suffix' => '</div>',
    ];

    $form['results']['wrapper'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="elastic-email-activity-log-results">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

}
