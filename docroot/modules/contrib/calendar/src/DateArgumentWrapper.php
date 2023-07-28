<?php

namespace Drupal\calendar;

use Drupal\views\Plugin\views\argument\Date;

/**
 * The DateArgumentWrapper class.
 */
class DateArgumentWrapper {

  /**
   * The date object.
   *
   * @var \Drupal\views\Plugin\views\argument\Date
   */
  protected $dateArg;

  /**
   * The variable declaration of type DateTime.
   *
   * @var \DateTime
   */
  protected $minDate;

  /**
   * The variable declaration of type DateTime.
   *
   * @var \DateTime
   */
  protected $maxDate;

  /**
   * The variable declaration of type int.
   *
   * @var int
   */
  protected $position;

  /**
   * Function to get the position.
   *
   * @return int
   *   Returns position.
   */
  public function getPosition() {
    return $this->position;
  }

  /**
   * Function to set position.
   *
   * @param int $position
   *   The position.
   */
  public function setPosition($position) {
    $this->position = $position;
  }

  /**
   * The function to return date.
   *
   * @return \Drupal\views\Plugin\views\argument\Date
   *   Returns date.
   */
  public function getDateArg() {
    return $this->dateArg;
  }

  /**
   * DateArgumentWrapper constructor.
   */
  public function __construct(Date $dateArg) {
    $this->dateArg = $dateArg;
  }

  /**
   * Get the argument date format for the handler.
   *
   * \Drupal\views\Plugin\views\argument\Date has no getter for
   * protected argFormat member.
   *
   * @return string
   */
  public function getArgFormat() {
    $class = get_class($this->dateArg);
    if (stripos($class, 'YearMonthDate') !== FALSE) {
      return 'Ym';
    }
    if (stripos($class, 'FullDate') !== FALSE) {
      return 'Ymd';
    }
    if (stripos($class, 'YearDate') !== FALSE) {
      return 'Y';
    }
    if (stripos($class, 'YearWeekDate') !== FALSE) {
      return 'oW';
    }
  }

  /**
   * {@inheritDoc}
   */
  public function createDateTime() {
    if ($value = $this->dateArg->getValue()) {
      if (!$this->validateValue()) {
        return FALSE;
      }
      return $this->createFromFormat($value);
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  protected function createFromFormat($value) {
    $format = $this->getArgFormat();
    if ($format == 'oW') {
      $date = new \DateTime();
      $year = (int) substr($value, 0, 4);
      $month = (int) substr($value, 4, 2);
      $date->setISODate($year, $month);
    }
    else {
      // Adds a ! character to the format so that the date is reset instead of
      // using the current day info, which can lead to issues for months with
      // 31 days.
      $format = '!' . $this->getArgFormat();
      $date = \DateTime::createFromFormat($format, $value);
    }
    return $date;
  }

  /**
   * {@inheritDoc}
   */
  public function format($format) {
    if ($date = $this->createDateTime()) {
      return $date->format($format);
    }
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getGranularity() {
    $plugin_id = $this->dateArg->getPluginId();
    $plugin_granularity = str_replace('datetime_', '', $plugin_id);
    $plugin_granularity = str_replace('date_', '', $plugin_granularity);
    switch ($plugin_granularity) {
      case 'year_month':
        return 'month';

      break;
      // Views and Datetime module don't use same suffix :(.
      case 'full_date':
      case 'fulldate':
        return 'day';

      break;
      case 'year':
        return 'year';

      break;
      case 'year_week';
        return 'week';

      break;
    }
  }

  /**
   * Function to get min date.
   *
   * @return \DateTime
   */
  public function getMinDate() {
    if (!$this->minDate) {
      $date = $this->createDateTime();
      $granularity = $this->getGranularity();
      if ($granularity == 'month') {
        $date->modify("first day of this month");
      }
      elseif ($granularity == 'week') {
        $date->modify('this week');
      }
      elseif ($granularity == 'year') {
        $date->modify("first day of January");
      }
      $date->setTime(0, 0, 0);
      $this->minDate = $date;
    }
    return $this->minDate;
  }

  /**
   * Function to get max date.
   *
   * @return \DateTime
   */
  public function getMaxDate() {
    if (!$this->maxDate) {
      $date = $this->createDateTime();
      $granularity = $this->getGranularity();
      if ($granularity == 'month') {
        $date->modify("last day of this month");
      }
      elseif ($granularity == 'week') {
        $date->modify('this week +6 days');
      }
      elseif ($granularity == 'year') {
        $date->modify("last day of December");
      }
      $date->setTime(23, 59, 59);
      $this->maxDate = $date;
    }
    return $this->maxDate;
  }

  /**
   * Check if a string value is valid for this format.
   *
   * \DateTime::createFromFormat will not throw an error but try to make a date
   * \DateTime::getLastErrors() is also not reliable.
   *
   * @param string $value
   *
   * @return bool
   */
  public function validateValue() {
    $value = $this->dateArg->getValue();
    if (empty($value)) {
      return FALSE;
    }
    if ($this->getArgFormat() == 'oW') {
      $info = $this->getYearWeek($value);
      // Find the max week for a year. Some years start a 53rd week.
      $max_week = gmdate("W", strtotime("28 December {$info['year']}"));
      return $info['week'] >= 1 && $info['week'] <= $max_week;

    }
    else {
      $created_date = $this->createFromFormat($value);
      return $created_date && $created_date->format($this->getArgFormat()) == $value;
    }

  }

  /**
   * {@inheritDoc}
   */
  protected function getYearWeek($value) {
    if (is_numeric($value) && strlen($value) == 6) {
      $return['year'] = (int) substr($value, 0, 4);
      $return['week'] = (int) substr($value, 4, 2);
      return $return;
    }
    return FALSE;
  }

}
