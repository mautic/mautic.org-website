<?php

namespace Drupal\config_readonly;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Readonly form event.
 */
class ReadOnlyFormEvent extends Event {

  const NAME = 'config_readonly_form_event';

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Flag as to whether the form is read only.
   *
   * @var bool
   */
  protected $readOnlyForm;

  /**
   * Constructs a new ReadOnlyFormEvent object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function __construct(FormStateInterface $form_state) {
    $this->readOnlyForm = FALSE;
    $this->formState = $form_state;
  }

  /**
   * Get the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Mark the form as read-only.
   */
  public function markFormReadOnly() {
    $this->readOnlyForm = TRUE;
  }

  /**
   * Check whether the form is read-only.
   *
   * @return bool
   *   Whether the form is read-only.
   */
  public function isFormReadOnly() {
    return $this->readOnlyForm;
  }

}
