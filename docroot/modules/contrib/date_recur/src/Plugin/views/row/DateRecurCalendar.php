<?php

namespace Drupal\date_recur\Plugin\views\row;

use Drupal\calendar\CalendarEvent;
use Drupal\calendar\CalendarHelper;
use Drupal\calendar\CalendarViewsTrait;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which creates a view on the resulting object and formats it as a
 * Calendar entity.
 *
 * @ViewsRow(
 *   id = "date_recur_calendar_row",
 *   title = @Translation("Calendar entities (with Date Recur)"),
 *   help = @Translation("Display the content as calendar entities."),
 *   theme = "views_view_row_calendar",
 *   register_theme = FALSE,
 * )
 */
class DateRecurCalendar extends RowPluginBase {

  use CalendarViewsTrait;

  /**
   * @var \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter service.
   */
  protected $dateFormatter;

  /**
   * @var $entityType
   *   The entity type being handled in the preRender() function.
   */
  protected $entityType;

  /**
   * @var $entities
   *   The entities loaded in the preRender() function.
   */
  protected $entities = [];

  /**
   * @var $dateFields
   *   todo document.
   */
  protected $dateFields = [];

  /**
   * @var \Drupal\views\Plugin\views\argument\Formula
   */
  protected $dateArgument;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // TODO needed?
//     $this->base_table = $view->base_table;
//     $this->baseField = $view->base_field;
  }

  /**
   * Constructs a Calendar row plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatter $date_formatter, EntityFieldManagerInterface $field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dateFormatter = $date_formatter;
    $this->fieldManager = $field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['date_fields'] = ['default' => []];
    $options['colors'] = [
      'contains' => [
        'legend' => ['default' => ''],
        'calendar_colors_type' => ['default' => []],
        'taxonomy_field' => ['default' => ''],
        'calendar_colors_vocabulary' => ['default' => []],
        'calendar_colors_taxonomy' => ['default' => []],
        'calendar_colors_group' => ['default' => []],
      ]
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['markup'] = [
      '#markup' => $this->t("The calendar row plugin will format view results as calendar items. Make sure this display has a 'Calendar' format and uses a 'Date' contextual filter, or this plugin will not work correctly."),
    ];


    $form['colors'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Legend Colors'),
      '#description' =>  $this->t('Set a hex color value (like #ffffff) to use in the calendar legend for each content type. Items with empty values will have no stripe in the calendar and will not be added to the legend.'),
    ];


    $options = [];
    // @todo Allow strip options for any bundes of any entity type
    if ($this->view->getBaseTables()['node_field_data']) {
      $options['type'] = $this->t('Based on Content Type');
    }
    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      $options['taxonomy'] = $this->t('Based on Taxonomy');
    }

    // If no option is available, stop here.
    if (empty($options)) {
      return;
    }

    $form['colors']['legend'] = [
      '#title' => $this->t('Stripes'),
      '#description' => $this->t('Add stripes to calendar items.'),
      '#type' => 'select',
      '#options' => $options,
      '#empty_value' => (string) $this->t('None'),
      '#default_value' => $this->options['colors']['legend'],
    ];

    if ($this->view->getBaseTables()['node_field_data']) {
      $colors = $this->options['colors']['calendar_colors_type'];
      $type_names = node_type_get_names();
      foreach ($type_names as $key => $name) {
        $form['colors']['calendar_colors_type'][$key] = [
          '#title' => $name,
          '#default_value' => isset($colors[$key]) ? $colors[$key] : CALENDAR_EMPTY_STRIPE,
          '#dependency' => ['edit-row-options-colors-legend' => ['type']],
          '#type' => 'textfield',
          '#size' => 7,
          '#maxlength' => 7,
          '#element_validate' => [[$this, 'validateHexColor']],
          '#prefix' => '<div class="calendar-colorpicker-wrapper">',
          '#suffix' => '<div class="calendar-colorpicker"></div></div>',
          '#attributes' => ['class' => ['edit-calendar-colorpicker']],
          '#attached' => [
            // Add Farbtastic color picker and the js to trigger it.
            'library' => [
              'calendar/calendar.colorpicker',
            ],
          ],
        ] + $this->visibleOnLegendState('type');
      }
    }



    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      // Get the display's field names of taxonomy fields.
      $vocabulary_field_options = [];
      $fields = $this->displayHandler->getOption('fields');
      foreach ($fields as $name => $field_info) {
        // Select the proper field type.
        if ($this->isTermReferenceField($field_info, $this->fieldManager)) {
            $vocabulary_field_options[$name] = $field_info['label'] ?: $name;
        }
      }
      if (empty($vocabulary_field_options)) {
        return;
      }
      $form['colors']['taxonomy_field'] = [
        '#title' => t('Term field'),
        '#type' => !empty($vocabulary_field_options) ? 'select' : 'hidden',
        '#default_value' => $this->options['colors']['taxonomy_field'],
        '#empty_value' => (string) $this->t('None'),
        '#description' => $this->t("Select the taxonomy term field to use when setting stripe colors. This works best for vocabularies with only a limited number of possible terms."),
        '#options' => $vocabulary_field_options,
        // @todo Is this in the form api?
        '#dependency' => ['edit-row-options-colors-legend' => ['taxonomy']],
      ] + $this->visibleOnLegendState('taxonomy');

      if (empty($vocabulary_field_options)) {
        $form['colors']['taxonomy_field']['#options'] = ['' => ''];
        $form['colors']['taxonomy_field']['#suffix'] = $this->t('You must add a term field to this view to use taxonomy stripe values. This works best for vocabularies with only a limited number of possible terms.');
      }

      // Get the Vocabulary names.
      $vocab_vids = [];

      foreach ($vocabulary_field_options as $field_name => $label) {
        // @todo Provide storage manager via Dependency Injection
        $field_config = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['field_name' => $field_name]);

        // @TODO refactor
        reset($field_config);
        $key = key($field_config);

        $data = \Drupal::config('field.field.' . $field_config[$key]->getOriginalId())->getRawData();

        if ($target_bundles = $data['settings']['handler_settings']['target_bundles']) {
          // Fields must target bundles set.
          reset($target_bundles);
          $vocab_vids[$field_name] = key($target_bundles);
        }
      }

      if (empty($vocab_vids)) {
        return;
      }

      $this->options['colors']['calendar_colors_vocabulary'] = $vocab_vids;

      $form['colors']['calendar_colors_vocabulary'] = [
          '#title' => t('Vocabulary Legend Types'),
          '#type' => 'value',
          '#value' => $vocab_vids,
        ] + $this->visibleOnLegendState('taxonomy');

      // Get the Vocabulary term id's and map to colors.
      // @todo Add labels for each Vocabulary.
      $term_colors = $this->options['colors']['calendar_colors_taxonomy'];
      foreach ($vocab_vids as $field_name => $vid) {
        $vocab = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadTree($vid);
        foreach ($vocab as $key => $term) {
          $form['colors']['calendar_colors_taxonomy'][$term->tid] = [
              '#title' => $this->t($term->name),
              '#default_value' => isset($term_colors[$term->tid]) ? $term_colors[$term->tid] : CALENDAR_EMPTY_STRIPE,
              '#access' => !empty($vocabulary_field_options),
              '#dependency_count' => 2,
              '#dependency' => [
                'edit-row-options-colors-legend' => ['taxonomy'],
                'edit-row-options-colors-taxonomy-field' => [$field_name],
              ],
              '#type' => 'textfield',
              '#size' => 7,
              '#maxlength' => 7,
              '#element_validate' => [[$this, 'validateHexColor']],
              '#prefix' => '<div class="calendar-colorpicker-wrapper">',
              '#suffix' => '<div class="calendar-colorpicker"></div></div>',
              '#attributes' => ['class' => ['edit-calendar-colorpicker']],
              '#attached' => [
                // Add Farbtastic color picker and the js to trigger it.
                'library' => [
                  'calendar/calendar.colorpicker',
                ],
              ],
            ]  + $this->visibleOnLegendState('taxonomy');
        }
      }

    }



  }

  /**
   *  Check to make sure the user has entered a valid 6 digit hex color.
   */
  public function validateHexColor($element, FormStateInterface $form_state) {
    if (!$element['#required'] && empty($element['#value'])) {
      return;
    }
    if (!preg_match('/^#(?:(?:[a-f\d]{3}){1,2})$/i', $element['#value'])) {
      $form_state->setError($element, $this->t("'@color' is not a valid hex color", ['@color' => $element['#value']]));
    }
    else {
      $form_state->setValueForElement($element, $element['#value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    // Preload each entity used in this view from the cache. This provides all
    // the entity values relatively cheaply, and we don't need to do it
    // repeatedly for the same entity if there are multiple results for one
    // entity.
    $ids = [];

    /** @var $row \Drupal\views\ResultRow */
    foreach ($result as $row) {
      // Use the entity id as the key so we don't create more than one value per
      // entity.
      $entity = $row->_entity;

      // Node revisions need special loading.
      if (isset($this->view->getBaseTables()['node_revision'])) {
        $this->entities[$entity->id()] = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($entity->id());
      }
      else {
        $ids[$entity->id()] = $entity->id();
      }
    }

    $base_tables = $this->view->getBaseTables();
    $base_table = key($base_tables);
    $table_data = Views::viewsData()->get($base_table);
    $this->entityType = $table_data['table']['entity type'];

    if (!empty($ids)) {
      $this->entities = \Drupal::entityTypeManager()->getStorage($this->entityType)->loadMultiple($ids);
    }

    // Identify the date argument and fields that apply to this view. Preload
    // the Date Views field info for each field, keyed by the field name, so we
    // know how to retrieve field values from the cached node.
    // @todo don't hardcode $date_fields, use viewsData() or viewsDataHelper()

//    $data = date_views_fields($this->view->base_table);
//    $data = $data['name'];

    $data = CalendarHelper::dateViewFields($this->entityType);

    $data = $data['name'];
    $date_fields = [];
    /** @var $handler \Drupal\views\Plugin\views\argument\Formula */
    foreach ($this->view->getDisplay()->getHandlers('argument') as $handler) {
      if ($handler instanceof Date) {
        // Strip "_calendar" from the field name.
        $fieldName = $handler->realField;
        $alias = $handler->table . '.' . $fieldName;
        $info = $data[$alias];
        $field_name  = str_replace(array('_value2', '_value'), '', $info['real_field_name']);
        $date_fields[$field_name] = $info;
        $this->dateArgument = $handler;

        $this->dateFields = $date_fields;
      }
    }
//
//    // Get the language for this view.
//    $this->language = $this->display->handler->get_option('field_language');
//    $substitutions = views_views_query_substitutions($this->view);
//    if (array_key_exists($this->language, $substitutions)) {
//      $this->language = $substitutions[$this->language];
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $rows = [];
    
    /** @var \Drupal\calendar\CalendarDateInfo $dateInfo */
    $dateInfo = $this->dateArgument->view->dateInfo;
    $dateMin = $dateInfo->getMinDate();
    $dateMax = $dateInfo->getMaxDate();
    $dateTimezone = new \DateTimeZone(timezone_name_get($dateInfo->getTimezone()));
    $id = $row->_entity->id();

    if (!is_numeric($id)) {
      return [];
    }

    // unrelated to end date: this addresses issue where an entity on a calendar is duplicated
    // if it has multiple entity references; ensure that the calendar entity is only displayed once
    static $used = '';
    if ($id != $used) {
      $used = $id;
    }
    else {
      return [];
    }

    // There could be more than one date field in a view so iterate through all
    // of them to find the right values for this view result.
    foreach ($this->dateFields as $field_name => $info) {

      // Clone this entity so we can change it's values without altering other
      // occurrences of this entity on the same page, for example in an
      // "Upcoming" block.
      /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
      $entity = clone($this->entities[$id]);

      if (empty($entity)) {
        return [];
      }

      // Retrieve the field value(s) that matched our query from the cached node.
      // Find the date and set it to the right timezone.
      $entity->date_id = [];
      $item_start_date = NULL;
      $item_end_date   = NULL;
      $granularity = 'month';
      $increment = 1;
      $delta = 0;

      if ($entity->hasField($field_name) && $entity->get($field_name) && isset($entity->get($field_name)[$delta]) && $entity->get($field_name)[$delta] instanceof DateRecurItem) {
        $item = $entity->get($field_name)[$delta];

        // Get DateRecur Occurrence Handler.
        $occurrenceHandler = $item->getHelper();

        if ($occurrenceHandler->getRules()) {
          $occurrences = $occurrenceHandler->generateOccurrences($dateMin, $dateMax, 1);

          if(!empty($occurrences)) {
            foreach ($occurrences as $_delta => $occurrence) {
              $start = $occurrence->getStart();
              $end = $occurrence->getEnd();

              $item_start_date = new \DateTime();
              $item_start_date->setTimestamp($start->getTimestamp());
              $item_start_date->setTimezone($dateTimezone);

              $item_end_date = new \DateTime();
              $item_end_date->setTimestamp($end->getTimestamp());
              $item_end_date->setTimezone($dateTimezone);

              // Finally, create event objects, and merge them into the row.
              $items = $this->getEventItems($entity, $item_start_date, $item_end_date, $dateTimezone, $granularity);

              if (!empty($items)) {
                $rows = array_merge($rows, $items);
              }
            }

            // At this point, all DateRecur occurrences are merged into $rows
            // so we can continue adding date items with the next field.
            continue;
          }
        }

        // "Date" and "DateRange" support
        $item = $entity->get($field_name)->getValue();
        $timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
        $item_start_date = new \DateTime($item[0]['value'], $timezone);
        $item_start_date->setTimezone($dateTimezone);

        if (!empty($item[0]['end_value'])) {
          $item_end_date = new \DateTime($item[0]['end_value'], $timezone);
          $item_end_date->setTimezone($dateTimezone);
        }
        else {
          $item_end_date = $item_start_date;
        }
      }
      elseif ($entity->hasField($field_name) && $entity->get($field_name)) {
        $item = $entity->get($field_name)->getValue();
        $timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
        $item_start_date = new \DateTime($item[0]['value'], $timezone);
        $item_start_date->setTimezone($dateTimezone);

        if (!empty($item[0]['end_value'])) {
          $item_end_date = new \DateTime($item[0]['end_value'], $timezone);
          $item_end_date->setTimezone($dateTimezone);
        }
        else {
          $item_end_date = $item_start_date;
        }
        $entity->date_id = array('calendar.' . $id . '.' . $field_name . '.0');
      }

      // If we don't have a date value, go no further.
      if (empty($item_start_date)) {
        continue;
      }

      $items = $this->getEventItems($entity, $item_start_date, $item_end_date, $dateTimezone, $granularity);

      if (!empty($items)) {
        $rows = array_merge($rows, $items);
      }
    }

    return $rows;
  }

  public function getEventItems($entity, $item_start_date, $item_end_date, $dateTimezone, $granularity) {
    $event = new CalendarEvent($entity);
    $event->setStartDate($item_start_date);
    $event->setEndDate($item_end_date);
    $event->setTimezone($dateTimezone);
    $event->setGranularity($granularity);

    // All calendar row plugins should provide a date_id that the theme can use.
    // @todo implement
    $event->date_id = $entity->date_id[0];

    /** @var \Drupal\calendar\CalendarEvent[] $events */
    $events = $this->explode_values($event);

    foreach ($events as $event) {
      switch ($this->options['colors']['legend']) {
        case 'type':
          if ($event->getEntityTypeId() == 'node') {
            $this->nodeTypeStripe($event);
          }

          break;
        case 'taxonomy':
          $this->calendarTaxonomyStripe($event);
          break;
      }
      $rows[] = $event;
    }

    return $rows;
  }

  /**
   * @todo rename and document
   *
   * @param \Drupal\calendar\CalendarEvent $event
   * @return array
   */
  function explode_values($event) {
    $rows = [];

    $dateInfo = $this->dateArgument->view->dateInfo;
    $item_start_date = $event->getStartDate()->getTimestamp();
    $item_end_date = $event->getEndDate()->getTimestamp();
//    $to_zone = $event->to_zone;
//    $db_tz = $event->db_tz;
//    $granularity = $event->granularity;
//    $increment = $event->increment;

    // Now that we have an 'entity' for each view result, we need to remove
    // anything outside the view date range, and possibly create additional
    // nodes so that we have a 'node' for each day that this item occupies in
    // this view.
    // @TODO make this work with the CalendarDateInfo object

    $start = $this->dateFormatter->format($event->getStartDate()->getTimestamp(), 'custom', 'Y-m-d');
    $now = $start;
    $to = $this->dateFormatter->format($event->getEndDate()->getTimestamp(), 'custom', 'Y-m-d');
    $next = new \DateTime();
    $next->setTimestamp($event->getStartDate()->getTimestamp());

    if (timezone_name_get($this->dateArgument->view->dateInfo->getTimezone()) != $event->getTimezone()->getName()) {
      // Make $start and $end (derived from $node) use the timezone $to_zone,
      // just as the original dates do.
      $next->setTimezone($event->getTimezone());
    }

    if (empty($to) || $now > $to) {
      $to = $now;
    }

    // $now and $next are midnight (in display timezone) on the first day where node will occur.
    // $to is midnight on the last day where node will occur.
    // All three were limited by the min-max date range of the view.
    $position = 0;
    while (!empty($now) && $now <= $to) {
      /** @var $entity \Drupal\calendar\CalendarEvent */
      $entity = clone($event);

      // Get start and end of current day.
      $start = $this->dateFormatter->format($next->getTimestamp(), 'custom', 'Y-m-d H:i:s');
      $next->setTimestamp(strtotime(' +1 day -1 second', $next->getTimestamp()));
      $end = $this->dateFormatter->format($next->getTimestamp(), 'custom', 'Y-m-d H:i:s');

      // Get start and end of item, formatted the same way.
      $item_start = $this->dateFormatter->format($item_start_date, 'custom', 'Y-m-d H:i:s');
      $item_end = $this->dateFormatter->format($item_end_date, 'custom', 'Y-m-d H:i:s');

      // Get intersection of current day and the node value's duration (as
      // strings in $to_zone timezone).
      $start_string = $item_start < $start ? $start : $item_start;
      $end_string = !empty($item_end) ? ($item_end > $end ? $end : $item_end) : NULL;
      $entity->calendar_start_date = (new \DateTime($start_string));
      $entity->calendar_end_date = (new \DateTime($end_string));

      // @TODO don't hardcode granularity and increment
      if ($now === $start || $now === $to) {
        $granularity = 'second';
        $increment = 1;
        $entity->setAllDay(CalendarHelper::dateIsAllDay($entity->getStartDate()->format('Y-m-d H:i:s'), $entity->getEndDate()->format('Y-m-d H:i:s'), $granularity, $increment));
      }
      else {
        $entity->setAllDay(TRUE);
      }

      $calendar_start = $this->dateFormatter->format($entity->calendar_start_date->getTimestamp(), 'custom', 'Y-m-d H:i:s');
      $calendar_end = $this->dateFormatter->format($entity->calendar_end_date->getTimestamp(), 'custom', 'Y-m-d H:i:s');

//      unset($entity->calendar_fields);
      if (isset($entity) && (empty($calendar_start))) {
        // if no date for the node and no date in the item
        // there is no way to display it on the calendar
        unset($entity);
      }
      else {
        $entity->date_id .= '.' . $position;
        $rows[] = $entity;
        unset($entity);
      }

      $next->setTimestamp(strtotime('+1 second', $next->getTimestamp()));
      $now = $this->dateFormatter->format($next->getTimestamp(), 'custom', 'Y-m-d');
      $position++;
    }
    return $rows;
  }

  /**
   * Create a stripe base on node type.
   *
   * @param \Drupal\calendar\CalendarEvent $event
   *   The event result object.
   */
  function nodeTypeStripe(&$event) {
    $colors = isset($this->options['colors']['calendar_colors_type']) ? $this->options['colors']['calendar_colors_type'] : [];
    if (empty($colors)) {
      return;
    }

    $type_names = node_type_get_names();
    $bundle = $event->getBundle();
    $label = '';
    $stripeHex = '';
    if (array_key_exists($bundle, $type_names) || $colors[$bundle] == CALENDAR_EMPTY_STRIPE) {
      $label = $type_names[$bundle];
    }
    if (array_key_exists($bundle, $colors)) {
      $stripeHex = $colors[$bundle];
    }

    $event->addStripeLabel($label);
    $event->addStripeHex($stripeHex);
  }

  /**
   * Create a stripe based on a taxonomy term.
   *
   * @param CalendarEvent $event
   */
  function calendarTaxonomyStripe(&$event) {
    $colors = isset($this->options['colors']['calendar_colors_taxonomy']) ? $this->options['colors']['calendar_colors_taxonomy'] : [];
    if (empty($colors)) {
      return;
    }

    $entity = $event->getEntity();
    $term_field_name = $this->options['colors']['taxonomy_field'];
    if ($entity->hasField($term_field_name) && $terms_for_entity = $entity->get($term_field_name)) {
      /** @var EntityReferenceFieldItemListInterface $item */
      foreach ($terms_for_entity as $item) {
        $tid = $item->getValue()['target_id'];
        $term = Term::load($tid);

        if (!array_key_exists($tid, $colors) || $colors[$tid] == CALENDAR_EMPTY_STRIPE) {
          continue;
        }
        $event->addStripeLabel($term->name->value);
        $event->addStripeHex($colors[$tid]);
      }
    }

    return;
  }

  /**
   * Get form options for hiding elements based on legend type.
   * @param $mode
   *
   * @return array
   */
  protected function visibleOnLegendState($mode) {
    return [
      '#states' => [
        'visible' => [
          ':input[name="row_options[colors][legend]"]' => ['value' => $mode],
        ],
      ],
    ];
  }

}

