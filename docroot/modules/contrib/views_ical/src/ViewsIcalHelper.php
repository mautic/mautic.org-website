<?php

namespace Drupal\views_ical;

use Drupal\Core\Entity\ContentEntityInterface;
use Eluceo\iCal\Component\Event;
use Drupal\views\ResultRow;

/**
 * Helper methods for views_ical.
 */
final class ViewsIcalHelper implements ViewsIcalHelperInterface {

  private $view;


  /**
   * @param $view
   */
  public function setView($view){
    $this->view = $view;
  }


  /**
   * Creates an event with default data.
   *
   * Event summary, location and description are set as defaults.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be used for default data.
   * @param array $field_mapping
   *   Views field option and entity field name mapping.
   *   Example:
   *   [
   *     'date_field' => 'field_event_date',
   *     'summary_field' => 'field_event_summary',
   *     'description_field' => 'field_event_description',
   *   ]
   *   End of example.
   *
   * @return \Eluceo\iCal\Component\Event
   *   A new event.
   *
   * @see \Drupal\views_ical\Plugin\views\style\Ical::defineOptions
   */
  protected function createDefaultEvent(ContentEntityInterface $entity, array $field_mapping): Event {
    if(isset($field_mapping['uid_field'])
      && ($field_mapping['uid_field'] == 'nid'
        || $field_mapping['uid_field'] == 'nothing')) {
      // If the Uid field is the nid, access with the id method.
      $uid = $entity->id();
      if(isset($this->view->field[$field_mapping['uid_field']]->options['alter']['alter_text'])
        && $this->view->field[$field_mapping['uid_field']]->options['alter']['alter_text']) {
        // I need rewrite of the UID field to happen here.
        // This is really hacky, It would be really nice to find a way to render as the row.
        $alter_text = $this->view->field[$field_mapping['uid_field']]->options['alter']['text'];
        $fields = array_keys($this->view->field);
        foreach ($fields as $field) {
          if ($entity->hasField($field)) {
            // This is where we really need to move this to a proper row handler.
            // for a date/created field, render according to the views definition.
            if ($entity->get($field)->getDataDefinition()->getType() == 'created') {
              $settings = $this->view->field['created']->options['settings'];
              ['custom_date_format'];
              if($settings['date_format'] == 'custom') {
                $field_value =  \Drupal::service('date.formatter')->format($entity->get($field)->getString(), 'custom', $settings['custom_date_format']);
              }
              else {
                $field_value =  \Drupal::service('date.formatter')->format($entity->get($field)->getString(), $settings['date_format']);
              }
            }
            else {
              $field_value = $entity->get($field)->getString();
            }
            $alter_text= str_replace("{{ $field }}", $field_value, $alter_text);
          }
        }
        $uid = $alter_text;
      }
    }
    else if(isset($field_mapping['uid_field'])
      && $field_mapping['uid_field'] != 'none'
      && $entity->hasField($field_mapping['uid_field'])
      && !$entity->get($field_mapping['uid_field'])->isEmpty()) {
      $uid = $entity->get($field_mapping['uid_field'])->getString();
    }
    else {
      $uid = null;
    }

    $event = new Event($uid);

    // Summary field.
    if (isset($field_mapping['summary_field']) && $entity->hasField($field_mapping['summary_field'])) {
      if ($field_mapping['summary_field'] == 'body'  && !$entity->get('body')->isEmpty()) {
        $summary = $entity->get('body')->getValue()[0]['value'];
      }
      else {
        $summary = $entity->get($field_mapping['summary_field'])->getString();
      }
      if ($summary) {
        $event->setSummary($summary);
      }
    }

    // Rrule field.
    if (isset($field_mapping['rrule_field']) && $entity->hasField($field_mapping['rrule_field'])) {

      $rrule = $entity->get($field_mapping['rrule_field'])->getString();

      if ($rrule) {
        $event->setRrule($rrule);
      }
    }

    // Location field
    if (isset($field_mapping['location_field']) && $entity->hasField($field_mapping['location_field'])) {
      if ($field_mapping['location_field'] == 'body' && !$entity->get('body')->isEmpty()) {
        $location = $entity->get('body')->getValue()[0]['value'];
        $event->setLocation($location);
      }
      else {
        $location = $entity->{$field_mapping['location_field']}->first();
        $event->setLocation($location->getValue()['value']);
      }

    }

    // URL field
    if (isset($field_mapping['url_field']) && $entity->hasField($field_mapping['url_field'])) {
      if ($field_mapping['url_field'] == 'body' && !$entity->get('body')->isEmpty()) {
        $url = $entity->get('body')->getValue()[0]['value'];
        $event->setUrl($url);
      }
      else {
        $url = $entity->{$field_mapping['url_field']}->first();
        $event->setUrl($url->getValue()['value']);
      }

    }

    // Description field
    if (isset($field_mapping['description_field']) && $entity->hasField($field_mapping['description_field'])) {
      if ($field_mapping['location_field'] == 'body') {
        /** @var \Drupal\Core\Field\FieldItemInterface $description */
        $description = $entity->{$field_mapping['description_field']}->getValue()[0]['value'];
        $event->setDescription($description);
      }
      else {
        /** @var \Drupal\Core\Field\FieldItemInterface $description */
        $description = $entity->{$field_mapping['description_field']}->first();
        $event->setDescription(\strip_tags($description->getValue()['value']));
      }
    }

    $event->setUseTimezone(TRUE);

    return $event;
  }

