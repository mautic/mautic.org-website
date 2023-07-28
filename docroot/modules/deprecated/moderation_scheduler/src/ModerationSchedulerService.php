<?php

namespace Drupal\moderation_scheduler;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a moderation scheduler interface.
 */
class ModerationSchedulerService {

  /**
   * Module handler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Config Factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Entity Manager service object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a SchedulerManager object.
   */
  public function __construct(ModuleHandler $moduleHandler, ConfigFactory $configFactory, EntityTypeManager $entityManager, Connection $database, LanguageManagerInterface $languageManager, EventDispatcherInterface $eventDispatcher) {
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
    $this->entityManager = $entityManager;
    $this->database = $database;
    $this->languageManager = $languageManager;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Main function to publish scheduled node.
   *
   * @return array
   *   Array of published nodes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function publishScheduled() {
    // Event dispatcher.
    $dispatcher = $this->eventDispatcher;

    $node_storage = $this->entityManager->getStorage('node');
    $state = 'published';
    $default_language = $this->languageManager->getDefaultLanguage()->getId();

    // Get a date string suitable for use with entity query.
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $date = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $languages = array_keys($this->languageManager->getLanguages());

    $query = $this->entityManager->getStorage('node')->getQuery()
      ->exists('field_scheduled_time')
      ->condition('field_scheduled_time.value', $date, '<=')
      ->latestRevision()
      ->sort('field_scheduled_time')
      ->sort('nid');

    // Disable access checks for this query.
    // @see https://www.drupal.org/node/2700209
    $query->accessCheck(FALSE);
    $nids = $query->execute();

    $nodes = $this->loadNodes($nids);

    if ($nodes) {

      foreach ($nodes as $node) {

        $languages = $node->getTranslationLanguages();

        $translations = [];

        // If node has translation get latest revision.
        if ($languages) {

          $d_node_vid = $node_storage->getLatestTranslationAffectedRevisionId($node->id(), $default_language);

          $d_node = $node_storage->loadRevision($d_node_vid)->getTranslation($default_language);

          $old_state = $this->returnState($d_node);

          $this->resetFields($d_node, $default_language);

          foreach ($languages as $language) {
            $rev_lang = $language->getId();
            $lang_name = $language->getName();
            if ($rev_lang != $default_language) {

              // Remove the old translation attached.
              $old_translation = $node->getTranslation($rev_lang);
              if ($d_node->hasTranslation($rev_lang)) {
                try {
                  $d_node->removeTranslation($rev_lang);
                }
                catch (\InvalidArgumentException $exception) {
                  $old_translation = $node->getTranslation($rev_lang);
                }
              }

              // Latest translation revision by scheduled field.
              // Ensure old translation will not be lost.
              if ($node->id()) {
                if ($this->fieldScheduledTimeRevision($node->id(), $rev_lang)) {
                  $t_node = $this->fieldScheduledTimeRevision($node->id(), $rev_lang);
                }
                else {
                  $t_node = $old_translation;
                }
              }
              if ($t_node) {

                $t_result = [
                  "lang" => $rev_lang . " - " . $lang_name . " Translation (" . $t_node->getTitle() . ")",
                ];
                array_push($translations, $t_result);

                $this->resetFields($t_node, $rev_lang);

                try {

                  // If no translation, throw exception.
                  $d_node->addTranslation($rev_lang, $t_node->toArray())->save();
                  $t_node->save();
                }
                catch (\InvalidArgumentException $exception) {
                  try {

                    // Remove default translation from node
                    // If no translation, throw exception.
                    try {
                      $t_node->removeTranslation($default_language);
                      $t_node->addTranslation($default_language, $d_node->toArray())->save();
                    }
                    catch (\InvalidArgumentException $exception) {
                      continue;
                    }
                  }
                  catch (\InvalidArgumentException $exception) {
                    $t_title = $t_node->getTitle();
                    if ($t_title) {
                      $t_node->save();
                    }
                  }
                }
              }
            }
          }
        }
        else {
          $d_node_vid = $node_storage->getLatestRevisionId($node->id());

          $d_node = $node_storage->loadRevision($d_node_vid);

          $old_state = $this->returnState($d_node);

          $this->resetFields($d_node, $default_language);

        }
        $d_title = $d_node->getTitle();

        // Save changes to default node revision.
        $d_node->save();

        // PUBLISH event.
        $event = new ModerationSchedulerEvent($d_node);
        $dispatcher->dispatch(ModerationSchedulerEvents::PUBLISH, $event);

        // Result log message.
        $result[] = [
          "node type" => $node->type->entity->label(),
          "node id" => $node->id(),
          "old state" => $old_state,
          "new state" => $state,
          "lang" => $default_language . " - Original Translation (" . $d_title . ")",
          "translations" => count($translations) >= 1 ? $translations : "no translation",
        ];
      }
    }
    else {

      // If no node with field_scheduled_time in default language
      // get iterating on languages and field_scheduled_time.
      $languages = $this->languageManager->getLanguages();
      if (isset($languages)) {
        foreach ($languages as $language) {
          $rev_lang = $language->getId();
          $lang_name = $language->getName();
          $translations = [];
          $t_nodes = $this->fieldScheduledTimeRevision(NULL, $rev_lang) ? $this->fieldScheduledTimeRevision(NULL, $rev_lang) : [];

          if (count($t_nodes) >= 1 && $rev_lang != $default_language) {
            foreach ($t_nodes as $t_node) {
              if ($t_node) {

                $old_state = $this->returnState($t_node);

                $d_node_vid = $node_storage->getLatestTranslationAffectedRevisionId($t_node->id(), $default_language);

                $d_node = $node_storage->loadRevision($d_node_vid)->getTranslation($default_language);

                $this->resetFields($t_node, $rev_lang);

                try {

                  // If no translation, throw exception.
                  $d_node->addTranslation($rev_lang, $t_node->toArray())->save();
                  $t_node->save();
                  $t_result = [
                    "lang" => $rev_lang . " - " . $lang_name . " Translation (" . $t_node->getTitle() . ")",
                  ];
                  array_push($translations, $t_result);
                }
                catch (\InvalidArgumentException $exception) {
                  try {

                    // Remove default translation from node
                    // If no translation, throw exception.
                    try {
                      $t_node->removeTranslation($default_language);
                      $t_node->addTranslation($default_language, $d_node->toArray())->save();
                      $t_result = [
                        "lang" => $rev_lang . " - " . $lang_name . " Translation (" . $d_node->getTitle() . ")",
                      ];
                      array_push($translations, $t_result);
                    }
                    catch (\InvalidArgumentException $exception) {
                      continue;
                    }
                  }
                  catch (\InvalidArgumentException $exception) {
                    $t_title = $t_node->getTitle();
                    $t_node->save();
                  }
                }

                // Trigger the PUBLISH event.
                $event = new ModerationSchedulerEvent($t_node);
                $dispatcher->dispatch(ModerationSchedulerEvents::PUBLISH, $event);
                $t_node->save();

                // Result log message.
                $result[] = [
                  "node type" => $node->type->entity->label(),
                  "node id" => $t_node->id(),
                  "old state" => $old_state,
                  "new state" => $state,
                  "lang" => $default_language . " - Original Translation (" . $d_title . ")",
                  "translations" => count($translations) >= 1 ? $translations : "no translation",
                ];
              }
            }
          }
        }
      }
    }

    // Return result of scheduling.
    return isset($result) ? $result : ["no scheduled content to publish"];
  }

  /**
   * Helper method to return node state.
   *
   * @param object $node
   *   Object of node.
   * @param string $lang
   *   String langcode of node.
   *
   * @return string
   *   String of node status.
   */
  public function resetFields($node, $lang) {

    if ($node->hasField('langcode')) {
      $node->set('langcode', $lang);
    }
    if ($node->hasField('field_scheduled_time')) {
      $node->set('field_scheduled_time', NULL);
    }
    if ($node->hasField('moderation_state')) {
      $node->set('moderation_state', 'published');
    }
    $node->setPublished(TRUE);
    $node->status = "1";

    return $node;
  }

  /**
   * Helper method to return node state.
   *
   * @param object $node
   *   Object of node.
   *
   * @return string
   *   String of node status.
   */
  public function returnState($node) {
    if ($node->hasField('moderation_state')) {
      $state = $node->get('moderation_state')->value;
      $node->set('moderation_state', 'published');
    }
    else {
      $state = $node->status = 1 ? "published" : "unpublished";
    }
    return $state;
  }

  /**
   * Helper method to load latest revision of each node.
   *
   * @param array $nids
   *   Array of node ids.
   *
   * @return array
   *   Array of loaded nodes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadNodes(array $nids) {
    $node_storage = $this->entityManager->getStorage('node');
    $nodes = [];

    // Load the latest revision for each node.
    foreach ($nids as $nid) {
      $vid = $node_storage->getLatestRevisionId($nid);
      $nodes[] = $node_storage->loadRevision($vid);
    }
    return $nodes;
  }

  /**
   * Helper method to load latest revision of each translation.
   *
   * @param int $nid
   *   Integer node id.
   * @param string $langcode
   *   String langcode of node.
   * @param bool $filterDate
   *   Boolean filterDate of query.
   *
   * @return object
   *   Object of loaded node revision or [Object node].
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function fieldScheduledTimeRevision($nid, $langcode, $filterDate = TRUE) {
    $node_storage = $this->entityManager->getStorage('node');

    // Get a date string suitable for use with entity query.
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $date = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $connection = $this->database;
    if ($nid) {

      // Get revision of latest tranlsation.
      $query = $connection->query("SELECT revision_id, langcode, field_scheduled_time_value, entity_id FROM {node_revision__field_scheduled_time} WHERE langcode = :langcode AND field_scheduled_time_value <= :date AND entity_id =:nid", [
        ':langcode' => $langcode,
        ':date' => $date,
        ':nid' => $nid,
      ]);

      $revisions = $query->fetchAll();

      if ($revisions) {
        $revision = end($revisions);

        // Get revision id from the field.
        $rev_id = $revision->revision_id;
        $schedule = $revision->field_scheduled_time_value;
        $lang = $revision->langcode;
        if ($lang == $langcode && $schedule <= $date) {

          // Load revision by lang and translation.
          $rev = $node_storage->loadRevision($rev_id)->getTranslation($langcode);
          if ($rev) {
            $rev->set('langcode', $langcode);
            return $rev;
          }
        }
      }
    }
    elseif ($langcode) {

      // If no nid get latest tranlsations.
      $query = $connection->query("SELECT revision_id, langcode, field_scheduled_time_value, entity_id FROM {node_revision__field_scheduled_time} WHERE langcode = :langcode AND field_scheduled_time_value <= :date", [
        ':langcode' => $langcode,
        ':date' => $date,
      ]);

      $revisions = $query->fetchAll();
      if ($revisions) {
        $nodes = [];
        foreach ($this->arrayMultidimUnique($revisions, 'entity_id') as $revision) {
          // Get revision id from the field.
          $revision_id = $revision->revision_id;
          $schedule = $revision->field_scheduled_time_value;
          $lang = $revision->langcode;
          if ($lang == $langcode && $schedule <= $date) {

            // Load revision with lang and translation.
            $rev = $node_storage->loadRevision($revision_id)->getTranslation($langcode);
            if ($rev) {
              $rev->set('langcode', $langcode);
              array_push($nodes, $rev);
            }
          }
        }
        return count($nodes) >= 1 ? $nodes : NULL;
      }
    }
    elseif (!$langcode && !$nid) {
      // If no nid get latest tranlsations.
      // https://www.drupal.org/project/moderation_scheduler/issues/3081710
      // https://www.drupal.org/project/moderation_scheduler/issues/3080452
      // remove filter to fix view.
      if ($filterDate == TRUE) {
        $query = $connection->query("SELECT revision_id, langcode, field_scheduled_time_value, entity_id FROM {node_revision__field_scheduled_time} WHERE field_scheduled_time_value <= :date", [
          ':date' => $date,
        ]);
      }
      else {
        $query = $connection->query("SELECT revision_id, langcode, field_scheduled_time_value, entity_id FROM {node_revision__field_scheduled_time}");
      }

      $revisions = $query->fetchAll();

      if ($revisions) {

        $nodes = [];
        foreach ($this->arrayMultidimUnique($revisions, 'revision_id') as $revision) {
          // Get revision id from the field.
          $revision_id = $revision->revision_id;
          $schedule = $revision->field_scheduled_time_value;
          $lang = $revision->langcode;

          // Load revision with lang and translation.
          $rev = $node_storage->loadRevision($revision_id)->getTranslation($lang);
          if ($rev) {
            if ($filterDate == TRUE) {
              if ($lang && $schedule <= $date) {
                array_push($nodes, $rev);
              }
            }
            else {
              array_push($nodes, $rev);
            }
          }
        }
        return count($nodes) >= 1 ? $nodes : NULL;
      }
    }
  }

  /**
   * Helper method to load arrayMultidimUnique by key.
   *
   * @param array $array
   *   Array of nodes.
   * @param string $key
   *   String of key index.
   *
   * @return array
   *   Array of [object node].   *
   */
  public function arrayMultidimUnique(array $array, $key) {
    $temp_array = [];
    $i = 0;
    $key_array = [];

    foreach ($array as $val) {
      if (!in_array($val->$key, $key_array)) {
        $key_array[$i] = $val->$key;
        $temp_array[$i] = $val;
      }
      $i++;
    }
    return $temp_array;
  }

  /**
   * Helper method to load latest revision of each translation.
   *
   * @param string $field
   *   String of field_name.
   *
   * @return bolean
   *   Bolean of result.
   */
  public function cleanFieldScheduledTimeRevision($field) {
    $db = $this->database;

    // Delete only if date is in the past
    // https://www.drupal.org/project/moderation_scheduler/issues/3080452
    // Get a date string suitable for use with entity query.
    $date = new DrupalDateTime();
    $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $date = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $query1 = $db->delete('node_revision__field_scheduled_time');
    $query1->condition('field_scheduled_time_value', $date, '<=');
    $query1->execute();

    $query2 = $db->delete('node_revision__field_scheduled_time');
    $query2->condition('field_scheduled_time_value', $date, '<=');
    $query2->execute();

    if ($query1->execute() >= 1 || $query2->execute() >= 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
