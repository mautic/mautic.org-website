<?php

namespace Drupal\Tests\hreflang\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for presence of the hreflang link element.
 *
 * @group hreflang
 */
class HreflangTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['hreflang', 'language'];

  /**
   * Functional tests for the hreflang tag.
   */
  public function testHreflangTag() {
    global $base_url;
    // User to add language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);
    // Add predefined language.
    $this->drupalGet('admin/config/regional/language/add');
    $edit = ['predefined_langcode' => 'fr'];
    $this->submitForm($edit, 'Add language');
    $this->drupalGet('admin');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/admin" />');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin" />');
    $this->drupalGet('fr/admin');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/admin" />');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin" />');

    // Disable URL detection and enable session detection.
    $this->drupalGet('admin/config/regional/language/detection');
    $edit = [
      'language_interface[enabled][language-url]' => FALSE,
      'language_interface[enabled][language-session]' => '1',
    ];
    $this->submitForm($edit, $this->t('Save settings'));

    $this->drupalGet('admin');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/admin?language=fr" />');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin" />');
    $this->drupalGet('admin', ['query' => ['language' => 'en']]);
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/admin?language=fr" />');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin?language=en" />');
    $this->drupalGet('admin', ['query' => ['language' => 'fr']]);
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/admin?language=fr" />');
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/admin?language=en" />');
  }

}