  /**
   * Create an event based on a daterange field.
   *
   * @param array $events
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \DateTimeZone $timezone
   * @param array $field_mapping
   */
  public function addDateRangeEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    $entity = $row->_entity;


    $utc_timezone = new \DateTimeZone('UTC');
    $datefield_values = $entity->get($field_mapping['date_field'])->getValue();

    // TODO: make these separate functions
    // Loop over the values to support multiple cardinality dates, which can
    // represent multiple events.
    foreach ($entity->get($field_mapping['date_field'])->getValue() as $date_entry) {

      // generate the event.
      $event = $this->createDefaultEvent($entity, $field_mapping);

      // Set the start time
      $start_datetime = new \DateTime($date_entry['value'], $utc_timezone);
      $start_datetime->setTimezone($timezone);
      $event->setDtStart($start_datetime);

      // Loop over field values so we can support daterange fields with multiple cardinality.
      if (!empty($date_entry['end_value'])) {
        $end_datetime = new \DateTime($date_entry['end_value'], $utc_timezone);
        $end_datetime->setTimezone($timezone);

        $event->setDtEnd($end_datetime);

        // If this is a date_all_day field, pull the all day option from that.
        if($date_all_day = false) {
          // TODO: implement
        }
        else {
          if (isset($field_mapping['no_time_field']) && $field_mapping['no_time_field'] != 'none') {
            $all_day = $entity->get($field_mapping['no_time_field'])->getValue();
            if ($all_day && isset($all_day[0]['value']) && $all_day[0]['value']) {
              $event->setNoTime(true);
            }
          }
        }
      }
      //else {
      // is DTEND is not a required field, but if it is not included, nor
      // is duration (which we are not using here), then the event's duration
      // is taken to be one day. But do we need to explicitly define that here?
      // Do calendar apps handle that? https://tools.ietf.org/html/rfc5545#section-3.6.1
      //}

      $events[] = $event;
    }
  }


  /**
   * Create an event based on a datetime field
   *
   * @param array $events
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param \DateTimeZone $timezone
   * @param array $field_mapping
   */
  public function addDateTimeEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    $entity = $row->_entity;


    $utc_timezone = new \DateTimeZone('UTC');
    $datefield_values = $entity->get($field_mapping['date_field'])->getValue();

    // If an end date field was defined, then the content model is most likely
    // using two, single cardinality fields for a start and an end date.
    if (isset($field_mapping['end_date_field']) && $field_mapping['end_date_field'] != 'none') {

      // generate the event
      $event = $this->createDefaultEvent($entity, $field_mapping);

      // set the start time.
      $date_entry = $datefield_values[0];
      $start_datetime = new \DateTime($date_entry['value'], $utc_timezone);
      $start_datetime->setTimezone($timezone);
      $event->setDtStart($start_datetime);

      // Set the end time
      $end_date_field_values = $entity->get($field_mapping['end_date_field'])->getValue();
      $end_date_entry = $end_date_field_values[0];
      $end_datetime = new \DateTime($end_date_entry['value'], $utc_timezone);
      $end_datetime->setTimezone($timezone);
      $event->setDtEnd($end_datetime);

      // All day events.
      if (isset($field_mapping['no_time_field']) && $field_mapping['no_time_field'] != 'none') {
        $all_day = $entity->get($field_mapping['no_time_field'])->getValue();
        if ($all_day && isset($all_day[0]['value']) && $all_day[0]['value']) {
          $event->setNoTime(TRUE);
        }
      }
      $events[] = $event;
    }


  }

  /**
   * {@inheritdoc}
   */
  public function addEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    // All code moved to field-specific methods.
  }




  /**
   * {@inheritdoc}
   */
  public function addDateRecurEvent(array &$events, ResultRow $row, \DateTimeZone $timezone, array $field_mapping): void {
    /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem[] $field_items */
    $entity = $row->_entity;
    $field_items = $entity->{$field_mapping['date_field']};

    foreach ($field_items as $index => $item) {
      /** @var \Drupal\date_recur\DateRange[] $occurrences */
      $occurrences = $item->getHelper()->getOccurrences();

      foreach ($occurrences as $occurrence) {
        $event = $this->createDefaultEvent($entity, $field_mapping);

        /** @var \DateTime $start_datetime */
        $start_datetime = $occurrence->getStart();
        $start_datetime->setTimezone($timezone);
        $event->setDtStart($start_datetime);

        /** @var \DateTime $end_datetime */
        $end_datetime = $occurrence->getEnd();
        $end_datetime->setTimezone($timezone);
        $event->setDtEnd($end_datetime);

        $events[] = $event;
      }
    }
  }

  /**
   * Our own token replacement
   *
   * @param $value
   * @param null $row_index
   * @return string
   */
  public function replaceTokens($value, $entity = NULL) {

    $token_service = \Drupal::token();
    // Replace the token for subject.
    $email_auth = $token_service->replace($value, array('node' => $entity));

    return $value;
  }


}
