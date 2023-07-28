<?php

namespace Drupal\Tests\mautic\Functional;

use Drupal\Core\Config\Config;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Mautic module.
 *
 * @requires module acquia_lift
 * @group mautic
 */
class LiftCustomFieldTest extends BrowserTestBase
{
  /**
   * @var Config
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp() {
    $this->checkRequirements();
    parent::setUp();
    // Mautic settings
    $this->config = \Drupal::configFactory()->getEditable('mautic.settings');
    // Mautic module on
    $this->config->set('mautic_enable', true);
    // Create user
    $user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer content types',
    ));
    // Login.
    $this->drupalLogin($user);
  }

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = [
    'mautic',
    'node',
    'user',
    'acquia_lift'
  ];

  /**
   * Test the path with the Acquia Personalization Integration snippet
   */
  public function testPathWithLiftSnippet()
  {
    // Page
    $page = Url::fromRoute('entity.node_type.collection');
    // Snippet in the header
    $this->config
      ->set('visibility.request_path_mode', 1)
      ->set('mautic_base_url', 'https:/mautic.test/mtc.js')
      ->set('visibility.request_path_pages', '/'.$page->getInternalPath())
      ->set('lift_enable', true)
      ->save();
    // Start the session.
    $session = $this->assertSession();
    // Navigate to the page
    $this->drupalGet($page);
    // Assure the page was loaded
    $session->statusCodeEquals(200);
    // Check if the snippet is in the code
    $currentPage = $this->getSession()->getPage();
    $this->assertStringContainsString('mautic.customFields.js', $currentPage->getHtml());
  }

  /**
   * Test the path without the Acquia Personalization Integration snippet
   */
  public function testPathWithoutLiftSnippet()
  {
    // Page
    $page = Url::fromRoute('entity.node_type.collection');
    // Snippet in the header
    $this->config
      ->set('visibility.request_path_mode', 1)
      ->set('mautic_base_url', 'https:/mautic.test/mtc.js')
      ->set('visibility.request_path_pages', '/'.$page->getInternalPath())
      ->set('lift_enable', false)
      ->save();
    // Start the session.
    $session = $this->assertSession();
    // Navigate to the page
    $this->drupalGet($page);
    // Assure the page was loaded
    $session->statusCodeEquals(200);
    // Check if the snippet is in the code
    $currentPage = $this->getSession()->getPage();
    $this->assertStringNotContainsString('mautic.customFields.js', $currentPage->getHtml());
  }
}
