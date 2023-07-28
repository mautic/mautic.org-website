<?php

namespace Drupal\Tests\mautic\Functional;

use Drupal\Core\Config\Config;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Mautic module.
 *
 * @group mautic
 */
class JsAssetTest extends BrowserTestBase
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
    'user'
  ];

  /**
   * Test the path with the js snippet in the header
   */
  public function testPathWithSnippetHeader()
  {
    // Page
    $page = Url::fromRoute('entity.node_type.collection');
    // Snippet in the header
    $this->config
      ->set('visibility.request_path_mode', 1)
      ->set('mautic_base_url', 'https:/mautic.test/mtc.js')
      ->set('visibility.request_path_pages', '/'.$page->getInternalPath())
      ->set('header', true)
      ->save();
    // Start the session.
    $session = $this->assertSession();
    // Navigate to the page
    $this->drupalGet($page);
    // Assure the page was loaded
    $session->statusCodeEquals(200);
    // Check if the snippet is in the code
    $currentPage = $this->getSession()->getPage();
    $this->assertTextInHeader('mautic.js', $currentPage->getHtml());
  }

  /**
   * Test the path with the js snippet in the footer
   */
  public function testPathWithSnippetFooter()
  {
    // Page
    $page = Url::fromRoute('entity.node_type.collection');
    // Snippet in the footer
    $this->config
      ->set('visibility.request_path_mode', 1)
      ->set('mautic_base_url', 'https:/mautic.test/mtc.js')
      ->set('visibility.request_path_pages', '/'.$page->getInternalPath())
      ->set('header', false)
      ->save();
    // Start the session.
    $session = $this->assertSession();
    // Navigate to the page
    $this->drupalGet($page);
    // Assure the page was loaded
    $session->statusCodeEquals(200);
    // Check if the snippet is in the code
    $currentPage = $this->getSession()->getPage();
    $this->assertTextInFooter('mautic.js', $currentPage->getHtml());
  }

  /**
   * Test the path without the js snippet
   */
  public function testPathWithoutSnippet()
  {
    // Page
    $page = Url::fromRoute('entity.node_type.collection');
    // Snippet not allowed in the page
    $this->config
      ->set('visibility.request_path_mode', 0)
      ->set('mautic_base_url', 'https:/mautic.test/mtc.js')
      ->set('visibility.request_path_pages', '/'.$page->getInternalPath())
      ->set('header', false)
      ->save();
    // Start the session.
    $session = $this->assertSession();
    // Navigate to the page
    $this->drupalGet($page);
    // Assure the page was loaded
    $session->statusCodeEquals(200);
    // Check if the snippet is in the code
    $currentPage = $this->getSession()->getPage();
    $this->assertTextNotInPage('mautic.js', $currentPage->getHtml());
  }

  private function assertTextInHeader($text, $html)
  {
    $textPosition = strpos($html, $text);
    $endHeaderPosition = strpos($html, '</header>');
    $this->assertLessThan($endHeaderPosition, $textPosition);
  }

  private function assertTextInFooter($text, $html)
  {
    $textPosition = strpos($html, $text);
    $startFooterPosition = strpos($html, '<footer>');
    $this->assertGreaterThan($startFooterPosition, $textPosition);
  }

  private function assertTextNotInPage($text, $html)
  {
    $textPosition = strpos($html, $text);
    $this->assertFalse($textPosition);
  }
}
