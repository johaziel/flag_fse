<?php

namespace Drupal\Tests\flag_fse\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\flag\Entity\Flag;

/**
 * Tests the Flag FSE action plugin.
 *
 * @group flag_fse
 */
class FlagFseActionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * Flag module doesn't properly declare schema for dynamic linkTypeConfig.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'flag',
    'flag_fse',
    'views',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create article content type.
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();

    // Create flag.
    $flag = Flag::create([
      'id' => 'bookmark',
      'label' => 'Bookmark',
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
    ]);
    $flag->save();
  }

  /**
   * Tests that the FSE action plugin exists.
   */
  public function testActionAvailability() {
    // Simply verify that the action plugin is defined.
    $action_manager = \Drupal::service('plugin.manager.action');
    $definitions = $action_manager->getDefinitions();

    $this->assertArrayHasKey('flag_fse', $definitions, 'Flag FSE action should be available');
    $this->assertEquals('Flag for someone else', (string) $definitions['flag_fse']['label']);
  }

}
