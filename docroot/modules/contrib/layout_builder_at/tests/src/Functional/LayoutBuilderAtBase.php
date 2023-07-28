<?php

namespace Drupal\Tests\layout_builder_at\Functional;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\Tests\content_translation\Functional\ContentTranslationTestBase;

/**
 * Base class for Layout Builder Asymmetric Translations.
 */
abstract class LayoutBuilderAtBase extends ContentTranslationTestBase {

  const defaultTextFieldText = 'The untranslated field value';
  const translatedTextFieldText = 'The translated field value';
  const defaultContentBlockBody = 'Custom block content body';
  const translatedContentBlockBody = 'Translated block content body';

  /**
   * User with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $fullAdmin = NULL;

  /**
   * The entity used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The default theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fullAdmin = $this->drupalCreateUser([], NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    $permissions = parent::getAdministratorPermissions();
    $permissions[] = 'administer entity_test_mul display';
    $permissions[] = 'create and edit custom blocks';
    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    $permissions = parent::getTranslatorPermissions();
    $permissions[] = 'view test entity translations';
    $permissions[] = 'view test entity';
    $permissions[] = 'configure any layout';
    $permissions[] = 'create and edit custom blocks';
    return $permissions;
  }

  /**
   * Set up the View Display.
   */
  protected function setUpViewDisplay() {
    EntityViewDisplay::create([
      'targetEntityType' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent($this->fieldName, ['type' => 'string'])->save();
  }

  /**
   * Set up the Form Display.
   */
  protected function setUpFormDisplay() {
    EntityFormDisplay::load($this->entityTypeId . '.' . $this->bundle . '.default')
      ->setComponent(OverridesSectionStorage::FIELD_NAME, ['type' => 'layout_builder_at_copy', 'region' => 'content'])
      ->save();
  }

  /**
   * Update the Form Display.
   */
  protected function updateFormDisplay() {
    EntityFormDisplay::load($this->entityTypeId . '.' . $this->bundle . '.default')
      ->setComponent(OverridesSectionStorage::FIELD_NAME, ['type' => 'layout_builder_at_copy', 'settings' => ['appearance' => 'checked_hidden'], 'region' => 'content'])
      ->save();
  }

  /**
   * Create a block content type.
   *
   * @param $label
   * @param bool $create_body
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setupBlockType($label, $create_body = TRUE) {
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE,
    ]);
    $bundle->save();
    if ($create_body) {
      block_content_add_body_field($bundle->id());
    }
  }

  /**
   * Setup translated entity with layouts.
   */
  protected function setUpEntities() {
    $this->drupalLogin($this->administrator);

    $field_ui_prefix = 'entity_test_mul/structure/entity_test_mul';
    // Allow overrides for the layout.
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[enabled]' => TRUE], 'Save');
    $this->drupalPostForm("$field_ui_prefix/display/default", ['layout[allow_custom]' => TRUE], 'Save');

    // @todo The Layout Builder UI relies on local tasks; fix in
    //   https://www.drupal.org/project/drupal/issues/2917777.
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Create entity.
   */
  protected function createDefaultTranslationEntity() {
    // Create a test entity.
    $id = $this->createEntity([
      $this->fieldName => [['value' => self::defaultTextFieldText]],
    ], $this->langcodes[0]);
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$id]);
    $this->entity = $storage->load($id);
  }

  /**
   * Adds a layout override.
   *
   * @param bool $add_text_block
   * @param string $custom_block_content_body
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function addLayoutOverride($add_text_block = FALSE, $custom_block_content_body = self::defaultContentBlockBody) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $entity_url = $this->entity->toUrl()->toString();
    $layout_url = $entity_url . '/layout';
    $this->drupalGet($layout_url);
    $assert_session->pageTextNotContains(self::translatedTextFieldText);
    $assert_session->pageTextContains(self::defaultTextFieldText);

    // Adjust the layout.
    $this->click('.layout-builder__add-block .layout-builder__link');
    $assert_session->linkExists('Powered by Drupal');
    $this->clickLink('Powered by Drupal');
    $button = $assert_session->elementExists('css', '#layout-builder-add-block .button--primary');
    $button->press();

    $assert_session->pageTextContains('Powered by Drupal');

    if ($add_text_block) {
      $this->click('.layout-builder__add-block .layout-builder__link');
      $this->clickLink('Create custom block');
      $edit = [
        'settings[label]' => 'Label',
        'settings[label_display]' => FALSE,
        'settings[block_form][body][0][value]' => $custom_block_content_body
      ];
      $this->drupalPostForm(NULL, $edit, $button->getValue());
    }

    $assert_session->buttonExists('Save layout');
    $page->pressButton('Save layout');
  }

  /**
   * Update layout override.
   *
   * @param $url
   * @param bool $update_text_block
   * @param string $custom_block_content_body
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function updateLayoutOverride($url, $update_text_block = FALSE, $custom_block_content_body = self::translatedContentBlockBody) {
    $user = $this->loggedInUser;
    $this->drupalLogin($this->fullAdmin);
    $assert_session = $this->assertSession();
    $layout_url = $url . '/layout';

    $this->drupalGet($layout_url);
    $assert_session->statusCodeEquals(200);
    $page = $this->getSession()->getPage();

    if ($update_text_block) {
      $id = $assert_session->elementExists('css', '.layout-builder__region > div:nth-child(4) > div');

      $groups = _contextual_id_to_links($id->getAttribute('data-contextual-id'));
      $contextual_links_manager = \Drupal::service('plugin.manager.menu.contextual_link');

      $items = [];
      foreach ($groups as $group => $args) {
        $args += [
          'route_parameters' => [],
          'metadata' => [],
        ];
        $items += $contextual_links_manager->getContextualLinksArrayByGroup($group, $args['route_parameters'], $args['metadata']);
      }

      $item = $items['layout_builder_block_update'];
      $item['localized_options']['language'] = \Drupal::languageManager()->getLanguage($item['metadata']['langcode']);
      $update_url = Url::fromRoute(isset($item['route_name']) ? $item['route_name'] : '', isset($item['route_parameters']) ? $item['route_parameters'] : [], $item['localized_options'])->toString();
      $this->drupalGet($update_url);
      $button = $assert_session->elementExists('css', '.button--primary');
      $edit = [
        'settings[block_form][body][0][value]' => $custom_block_content_body
      ];
      $this->drupalPostForm(NULL, $edit, $button->getValue());
    }

    $page->pressButton('Save layout');

    $this->drupalLogin($user);
  }

  /**
   * Adds an entity translation.
   *
   * @param $copy
   *   Whether to copy the blocks or not.
   * @param $target
   *   The target of the langcode.
   * @param $source_language
   *   Which source language to use, if applicable.
   */
  protected function addEntityTranslation($copy = FALSE, $target = 2, $source_language = NULL) {
    $user = $this->loggedInUser;
    $this->drupalLogin($this->translator);
    $add_translation_url = Url::fromRoute("entity.$this->entityTypeId.content_translation_add", [
      $this->entityTypeId => $this->entity->id(),
      'source' => $this->langcodes[0],
      'target' => $this->langcodes[$target],
    ]);

    $this->drupalGet($add_translation_url);

    if ($source_language) {
      $this->drupalPostForm(NULL, ['source_langcode[source]' => $source_language], 'Change');
    }

    $edit = ["{$this->fieldName}[0][value]" => 'The translated field value'];
    if ($copy) {
      $this->assertText('Copy blocks into translation');
      $edit['layout_builder__layout[value]'] = TRUE;
    }
    elseif (isset($copy)) {
      $this->assertNoText('Copy blocks into translation');
    }

    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->drupalLogin($user);
  }

  /**
   * Update a node.
   *
   * @param $langcode
   */
  protected function updateNode($langcode) {
    $user = $this->loggedInUser;
    $this->drupalLogin($this->fullAdmin);
    $update_url = Url::fromRoute("entity.$this->entityTypeId.edit_form", [
      $this->entityTypeId => $this->entity->id(),
    ], ['language' => \Drupal::languageManager()->getLanguage($langcode)]);

    $edit = [];
    $this->drupalPostForm($update_url, $edit, 'Save');
    $this->drupalLogin($user);
  }

}
