<?php

namespace Drupal\flippy;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Core\Utility\Token;
use Drupal\Component\Utility\Html;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the flippy pager service.
 */
class FlippyPager {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The flippy Settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $flippySettings;

  /**
   * Drupal token service.
   *
   * @var \Drupal\token\Token
   */
  protected $token;

  /**
   * Drupal Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event displatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory to get flippy settings.
   * @param \Drupal\Core\Utility\Token $token
   *   Drupal token service.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Drupal Language manager service.
   */
  public function __construct(EntityFieldManagerInterface $entityFieldManager, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, Connection $connection, ConfigFactoryInterface $config_factory, Token $token, LanguageManager $languageManager) {
    $this->entityFieldManager = $entityFieldManager;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->flippySettings = $config_factory->get('flippy.settings');
    $this->token = $token;
    $this->languageManager = $languageManager;
  }

  /**
   * Helper function: Query to get the list of flippy pagers.
   *
   * @param Drupal\node\Entity\Node $node
   *   Current node object.
   *
   * @return array
   *   A list of flippy pagers.
   */
  public function flippy_build_list(Node $node) {
    // Get all the properties from the current node.
    $master_list = &drupal_static(__FUNCTION__);
    if (!isset($master_list)) {
      $master_list = [];
    }
    if (!isset($master_list[$node->id()])) {
      // Check to see if we need custom sorting.
      if ($this->flippySettings
        ->get('flippy_custom_sorting_' . $node->getType())
      ) {
        // Get order.
        $order = $this->flippySettings
          ->get('flippy_order_' . $node->getType());
        // Get sort.
        $sort = $this->flippySettings
          ->get('flippy_sort_' . $node->getType());
      }
      else {
        $order = 'ASC';
        $sort = 'created';
      }

      // Validate that the sort criteria is OK to use.
      // Achieve the base field from a node type.
      $sort_options = [];
      // Get all the field from a node type.
      $content_type_fields = $this->entityFieldManager->getFieldDefinitions('node', $node->getType());
      foreach ($content_type_fields as $sort_field) {
        if (get_class($sort_field) == 'Drupal\Core\Field\BaseFieldDefinition') {
          // It is a base field.
          $schema_info = $sort_field->getSchema();
        }
        if (isset($schema_info['columns']['value']) && $schema_info['columns']['value']['type'] == 'int') {
          $sort_options[] = $sort_field->getName();
        }
      }

      $base_table_properties = $sort_options;
      $field_value = NULL;
      // If the sort criteria is not in the $sort_option array, we assume it's
      // a field.
      if (!in_array($sort, $base_table_properties)) {
        // Get the value of the current node's field (use the first one only)
        $current_field_items = $node->{$sort}->getValue();
        if (!isset($current_field_items[0]['value'])) {
          // Should never happen, but just in case, fall back to post date
          // ascending.
          $sort = 'created';

          $order = 'ASC';
        }
        else {
          // Otherwise save the field value for later.
          $field_value = $current_field_items[0]['value'];
        }
      }
      // Depending on order, decide what before and after means.
      $before = ($order == 'ASC') ? '<' : '>';
      $after = ($order == 'ASC') ? '>' : '<';
      // Also decide what up and down means.
      $up = ($order == 'ASC') ? 'ASC' : 'DESC';
      $down = ($order == 'ASC') ? 'DESC' : 'ASC';

      // Create a starting-point EntityQuery object.
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('type', $node->getType())
        ->condition('status', 1)
        ->condition('langcode', $this->languageManager->getCurrentLanguage()->getId())
        ->condition('nid', $node->id(), '!=')
        ->addTag('node_access');

      // Create the individual queries.
      $first = clone $query;
      $prev = clone $query;
      $next = clone $query;
      $last = clone $query;
      $random = clone $query;
      // We will construct the queries differently depending on whether the
      // sorting criteria is a field or a base table property.
      // If we found a field value earlier, we know we're dealing with a field.
      if (isset($field_value)) {
        // Set the conditions.
        // The first query.
        $field_value_equal_condition = $first->orConditionGroup()
          ->condition($sort . '.value', $field_value, '=')
          ->condition($sort . '.value', NULL, 'IS NULL');

        $field_default_condition = $first->andConditionGroup()
          ->condition('nid', $node->id(), $before)
          ->condition($field_value_equal_condition);

        $field_sorting_group = $first->orConditionGroup()
          ->condition($sort . '.value', $field_value, $before)
          ->condition($field_default_condition);

        $first->condition($field_sorting_group);

        // The last query.
        $field_value_equal_condition = $last->orConditionGroup()
          ->condition($sort . '.value', $field_value, '=')
          ->condition($sort . '.value', NULL, 'IS NULL');

        $field_default_condition = $last->andConditionGroup()
          ->condition('nid', $node->id(), $after)
          ->condition($field_value_equal_condition);

        $field_sorting_group = $last->orConditionGroup()
          ->condition($sort . '.value', $field_value, $after)
          ->condition($field_default_condition);

        $last->condition($field_sorting_group);

        // Previous query to find out the previous item based on the field,
        // using node id if the other criteria is the same.
        // The prev query.
        $field_value_equal_condition = $prev->orConditionGroup()
          ->condition($sort . '.value', $field_value, '=')
          ->condition($sort . '.value', NULL, 'IS NULL');

        $field_default_condition = $prev->andConditionGroup()
          ->condition('nid', $node->id(), $before)
          ->condition($field_value_equal_condition);

        $field_sorting_group = $prev->orConditionGroup()
          ->condition($sort . '.value', $field_value, $before)
          ->condition($field_default_condition);

        $prev->condition($field_sorting_group);

        // The next query.
        $field_value_equal_condition = $next->orConditionGroup()
          ->condition($sort . '.value', $field_value, '=')
          ->condition($sort . '.value', NULL, 'IS NULL');

        $field_default_condition = $next->andConditionGroup()
          ->condition('nid', $node->id(), $after)
          ->condition($field_value_equal_condition);

        $field_sorting_group = $next->orConditionGroup()
          ->condition($sort . '.value', $field_value, $after)
          ->condition($field_default_condition);

        $next->condition($field_sorting_group);

        // Set the ordering.
        $first->sort($sort, $up);
        $prev->sort($sort, $down);
        $next->sort($sort, $up);
        $last->sort($sort, $down);
      }
      else {
        // Otherwise we assume the variable is a column in the base table
        // (a property). Like above, set the conditions.
        $sort_value = $node->get($sort);
        $sort_value = $sort_value->getValue();
        // First and last query.
        $first->condition($sort, $sort_value[0]['value'], $before);
        $last->condition($sort, $sort_value[0]['value'], $after);

        // Previous query to find out the previous item based on the field,
        // using node id if the other criteria is the same.
        $field_default_condition = $prev->andConditionGroup()
          ->condition($sort, $sort_value[0]['value'])
          ->condition('nid', $node->id(), $before);

        $field_sorting_group = $prev->orConditionGroup()
          ->condition($sort, $sort_value[0]['value'], $before)
          ->condition($field_default_condition);

        $prev->condition($field_sorting_group);

        // Next query to find out the next item based on the field, using
        // node id if the other criteria is the same.
        $field_default_condition = $next->andConditionGroup()
          ->condition($sort, $sort_value[0]['value'])
          ->condition('nid', $node->id(), $after);

        $field_sorting_group = $next->orConditionGroup()
          ->condition($sort, $sort_value[0]['value'], $after)
          ->condition($field_default_condition);

        $next->condition($field_sorting_group);

        // Set the ordering.
        $first->sort($sort, $up);
        $prev->sort($sort, $down);
        $next->sort($sort, $up);
        $last->sort($sort, $down);
      }

      // Event dispatcher.
      $queries = [
        'first' => $first,
        'prev' => $prev,
        'next' => $next,
        'last' => $last,
      ];
      $event = new FlippyEvent($queries, $node);
      $this->eventDispatcher->dispatch('buildFlippyQuery', $event);
      $queries = $event->getQueries();

      // Execute the queries.
      $results = [];
      $results['first'] = $queries['first']
        ->range(0, 1)
        ->execute();
      $results['first'] = !empty($results['first']) ? array_values($results['first'])[0] : NULL;

      $results['prev'] = $queries['prev']
        ->range(0, 1)
        ->execute();
      $results['prev'] = !empty($results['prev']) ? array_values($results['prev'])[0] : NULL;

      $results['next'] = $queries['next']
        ->range(0, 1)
        ->execute();
      $results['next'] = !empty($results['next']) ? array_values($results['next'])[0] : NULL;

      $results['last'] = $queries['last']
        ->range(0, 1)
        ->execute();
      $results['last'] = !empty($results['last']) ? array_values($results['last'])[0] : NULL;

      $node_ids = [];
      foreach ($results as $key => $result) {
        // If the query returned no results, it means we're already
        // at the beginning/end of the pager, so ignore those.
        if (is_numeric($result)) {
          // Otherwise we save the node ID.
          $node_ids[$key] = (int) $result;
        }
        elseif (is_array($result) && count($result) > 0) {
          // Otherwise we save the node ID.
          $node_ids[$key] = $results[$key];
        }

      }

      // Make our final array of node IDs and titles.
      $list = [];
      // But only if we actually found some matches.
      if (count($node_ids) > 0) {
        // We also need titles to go with our node ids.
        $title_query = $this->connection->select('node_field_data', 'nfd')
          ->fields('nfd', ['title', 'nid'])
          ->condition('nfd.nid', $node_ids, 'IN')
          ->execute()
          ->fetchAllAssoc('nid');

        foreach ($node_ids as $key => $nid) {
          $list[$key] = [
            'nid' => $nid,
            'title' => isset($title_query[$nid]) ? $title_query[$nid]->title : '',
          ];
        }
      }

      // Create random list.
      // TODO: orderRandom is not available in entityQuery yet.
      if ($this->flippySettings
        ->get('flippy_random_' . $node->getType())
      ) {
        $random_nids = $random->execute();
        $random_nid = array_rand($random_nids, 1);

        // Find out the node title.
        $title = $this->connection->select('node_field_data', 'nfd')
          ->fields('nfd', ['title'])
          ->condition('nfd.nid', $random_nid, '=')
          ->execute()
          ->fetchField();
        $list['random'] = [
          'nid' => $random_nid,
          'title' => $title,
        ];
      }

      $master_list[$node->id()] = $list;
    }
    return $master_list[$node->id()];
  }

  /**
   * Determine if the Flippy pager should be shown for the give node.
   *
   * @param Drupal\node\Entity\Node $node
   *   Node to check for pager.
   *
   * @return bool
   *   Boolean: TRUE if pager should be shown, FALSE if not.
   */
  public function flippy_use_pager(Node $node) {
    if (!is_object($node)) {
      return FALSE;
    }
    return node_is_page($node) && $this->flippySettings
      ->get('flippy_' . $node->getType());
  }

  /**
   * Helper function to generate link.
   *
   * @param int $nodeId
   *   Target node ID.
   * @param string $label
   *   Target node label.
   *
   * @return array|mixed
   *   Link render array.
   */
  public function flippy_generate_link($nodeId, $label) {
    $token_service = $this->token;
    $language = $this->languageManager->getCurrentLanguage()->getId();

    $url = Url::fromRoute('entity.node.canonical');
    $url->setRouteParameter('node', $nodeId);
    $node_storage = $this->entityTypeManager->getStorage('node');
    $flippyLink = Link::fromTextAndUrl(HTML::decodeEntities($token_service->replace($label, ['node' => $node_storage->load($nodeId)], ['langcode' => $language])), $url);

    return $flippyLink->toRenderable();
  }

}
