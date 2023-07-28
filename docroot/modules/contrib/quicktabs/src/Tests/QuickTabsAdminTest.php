<?php

namespace Drupal\quicktabs\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests creating and saving a QuickTabs instance..
 *
 * @group quicktabs
 */
class QuickTabsAdminTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'block',
    'menu_ui',
    'user',
    'taxonomy',
    'toolbar',
    'quicktabs',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $perms = [
      'access toolbar',
      'access administration pages',
      'administer site configuration',
      'bypass node access',
      'administer themes',
      'administer nodes',
      'access content overview',
      'administer blocks',
      'administer menu',
      'administer modules',
      'administer permissions',
      'administer users',
      'access user profiles',
      'administer taxonomy',
      'administer quicktabs',
    ];

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser($perms);

    $this->drupalLogin($this->adminUser);

    // Create an article content type.
    $this->drupalCreateContentType([
      'type' => 'article',
    ]);
  }

  /**
   * Test all vocabularies appear on admin page.
   */
  public function testQuickTabsAdmin() {
    $this->drupalGet('admin/structure/quicktabs');
    $this->assertResponse(200);
    $this->assertRaw('Quick Tabs');
    $this->drupalGet('admin/structure/quicktabs/add');
    $this->assertResponse(200);
    $this->assertRaw('Add QuickTabs Instance');
    $this->assertRaw('Name');
    $this->assertRaw('Renderer');
    $this->assertRaw('Default tab');
    $this->assertRaw('Hide empty tabs');
    $this->assertRaw('Add tab');
    $this->assertRaw('Save');

    $node1 = $this->drupalCreateNode([
      'title' => $this->t('Node 1'),
      'type' => 'article',
    ]);
    $node2 = $this->drupalCreateNode([
      'title' => $this->t('Node 2'),
      'type' => 'article',
    ]);

    $edit = [
      'label' => $this->randomMachineName(),
      'id' => strtolower($this->randomMachineName()),
      'renderer' => 'quick_tabs',
      'options[quick_tabs][ajax]' => 0,
      'hide_empty_tabs' => 1,
      'default_tab' => 9999,
      'configuration_data[0][title]' => $this->randomMachineName(),
      'configuration_data[0][type]' => 'node_content',
      'configuration_data[0][content][node_content][options][nid]' => $node1->id(),
      'configuration_data[0][content][node_content][options][view_mode]' => 'full',
      'configuration_data[0][content][node_content][options][hide_title]' => 1,
      'configuration_data[1][title]' => $this->randomMachineName(),
      'configuration_data[1][type]' => 'node_content',
      'configuration_data[1][content][node_content][options][nid]' => $node2->id(),
      'configuration_data[1][content][node_content][options][view_mode]' => 'full',
      'configuration_data[1][content][node_content][options][hide_title]' => 1,
    ];

    $this->drupalPostForm('admin/structure/quicktabs/add', $edit, $this->t('Save'));

    $qt = \Drupal::service('entity_type.manager')->getStorage('quicktabs_instance')->load($edit['id']);

    $this->assertEqual('Drupal\quicktabs\Entity\QuickTabsInstance', get_class($qt));
    $this->assertEqual($qt->id(), $edit['id']);
    $this->assertEqual($qt->label(), $edit['label']);
    $this->assertEqual($qt->getRenderer(), $edit['renderer']);
    $this->assertEqual($qt->getHideEmptyTabs(), $edit['hide_empty_tabs']);
    $this->assertEqual($qt->getDefaultTab(), $edit['default_tab']);

    $configurationData = $qt->getConfigurationData();
    $this->assertEqual($configurationData[0]['title'], $edit['configuration_data[0][title]']);
    $this->assertEqual($configurationData[1]['title'], $edit['configuration_data[1][title]']);
    $this->assertEqual($configurationData[0]['type'], $edit['configuration_data[0][type]']);
    $this->assertEqual($configurationData[1]['type'], $edit['configuration_data[1][type]']);
    $this->assertEqual($configurationData[0]['content']['node_content']['options']['nid'], $edit['configuration_data[0][content][node_content][options][nid]']);
    $this->assertEqual($configurationData[1]['content']['node_content']['options']['nid'], $edit['configuration_data[1][content][node_content][options][nid]']);
    $this->assertEqual($configurationData[0]['content']['node_content']['options']['view_mode'], $edit['configuration_data[0][content][node_content][options][view_mode]']);
    $this->assertEqual($configurationData[1]['content']['node_content']['options']['view_mode'], $edit['configuration_data[1][content][node_content][options][view_mode]']);
    $this->assertEqual($configurationData[0]['content']['node_content']['options']['hide_title'], $edit['configuration_data[0][content][node_content][options][hide_title]']);
    $this->assertEqual($configurationData[1]['content']['node_content']['options']['hide_title'], $edit['configuration_data[1][content][node_content][options][hide_title]']);

    $this->drupalPostForm('admin/structure/quicktabs/' . $qt->id() . '/delete', [], $this->t('Delete'));

    $qt = \Drupal::service('entity_type.manager')->getStorage('quicktabs_instance')->load($edit['id']);
    $this->assertNull($qt, $this->t('QuickTabs instance not found in database'));
  }

}
