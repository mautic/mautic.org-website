<?php

namespace Drupal\Tests\layout_builder_at\Functional;

/**
 * Layout Builder Asymmetric Translations tests.
 *
 * @group layout_builder_at
 */
class LayoutBuilderAtTranslationTest extends LayoutBuilderAtBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'contextual',
    'entity_test',
    'layout_builder',
    'layout_builder_at',
    'block',
  ];

  /**
   * The entity used for testing.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpViewDisplay();
    $this->setUpFormDisplay();
    $this->setUpEntities();
  }

  /**
   * Tests that layout overrides have different blocks.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function testAsymmetricTranslation() {
    $assert_session = $this->assertSession();

    $this->drupalGet('entity_test_mul/structure/entity_test_mul/display/default/layout');

    // Make layout builder field translatable.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'settings[entity_test_mul][entity_test_mul][fields][layout_builder__layout]' => TRUE,
    ];
    $assert_session->pageTextNotContains('Layout Builder does not support translating layouts.');
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    // Create default entity.
    $this->createDefaultTranslationEntity();

    // Add layout override.
    $this->addLayoutOverride();

    // Now translate.
    $this->addEntityTranslation(NULL);

    $entity_url = $this->entity->toUrl()->toString();
    $language = \Drupal::languageManager()->getLanguage($this->langcodes[2]);
    $translated_entity_url = $this->entity->toUrl('canonical', ['language' => $language])->toString();
    $translated_layout_url = $translated_entity_url . '/layout';

    $this->drupalGet($entity_url);
    $assert_session->pageTextNotContains(self::translatedTextFieldText);
    $assert_session->pageTextContains(self::defaultTextFieldText);
    $assert_session->linkExists('Layout');
    $assert_session->pageTextContains('Powered by Drupal');

    $this->drupalGet($translated_entity_url);
    $assert_session->pageTextNotContains(self::defaultTextFieldText);

    // Translated field is visible as the field block is copied over from the
    // default.
    $assert_session->pageTextContains(self::translatedTextFieldText);

    // Layout should be accessible.
    $assert_session->linkExists('Layout');

    // Powered by Drupal block is not copied over.
    $assert_session->pageTextNotContains('Powered by Drupal');

    $this->drupalGet($translated_layout_url);
    $assert_session->statusCodeEquals(200);

    // Test source language.
    $second_language = \Drupal::languageManager()->getLanguage($this->langcodes[1]);
    $translated_entity_url = $this->entity->toUrl('canonical', ['language' => $second_language])->toString();
    $this->addEntityTranslation(TRUE, 1, 'fr');
    $this->drupalGet($translated_entity_url);
    $assert_session->pageTextContains(self::translatedTextFieldText);
    $assert_session->pageTextNotContains('Powered by Drupal');
  }

}
