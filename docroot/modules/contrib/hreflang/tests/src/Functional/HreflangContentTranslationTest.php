<?php

namespace Drupal\Tests\hreflang\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests for presence of the hreflang link element.
 *
 * @group hreflang
 */
class HreflangContentTranslationTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['hreflang', 'content_translation'];

  /**
   * Functional tests for the hreflang tag.
   */
  public function testHreflangTag() {
    global $base_url;
    // User to add language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'administer site configuration',
      'create page content',
    ]);
    $this->drupalLogin($admin_user);
    // Add predefined language.
    $this->drupalGet('admin/config/regional/language/add');
    $edit = ['predefined_langcode' => 'fr'];
    $this->submitForm($edit, 'Add language');
    // Add node.
    $this->drupalGet('node/add/page');
    $edit = ['title[0][value]' => 'Test front page'];
    $this->submitForm($edit, 'Save');
    // Set front page.
    $this->drupalGet('admin/config/system/site-information');
    $edit = ['site_frontpage' => '/node/1'];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('');
    // English hreflang found on English page.
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/" />');
    // French hreflang found on English page.
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr" />');
    // English hreflang found on English page.
    $this->assertSession()->responseNotContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/node/1" />');
    // French hreflang found on English page.
    $this->assertSession()->responseNotContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/node/1" />');
    $this->drupalGet('fr');
    // English hreflang found on French page.
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/" />');
    // French hreflang found on French page.
    $this->assertSession()->responseContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr" />');
    // English hreflang found on French page.
    $this->assertSession()->responseNotContains('<link rel="alternate" hreflang="en" href="' . $base_url . '/node/1" />');
    // French hreflang found on French page.
    $this->assertSession()->responseNotContains('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/node/1" />');
  }

}
