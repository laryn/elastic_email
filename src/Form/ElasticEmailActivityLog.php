<?php

namespace Drupal\elastic_email\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\elastic_email\Api\ElasticEmailApiActivityLog;
use Drupal\elastic_email\Api\ElasticEmailApiChannelList;
use Drupal\elastic_email\Api\ElasticEmailException;

class ElasticEmailActivityLog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elastic_email_activity_log';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $config = \Drupal::config('elastic_email.settings');

    $form['text'] = [
      '#markup' => $this->t('The following log information only provides data from the last 30 days. For a full report on your emails, visit the <a href="https://elasticemail.com/account">Elastic Email</a> main dashboard.')
    ];

    $form['search'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search Options'),
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
      '#title' => $this->t('Email Status'),
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

    $channelList = $this->getChannelList();

    $url = parse_url($base_url);
    $defaultChannel = $config->get('default_channel');
    if (empty($defaultChannel)) {
      $defaultChannel = $url['host'];
    }

    $form['search']['channel'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the Channel'),
      '#options' => $channelList,
      '#default_value' => $defaultChannel,
    ];

    /** @var DateFormatter $dateFormatter */
    $dateFormatter = \Drupal::service('date.formatter');
    $dateFormat = 'd/m/Y h:i A';
    $fromValue = $dateFormatter->format(REQUEST_TIME, 'custom', 'Y-m-d');
    $toValue = $dateFormatter->format(REQUEST_TIME + (60 * 60 * 24), 'custom', 'Y-m-d');

    $form['search']['date_from'] = [
      '#type' => 'date',
      '#title' => $this->t('Date From'),
      '#default_value' => $fromValue,
      '#date_format' => $dateFormat,
      '#date_label_position' => 'within',
      '#date_timezone' => 'Europe/London',
      '#date_increment' => 15,
      '#date_year_range' => '0:0',
    ];

    $form['search']['date_to'] = [
      '#type' => 'date',
      '#title' => $this->t('Date To'),
      '#default_value' => $toValue,
      '#date_format' => $dateFormat,
      '#date_label_position' => 'within',
      '#date_timezone' => 'Europe/London',
      '#date_increment' => 15,
      '#date_year_range' => '0:0',
    ];

    $form['search']['apply'] = [
      '#type' => 'button',
      '#value' => $this->t('Apply'),
      '#ajax' => [
        'callback' => [$this, 'activityLogTable'],
        'wrapper' => 'elastic-email-activity-log-results',
      ],
    ];

    $form['results'] = [
      '#prefix' => '<div class="ee-activity-log">',
      '#suffix' => '</div>',
    ];

    $form['results']['wrapper'] = [
      '#prefix' => '<div id="elastic-email-activity-log-results">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * Ajax handler to get the account activity log from Elastic Email API.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public function activityLogTable(array &$form, FormStateInterface $form_state) {
    $completeForm = $form_state->getCompleteForm();

    $status = $completeForm['search']['status']['#value'];
    $channel = $completeForm['search']['channel']['#value'];
    $fromDate = $completeForm['search']['date_from']['#value'];
    $toDate = $completeForm['search']['date_to']['#value'];

    $data = $this->getActivityDate($status, $channel, $fromDate, $toDate);

    $tableHeader = [
      'to',
      'status',
      'channel',
      'date time (US Format)',
      /*'message',
      'bounce cat.',
      'msg-id',
      'trans-id',*/
      'subject',
    ];

    $activityData = [];
    foreach ($data as $row) {
      // Remove message, bounce cat., msg-id, trans-id columns from the data.
      unset($row[4], $row[5], $row[6], $row[7]);
      $activityData[] = $row;
    }

    $table = [
      '#theme' => 'table',
      '#header' => $tableHeader,
      '#rows' => $activityData,
      '#empty' => $this->t('No records available.'),
    ];

    $output = '<div id="elastic-email-activity-log-results">' . render($table) . '</div>';

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#elastic-email-activity-log-results',
      $output
    ));

    return $response;
  }

  /**
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method isn't needed as the form has an AJAX handler attached.
  }

  /**
   * Get the channel list from API.
   *
   * @return array
   */
  protected function getChannelList() {
    try {
      /** @var ElasticEmailApiChannelList $channelList */
      $channelList = \Drupal::service('elastic_email.api.channel_list');
      return $channelList->makeRequest();
    }
    catch (ElasticEmailException $e) {
      drupal_set_message($e->getMessage(), 'error');
      return [];
    }
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
  protected function formatDate($date_field) {
    return date('m/d/Y H:i:s', strtotime($date_field));
  }

  /**
   * Get the activity log data from Elastic Email API.
   *
   * @param string $status
   *   The status of t
   * @param string $channel
   *   The channel the the email was sent by.
   * @param string $fromDate
   *   The from date for retrieving data.
   * @param string $toDate
   *   The to date for retrieving data.
   *
   * @return array
   *   The log data from Elastic Email.
   */
  protected function getActivityDate($status, $channel, $fromDate, $toDate) {
    try {
      $fromDate = $this->formatDate($fromDate);
      $toDate = $this->formatDate($toDate);

      /** @var ElasticEmailApiActivityLog $activityLog */
      $activityLog = \Drupal::service('elastic_email.api.activity_log');
      $activityLog->setParams($status, $channel, $fromDate, $toDate);
      return $activityLog->makeRequest(FALSE);
    }
    catch (ElasticEmailException $e) {
      return [$e->getMessage()];
    }
  }

}
