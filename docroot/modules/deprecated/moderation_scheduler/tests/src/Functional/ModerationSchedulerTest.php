<?php

namespace Drupal\Tests\moderation_scheduler\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the existence of Admin Toolbar module.
 *
 * @group moderation_scheduler
 */
class ModerationSchedulerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'moderation_scheduler',
  ];

  /**
   * The internal name of the standard content type created for testing.
   *
   * @var string
   */
  protected $type;

  /**
   * The readable name of the standard content type created for testing.
   *
   * @var string
   */
  protected $typeName;

  /**
   * The node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodetype;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;
  /**
   * A test user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->type = 'page';
    $this->typeName = 'Basic page';
    // Create a 'Basic Page' content type, with 'page' as the identifier.
    // The test files should use $this->type and $this->typeName and not use
    // $this->nodetype->get('type') or $this->nodetype->get('name'), nor have
    // the hard-coded strings 'page' and 'Basic page'.
    /** @var NodeTypeInterface $nodetype */
    $this->nodetype = $this->drupalCreateContentType([
      'type' => $this->type,
      'name' => $this->typeName,
    ]);

    // Add scheduler functionality to the node type.
    $this->nodetype->save();

    // Define nodeStorage for use in many tests.
    /** @var EntityStorageInterface $nodeStorage */
    $this->nodeStorage = $this->container->get('entity.manager')->getStorage('node');

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer nodes',
      'access content',
      'create ' . $this->type . ' content',
      'edit own ' . $this->type . ' content',
      'delete own ' . $this->type . ' content',
      'view own unpublished content',
      'access content overview',
      'access site reports',
      'administer site configuration',
      'access administration pages',
      'edit moderation scheduler field',
      'administer moderation_scheduler module',
    ]);

    $this->drupalLogin($this->adminUser);

  }

  /**
   * TestModerationScheduler functional test.
   */
  public function testModerationScheduler() {
    // Login is required here before creating the publish_on date and time
    // values so that date.formatter can utilise the current users timezone. The
    // constraints receive values which have been converted using the users
    // timezone so they need to be consistent.
    $this->drupalLogin($this->adminUser);

    // Create node values. Set time to one hour in the future.
    $edit = [
      'title[0][value]' => 'Publish This Node',
    ];
    $this->helpTestModerationScheduler($edit);

  }

  /**
   * Helper function for testModerationScheduler().
   *
   * Schedules content, runs cron and asserts status.
   */
  protected function helpTestModerationScheduler($edit) {
    $this->drupalPostForm('node/add/' . $this->type, $edit, t('Save'));
    // Verify that the node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $node->save();
    $this->assertTrue($node, sprintf('Basic page "%s" has been created.', $edit['title[0][value]']));
    if (empty($node)) {
      $this->assert(FALSE, 'Test halted because node was not created.');
      return;
    }

    // Modify the scheduler field data to a time in the past, then run cron.
    // Refresh the node cache and check the node status.
    $this->nodeStorage->resetCache([$node->id()]);
    $node = $this->nodeStorage->load($node->id());

    $edit['status[value]'] = 0;
    $edit['field_scheduled_time[0][value][date]'] = '2019-02-24';

    $edit['field_scheduled_time[0][value][time]'] = '09:00:00';

    $node->set('field_scheduled_time', '2019-02-24T08:00:00');
    $node->status = "0";
    $node->save();

    // Modify the scheduler field data to a time in the past, then run cron.
    $this->drupalPostForm('node/' . $node->id() . '/edit/', $edit, t('Save'));

    if (!$node->isPublished()) {
      $this->assertRaw('name="field_scheduled_time[0][value][date]"');
    }
  }

}
