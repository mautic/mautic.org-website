<?php

namespace Drupal\Tests\config_readonly\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Tests read-only module config functionality.
 *
 * @group ConfigReadOnly
 */
class ReadOnlyConfigTest extends BrowserTestBase {

  /**
   * User account with administrative permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['config', 'config_readonly'];

  /**
   * Read-only message.
   *
   * @var string
   */
  protected $message = 'This form will not be saved because the configuration active store is read-only.';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Turn on read-only mode.
   */
  protected function turnOnReadOnlySetting() {
    $settings['settings']['config_readonly'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Turn off read-only mode.
   */
  protected function turnOffReadOnlySetting() {
    $settings['settings']['config_readonly'] = (object) [
      'value' => FALSE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Tests switching the modules form to read-only.
   */
  public function testModulePages() {
    // Verify if we can successfully access the modules list route.
    $module_url = Url::fromRoute('system.modules_list');
    $this->drupalGet($module_url);
    $this->assertSession()->statusCodeEquals(200);
    // The modules list form is not read-only.
    $this->assertSession()->pageTextNotContains($this->message);

    // Verify if we can successfully access the modules uninstall route.
    $uninstall_url = Url::fromRoute('system.modules_uninstall');
    $this->drupalGet($uninstall_url);
    $this->assertSession()->statusCodeEquals(200);
    // The modules uninstall form is not read-only.
    $this->assertSession()->pageTextNotContains($this->message);

    // Enable the search module to confirm we can submit the form.
    $edit = [
      'modules[search][enable]' => TRUE,
    ];
    $this->drupalPostForm($module_url, $edit, 'Install');
    $this->assertSession()->pageTextNotContains($this->message);

    // Switch forms to read-only.
    $this->turnOnReadOnlySetting();
    $this->drupalGet($module_url);
    // The modules list form is read-only.
    $this->assertSession()->pageTextContains($this->message);
    $this->drupalGet($uninstall_url);
    // The modules uninstall form is read-only.
    $this->assertSession()->pageTextContains($this->message);

    $this->drupalGet($module_url);
    $elements = $this->xpath("//form[@id='system-modules']//input[@id='edit-submit']");
    $install_button = isset($elements[0]) && $elements[0] instanceof NodeElement ? $elements[0] : FALSE;
    $this->assertTrue($install_button !== FALSE, 'Found the install form submit button.');
    $this->assertTrue($install_button->hasAttribute('disabled'), 'The install modules form button is disabled.');

    // Verify that a search can be run since work-around is removed.
    // @see https://www.drupal.org/node/2845743
    $options = [
      'query' => [
        'keys' => 'test',
      ],
    ];
    $search_url = Url::fromRoute('search.view_user_search', [], $options);
    $this->drupalGet($search_url);
    $this->assertSession()->pageTextNotContains($this->message);
    $elements = $this->xpath("//form[@id='search-form']//input[@id='edit-submit']");
    $button = isset($elements[0]) && $elements[0] instanceof NodeElement ? $elements[0] : FALSE;
    $this->assertTrue($button !== FALSE, 'Found the search form submit button.');
    $this->assertFalse($button->hasAttribute('disabled'), 'The search form button is not disabled.');
  }

  /**
   * Tests switching a simple config form to read-only.
   */
  public function testSimpleConfig() {
    // Verify if we can successfully access the site information route.
    $site_url = Url::fromRoute('system.site_information_settings');
    $this->drupalGet($site_url);
    $this->assertSession()->statusCodeEquals(200);
    // The site information form is not read-only.
    $this->assertSession()->pageTextNotContains($this->message);

    // Switch forms to read-only.
    $this->turnOnReadOnlySetting();
    $this->drupalGet($site_url);
    // The site information form is read-only.
    $this->assertSession()->pageTextContains($this->message);
  }

  /**
   * Tests switching the config single import form to read-only.
   */
  public function testSingleImport() {
    // Verify if we can successfully access the single import route.
    $import_url = Url::fromRoute('config.import_single');
    $this->drupalGet($import_url);
    $this->assertSession()->statusCodeEquals(200);
    // The single import form is not read-only.
    $this->assertSession()->pageTextNotContains($this->message);

    // Switch forms to read-only.
    $this->turnOnReadOnlySetting();
    $this->drupalGet($import_url);
    // The single import form is read-only.
    $this->assertSession()->pageTextContains($this->message);
  }

}
