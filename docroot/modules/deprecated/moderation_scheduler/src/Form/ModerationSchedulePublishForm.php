<?php

namespace Drupal\moderation_scheduler\Form;

use Drupal\Core\CronInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\moderation_scheduler\ModerationSchedulerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with moderation_scheduler on how to use cron.
 */
class ModerationSchedulePublishForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;


  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\moderation_scheduler\ModerationSchedulerService
   */
  protected $moderationSchedulerService;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $current_user, CronInterface $cron, StateInterface $state, ModerationSchedulerService $moderationSchedulerService) {
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->state = $state;
    $this->moderationSchedulerService = $moderationSchedulerService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
        $container->get('current_user'), $container->get('cron'), $container->get('state'), $container->get('moderation_scheduler.services')
    );
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moderation_scheduler';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $caption = '<p>' . $this->t('Runnung Moderation scheduler content u will pubblish all content with field Moderation Scheduled Publish Time filled.') . '</p>';

    $form['description'] = ['#markup' => $caption];
    $form['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('If you have administrative privileges you can run cron from this page and see the results.'),
    ];
    if ($this->currentUser->hasPermission('edit moderation scheduler field')) {
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run cron now'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Main function to check scheduled nodes and publish.
    $result = $this->moderationSchedulerService->publishScheduled();

    // Return message with results of scheduling.
    if (isset($result)) {
      $this->moderationSchedulerService->cleanFieldScheduledTimeRevision('field_scheduled_time');
      $resultMessage = moderation_scheduler_result_message($result);
      $result = Markup::create($resultMessage);
    }
    $this->messenger()->addMessage($this->t('moderation_scheduler executed at %time with results: %results', ['%time' => date('c', \Drupal::time()->getRequestTime()), '%results' => $result]));
    $url = Url::fromRoute('system.admin_content');
    $form_state->setRedirectUrl($url);
  }

}
