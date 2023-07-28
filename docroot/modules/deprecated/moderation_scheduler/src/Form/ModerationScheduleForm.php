<?php

namespace Drupal\moderation_scheduler\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Form with moderation_scheduler on how to use cron.
 */
class ModerationScheduleForm extends ConfigFormBase {

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
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, CronInterface $cron, QueueFactory $queue, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, ModuleHandler $moduleHandler) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->queue = $queue;
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
        $container->get('config.factory'), $container->get('current_user'), $container->get('cron'), $container->get('queue'), $container->get('state'), $container->get('entity_type.manager'), $container->get('module_handler')
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
    $config = $this->configFactory->get('moderation_scheduler.settings');
    $node_options = [];
    $node_options_default = [];
    $nodes_type = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();

    foreach ($nodes_type as $type) {
      $node_options[$type->id()] = $type->label();
      $node_options_default[$type->id()] = $type->id();
    }

    if (!empty($config->get('moderation_scheduler_content_types'))) {
      $enabled_content_type = $config->get('moderation_scheduler_content_types');
    }
    else {
      $enabled_content_type = $node_options_default;
    }
    $form['configuration_nodes']['content_types_list'] = [
      '#type' => 'details',
      '#title' => $this->t('Moderation Scheduler - Content types configuration'),
      '#open' => TRUE,
    ];
    $form['configuration_nodes']['content_types_list']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('You can select content types where enable the moderation scheduler, by default is enabled on all content types.'),
    ];
    $form['configuration_nodes']['content_types_list']['moderation_scheduler_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Content Types'),
      '#description' => $this->t('Define what content types will be enabled for content moderation scheduler publish.'),
      '#default_value' => $enabled_content_type,
      '#options' => $node_options,
    ];

    if ($this->moduleHandler->moduleExists('content_moderation')) {
      $form['configuration_moderation']['moderation_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Moderation Scheduler - Content Moderation configuration'),
        '#open' => TRUE,
      ];
      $form['configuration_moderation']['moderation_settings']['moderation_scheduler_enablemoderation'] = [
        '#type' => 'checkbox',
        '#title' => ' ' . $this->t("Enable content moderation status control"),
        '#default_value' => $config->get('moderation_scheduler_enablemoderation'),
        '#prefix' => '<div class="check-wrap">',
        '#suffix' => '</div>',
      ];

      $form['configuration_moderation']['moderation_settings']['moderation_scheduler_moderation_state_settings'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Moderation State to schedule'),
        '#placeholder' => $this->t('Ready for review'),
        '#description' => $this->t('Insert elegible moderation state for publish, you can insert more than one separated by comma. If you have enabled workflows and Content Moderation get list of states here "admin/config/workflow/workflows"'),
        '#default_value' => $config->get('moderation_scheduler_moderation_state_settings'),
        '#size' => 55,
        '#states' => [
          'visible' => [
            ['input[name="moderation_scheduler_enablemoderation"]' => ['checked' => TRUE]],
          ],
          'required' => [
            ['input[name="moderation_scheduler_enablemoderation"]' => ['checked' => TRUE]],
          ],
        ],
      ];
    }

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Moderation Scheduler - Cron status information'),
      '#open' => TRUE,
    ];
    $form['status']['intro'] = [
      '#type' => 'item',
      '#markup' => $this->t('If you have administrative privileges you can run cron from this page and see the results.'),
    ];
    $next_execution = $this->state->get('moderation_scheduler.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : \Drupal::time()->getRequestTime();

    $args = [
      '%time' => date('c', $this->state->get('moderation_scheduler.next_execution')),
      '%seconds' => $next_execution - \Drupal::time()->getRequestTime(),
    ];
    $form['status']['last'] = [
      '#type' => 'item',
      '#markup' => $this->t('moderation_scheduler_cron() will next execute the first time cron runs after %time (%seconds seconds from now)', $args),
    ];

    if ($this->currentUser->hasPermission('administer moderation_scheduler module')) {
      $form['cron_run'] = [
        '#type' => 'details',
        '#title' => $this->t('Moderation Scheduler - Run cron manually'),
        '#open' => TRUE,
      ];
      $form['cron_run']['cron_reset'] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Run moderation_scheduler's cron regardless of whether interval has expired."),
        '#default_value' => FALSE,
      ];
      $form['cron_run']['cron_trigger']['actions'] = ['#type' => 'actions'];
      $form['cron_run']['cron_trigger']['actions']['sumbit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run cron now'),
        '#submit' => [[$this, 'cronRun']],
      ];
    }

    $form['cron_queue_setup'] = [
      '#type' => 'details',
      '#title' => $this->t('Moderation Scheduler - Cron queue setup (for hook_cron_queue_info(), etc.)'),
      '#open' => TRUE,
    ];

    $queue_1 = $this->queue->get('moderation_scheduler_queue_1');
    $queue_2 = $this->queue->get('moderation_scheduler_queue_2');

    $args = [
      '%queue_1' => $queue_1->numberOfItems(),
      '%queue_2' => $queue_2->numberOfItems(),
    ];
    $form['cron_queue_setup']['current_cron_queue_status'] = [
      '#type' => 'item',
      '#markup' => $this->t('There are currently %queue_1 items in queue 1 and %queue_2 items in queue 2', $args),
    ];
    $form['cron_queue_setup']['num_items'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of items to add to queue'),
      '#options' => array_combine([1, 5, 10, 100, 1000], [1, 5, 10, 100, 1000]),
      '#default_value' => 5,
    ];
    $form['cron_queue_setup']['queue'] = [
      '#type' => 'radios',
      '#title' => $this->t('Queue to add items to'),
      '#options' => [
        'moderation_scheduler_queue_1' => $this->t('Queue 1'),
        'moderation_scheduler_queue_2' => $this->t('Queue 2'),
      ],
      '#default_value' => 'moderation_scheduler_queue_1',
    ];
    $form['cron_queue_setup']['actions'] = ['#type' => 'actions'];
    $form['cron_queue_setup']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add jobs to queue'),
      '#submit' => [[$this, 'addItems']],
    ];

    $form['configuration']['cron_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration of moderation_scheduler_cron()'),
      '#open' => TRUE,
    ];
    $form['configuration']['cron_config']['moderation_scheduler_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron interval'),
      '#description' => $this->t('Time after which moderation_scheduler_cron will respond to a processing request.'),
      '#default_value' => $config->get('interval'),
      '#options' => [
        60 => $this->t('1 minute'),
        300 => $this->t('5 minutes'),
        3600 => $this->t('1 hour'),
        86400 => $this->t('1 day'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronRun(array &$form, FormStateInterface &$form_state) {

    $cron_reset = $form_state->getValue('cron_reset');
    if (!empty($cron_reset)) {
      $this->state->set('moderation_scheduler.next_execution', 0);
    }

    // State variable to signal that cron was run manually from this form.
    $this->state->set('moderation_scheduler_show_status_message', TRUE);
    if ($this->cron->run()) {
      $this->messenger()->addMessage($this->t('Moderation Scheduler - Cron ran successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('Moderation Scheduler - Cron run failed.'));
    }
  }

  /**
   * Add the items to the queue when signaled by the form.
   */
  public function addItems(array &$form, FormStateInterface &$form_state) {
    $values = $form_state->getValues();
    $queue_name = $form['cron_queue_setup']['queue'][$values['queue']]['#title'];
    $num_items = $form_state->getValue('num_items');
    // Queues are defined by a QueueWorker Plugin which are selected by their
    // id attritbute.
    // @see \Drupal\moderation_scheduler\Plugin\QueueWorker\ReportWorkerOne
    $queue = $this->queue->get($values['queue']);

    for ($i = 1; $i <= $num_items; $i++) {
      // Create a new item, a new data object, which is passed to the
      // QueueWorker's processItem() method.
      $item = new \stdClass();
      $item->created = \Drupal::time()->getRequestTime();
      $item->sequence = $i;
      $queue->createItem($item);
    }

    $args = [
      '%num' => $num_items,
      '%queue' => $queue_name,
    ];
    $this->messenger()->addMessage($this->t('Added %num items to %queue', $args));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the interval as stored in configuration. This will be read when
    // this modules hook_cron function fires and will be used to ensure that
    // action is taken only after the appropiate time has elapsed.
    $this->configFactory->getEditable('moderation_scheduler.settings')
      ->set('interval', $form_state->getValue('moderation_scheduler_interval'))
      ->set('moderation_scheduler_content_types', $form_state->getValue('moderation_scheduler_content_types'))
      ->set('moderation_scheduler_moderation_state_settings', $form_state->getValue('moderation_scheduler_moderation_state_settings'))
      ->set('moderation_scheduler_enablemoderation', $form_state->getValue('moderation_scheduler_enablemoderation'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['moderation_scheduler.settings'];
  }

}
