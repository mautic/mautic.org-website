<?php
namespace Drupal\Tests\flippy\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Flippy pagers are appearing.
 *
 * @group flippy
 */
class FlippyTest extends BrowserTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'flippy',];

  /**
   * Disable schema validation.
   *
   * @var bool
   */
  public $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an article content type that we will use for testing.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

    // Enable flippy for article content type.
    \Drupal::configFactory()
      ->getEditable('flippy.settings')
      ->set('flippy_article', 1)
      ->set('flippy_prev_label_article', 'Previous')
      ->set('flippy_next_label_article', 'Next')
      ->set('flippy_first_label_article', 'First')
      ->set('flippy_last_label_article', 'Last')
      ->set('flippy_random_label_article', 'Random')
      ->save();

    // Create sample nodes in article content type.
    for ($i = 0; $i <= 2; $i++) {
      $node = $this->container->get('entity_type.manager')->getStorage('node')
        ->create([
          'type' => 'article',
          'title' => $i,
        ]);
      $node->save();
    }
  }

  /**
   * Make sure pages appear in article node pages.
   */
  public function testPagerOnPage() {
    // Load the first page.
    $this->drupalGet('/node/1');

    // Confirm that the site didn't throw a server error or something else.
    $this->assertSession()->statusCodeEquals(200);

    // Confirm that the front page contains the standard text.
    $this->assertNoText('Previous');
    $this->assertText('Next');

    // Load the second page.
    $this->drupalGet('/node/2');

    // Confirm that the site didn't throw a server error or something else.
    $this->assertSession()->statusCodeEquals(200);

    // Confirm that the front page contains the standard text.
    $this->assertText('Previous');
    $this->assertText('Next');

    // Load the last page.
    $this->drupalGet('/node/3');

    // Confirm that the site didn't throw a server error or something else.
    $this->assertSession()->statusCodeEquals(200);

    // Confirm that the front page contains the standard text.
    $this->assertText('Previous');
    $this->assertNoText('Next');
  }

}
