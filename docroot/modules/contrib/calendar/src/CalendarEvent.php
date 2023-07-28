<?php

namespace Drupal\calendar;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines a calendar event object.
 */
class CalendarEvent {

  /**
   * The start date of the event.
   *
   * @var \DateTime
   */
  protected $startDate;

  /**
   * The end date of the event.
   *
   * @var \DateTime
   */
  protected $endDate;

  /**
   * The granularity of this event (e.g. "day", "second").
   *
   * @var string
   */
  protected $granularity;

  /**
   * Defines whether or not this event's duration is all day.
   *
   * @var bool
   */
  protected $allDay;

  /**
   * The timezone of the event.
   *
   * @var \DateTimeZone
   */
  protected $timezone;


  /**
   * An array of the fields to render.
   *
   * @var array
   */
  protected $renderedFields;

  /**
   * The array of labels to be used for this stripe option.
   *
   * @var array
   */
  protected $stripeLabels;

  /**
   * The hex code array of the color to be used.
   *
   * @var string
   */
  protected $stripeHexes;

  /**
   * Whether this event covers multiple days.
   *
   * @var bool
   */
  protected $isMultiDay;

  /**
   * The content entity interface object.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * CalendarEvent constructor.
   */
  public function __construct(ContentEntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Getter for the entity id.
   *
   * @return intmixed
   *   The entity id.
   */
  public function getEntityId() {
    return $this->entity->id();
  }

  /**
   * Getter for the entity type id.
   *
   * @todo Remove for getType
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId() {
    return $this->entity->getEntityTypeId();
  }

  /**
   * Function to get entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Getter for the type.
   *
   * @return string
   *   The type of the entity.
   */
  public function getType() {
    return $this->entity->getEntityTypeId();
  }

  /**
   * {@inheritDoc}
   */
  public function getBundle() {
    return $this->entity->bundle();
  }

  /**
   * Getter for the start date.
   *
   * @return \DateTime
   *   The start date.
   */
  public function getStartDate() {
    return $this->startDate;
  }

  /**
   * Setter for the start date.
   *
   * @param \DateTime $startDate
   *   The start date.
   */
  public function setStartDate($startDate) {
    $this->startDate = $startDate;
  }

  /**
   * Getter for the end date.
   *
   * @return \DateTime
   *   The end date.
   */
  public function getEndDate() {
    return $this->endDate;
  }

  /**
   * Setter for the end date.
   *
   * @param \DateTime $endDate
   *   The end date.
   */
  public function setEndDate($endDate) {
    $this->endDate = $endDate;
  }

  /**
   * Getter for the event granularity.
   *
   * @return string
   *   The event granularity.
   */
  public function getGranularity() {
    return $this->granularity;
  }

  /**
   * Setter for the event granularity.
   *
   * @param string $granularity
   *   The event granularity.
   */
  public function setGranularity($granularity) {
    $this->granularity = $granularity;
  }

  /**
   * Getter for the all day property.
   *
   * @return bool
   *   TRUE if the event is all day, FALSE otherwise.
   */
  public function isAllDay() {
    return $this->allDay;
  }

  /**
   * Setter for the all day property.
   *
   * @param bool $allDay
   *   TRUE if the event is all day, FALSE otherwise.
   */
  public function setAllDay($allDay) {
    $this->allDay = $allDay;
  }

  /**
   * Getter for the timezone property.
   *
   * @return \DateTimeZone
   *   The timezone of this event.
   */
  public function getTimezone() {
    return $this->timezone;
  }

  /**
   * Setter for the timezone property.
   *
   * @param \DateTimeZone $timezone
   *   The timezone of this event.
   */
  public function setTimezone($timezone) {
    $this->timezone = $timezone;
  }

  /**
   * The title getter.
   *
   * @return string
   *   The title of the event.
   */
  public function getTitle() {
    return $this->entity->label();
  }

  /**
   * Getter for the url.
   *
   * @return string
   *   The public url to this event.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getUrl() {
    return $this->entity->toUrl();
  }

  /**
   * Getter for the rendered fields array.
   *
   * @return array
   *   The rendered fields array.
   */
  public function getRenderedFields() {
    return $this->renderedFields;
  }

  /**
   * Setter for the rendered fields array.
   *
   * @param array $renderedFields
   *   The rendered fields array.
   */
  public function setRenderedFields(array $renderedFields) {
    $this->renderedFields = $renderedFields;
  }

  /**
   * Getter for the stripe label array.
   *
   * If no array is defined, this initializes the variable to an empty array.
   *
   * @return array
   *   The stripe labels.
   */
  public function getStripeLabels() {
    if (!isset($this->stripeLabels)) {
      $this->stripeLabels = [];
    }
    return $this->stripeLabels;
  }

  /**
   * Setter for the stripe label array.
   *
   * @param string $stripeLabels
   *   The stripe labels.
   */
  public function setStripeLabels($stripeLabels) {
    $this->stripeLabels = $stripeLabels;
  }

  /**
   * Getter for the stripe hex code array.
   *
   * If no array is defined, this initializes the variable to an empty array.
   *
   * @return array
   *   The stripe hex code array.
   */
  public function getStripeHexes() {
    if (!isset($this->stripeHexes)) {
      $this->stripeHexes = [];
    }
    return $this->stripeHexes;
  }

  /**
   * The setter for the stripe hex code array.
   *
   * @param string $stripeHexes
   *   The stripe hex code array.
   */
  public function setStripeHexes($stripeHexes) {
    $this->stripeHexes = $stripeHexes;
  }

  /**
   * Add a single strip hex.
   *
   * @param $stripeHex
   */
  public function addStripeHex($stripeHex) {
    $this->stripeHexes[] = $stripeHex;
  }

  /**
   * Add a single strip label.
   *
   * @param $stripeLabel
   */
  public function addStripeLabel($stripeLabel) {
    $this->stripeLabels[] = $stripeLabel;
  }

  /**
   * The getter which indicates whether an event covers multiple days.
   *
   * @return bool
   */
  public function getIsMultiDay() {
    return $this->isMultiDay;
  }

  /**
   * The setter to indicate whether an event covers multiple days.
   */
  public function setIsMultiDay($isMultiDay) {
    $this->isMultiDay = $isMultiDay;
  }

}
